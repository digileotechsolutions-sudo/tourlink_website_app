<?php

namespace Tests\Feature;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\User;
use App\PaymentMethod;
use App\PaymentStatus;
use App\Services\Payments\PaymentService;
use App\Services\Payments\PesapalGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnifiedPaymentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setReferralSettings();
    }

    public function test_traveler_can_see_booking_balance_and_payment_link(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $booking = Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'total_amount' => 15000,
        ]);

        $this->actingAs($traveler)
            ->get(route('traveler.bookings'))
            ->assertOk()
            ->assertSee('Pay balance')
            ->assertSee(route('payments.show', $booking), false)
            ->assertSee('Balance KES 15,000');
    }

    public function test_admin_can_record_and_verify_manual_payment(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $booking = Booking::factory()->create(['total_amount' => 5000]);

        $this->actingAs($admin)
            ->post(route('admin.bookings.cash-payments.store', $booking), [
                'amount' => 1000,
                'notes' => 'Cash received at office',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $cashPayment = Payment::query()->where('booking_id', $booking->id)->firstOrFail();
        $this->assertSame(PaymentMethod::Cash, $cashPayment->payment_method);
        $this->assertSame(PaymentStatus::Successful, $cashPayment->status);
        $this->assertSame($admin->id, $cashPayment->received_by);
        $this->assertNotNull($cashPayment->receipt_number);

        $this->actingAs($admin)
            ->post(route('admin.bookings.bank-transfers.store', $booking), [
                'amount' => 1500,
                'reference' => 'BANK-TEST-001',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $bankPayment = Payment::query()->where('transaction_reference', 'BANK-TEST-001')->firstOrFail();
        $this->assertSame(PaymentMethod::BankTransfer, $bankPayment->payment_method);
        $this->assertSame(PaymentStatus::Pending, $bankPayment->status);

        $this->actingAs($admin)
            ->patch(route('admin.payments.verify-bank-transfer', $bankPayment))
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame(PaymentStatus::Successful, $bankPayment->refresh()->status);
        $this->assertSame($admin->id, $bankPayment->received_by);
    }

    public function test_admin_cannot_record_payment_above_remaining_booking_balance(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $booking = Booking::factory()->create(['total_amount' => 1000]);

        $this->actingAs($admin)
            ->from(route('admin.bookings.show', $booking))
            ->post(route('admin.bookings.cash-payments.store', $booking), ['amount' => 1001])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseMissing('payments', [
            'booking_id' => $booking->id,
            'payment_method' => PaymentMethod::Cash->value,
        ]);
    }

    public function test_partially_paid_booking_cannot_be_cancelled_without_finance_review(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $booking = Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'total_amount' => 5000,
        ]);
        app(PaymentService::class)->recordManualPayment(
            $booking,
            $admin,
            PaymentMethod::Cash,
            1000,
            null,
            null,
        );

        $this->actingAs($traveler)
            ->patch(route('traveler.bookings.cancel', $booking))
            ->assertSessionHasErrors('booking');

        $this->assertNotSame(BookingStatus::Cancelled, $booking->refresh()->status);
    }

    public function test_refund_is_pending_until_admin_marks_provider_refund_completed(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $booking = Booking::factory()->create(['total_amount' => 5000]);
        $payment = app(PaymentService::class)->recordManualPayment(
            $booking,
            $admin,
            PaymentMethod::Cash,
            5000,
            null,
            null,
        );

        $this->actingAs($admin)
            ->post(route('admin.payments.refunds.store', $payment), [
                'amount' => 1200,
                'reason' => 'Partial cancellation',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $refund = $payment->refunds()->firstOrFail();
        $this->assertSame('PENDING', $refund->status);

        $this->actingAs($admin)
            ->patch(route('admin.refunds.complete', $refund), [
                'provider_reference' => 'PROVIDER-REF-001',
            ])
            ->assertRedirect(route('admin.bookings.show', $booking));

        $this->assertSame('COMPLETED', $refund->refresh()->status);
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->refresh()->status);
    }

    public function test_full_refund_closes_the_booking_as_refunded(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $booking = Booking::factory()->create(['total_amount' => 1000]);
        $payment = app(PaymentService::class)->recordManualPayment(
            $booking,
            $admin,
            PaymentMethod::Cash,
            1000,
            null,
            null,
        );
        $refund = app(PaymentService::class)->createRefund($payment, $admin, 1000, 'Full cancellation');

        app(PaymentService::class)->completeRefund($refund, $admin, 'PROVIDER-REF-FULL');

        $this->assertSame(PaymentStatus::Refunded, $payment->refresh()->status);
        $this->assertSame(BookingStatus::Refunded, $booking->refresh()->status);
    }

    public function test_receipts_are_limited_to_the_traveler_and_admin(): void
    {
        $traveler = User::factory()->create(['role' => 'TRAVELER']);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $booking = Booking::factory()->create([
            'traveler_id' => $traveler->id,
            'total_amount' => 1000,
        ]);
        $payment = app(PaymentService::class)->recordManualPayment(
            $booking,
            $admin,
            PaymentMethod::Cash,
            1000,
            null,
            null,
        );

        $this->actingAs($operator)
            ->get(route('payments.receipt', $payment))
            ->assertNotFound();

        $this->actingAs($traveler)
            ->get(route('payments.receipt.download', $payment))
            ->assertDownload();

        $this->actingAs($admin)
            ->get(route('payments.receipt.download', $payment))
            ->assertDownload();
    }

    public function test_pesapal_ipn_uses_verified_provider_status_and_supports_retries(): void
    {
        $booking = Booking::factory()->create(['total_amount' => 1000]);
        $payment = $this->processingPesapalPayment($booking);
        $gateway = \Mockery::mock(PesapalGateway::class);
        $gateway->shouldReceive('verify')
            ->twice()
            ->with('PESAPAL-TRACK-001')
            ->andReturn([
                'payment_status_description' => 'Completed',
                'amount' => '1000.00',
                'currency' => 'KES',
                'merchant_reference' => $payment->merchant_reference,
                'confirmation_code' => 'PESAPAL-CONFIRM-001',
            ]);
        $this->instance(PesapalGateway::class, $gateway);

        $this->getJson('/api/pesapal/ipn?OrderTrackingId=PESAPAL-TRACK-001')
            ->assertOk()
            ->assertJsonPath('payment_status', 'successful');
        $this->getJson('/api/pesapal/ipn?OrderTrackingId=PESAPAL-TRACK-001')
            ->assertOk()
            ->assertJsonPath('payment_status', 'successful');

        $this->assertSame(PaymentStatus::Successful, $payment->refresh()->status);
        $this->assertSame('PESAPAL-CONFIRM-001', $payment->transaction_reference);
        $this->assertSame(1, Notification::query()
            ->where('user_id', $booking->traveler_id)
            ->where('title', 'Payment received')
            ->count());
    }

    public function test_pesapal_amount_mismatch_does_not_confirm_payment(): void
    {
        $booking = Booking::factory()->create(['total_amount' => 1000]);
        $payment = $this->processingPesapalPayment($booking);
        $gateway = \Mockery::mock(PesapalGateway::class);
        $gateway->shouldReceive('verify')
            ->once()
            ->with('PESAPAL-TRACK-001')
            ->andReturn([
                'payment_status_description' => 'Completed',
                'amount' => '1001.00',
                'currency' => 'KES',
                'merchant_reference' => $payment->merchant_reference,
                'confirmation_code' => 'PESAPAL-CONFIRM-001',
            ]);
        $this->instance(PesapalGateway::class, $gateway);

        $this->getJson('/api/pesapal/ipn?OrderTrackingId=PESAPAL-TRACK-001')
            ->assertStatus(409);

        $this->assertSame(PaymentStatus::Processing, $payment->refresh()->status);
    }

    private function processingPesapalPayment(Booking $booking): Payment
    {
        return Payment::query()->create([
            'booking_id' => $booking->id,
            'status' => PaymentStatus::Processing,
            'provider' => 'PESAPAL',
            'payment_method' => PaymentMethod::Card,
            'merchant_reference' => 'HTP-PESAPAL-001',
            'transaction_reference' => 'PESAPAL-TRACK-001',
            'amount' => 1000,
            'metadata' => [
                'order_tracking_id' => 'PESAPAL-TRACK-001',
                'merchant_reference' => 'HTP-PESAPAL-001',
            ],
        ]);
    }
}
