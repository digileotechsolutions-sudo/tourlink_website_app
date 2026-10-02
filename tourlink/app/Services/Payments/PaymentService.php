<?php

namespace App\Services\Payments;

use App\BookingStatus;
use App\Models\Booking;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\PaymentAudit;
use App\Models\Refund;
use App\Models\User;
use App\PaymentMethod;
use App\PaymentStatus;
use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * @return array{total: int, paid: int, balance: int, status: string}
     */
    public function summary(Booking $booking): array
    {
        if ($booking->relationLoaded('payments')
            && $booking->payments->every(fn (Payment $payment): bool => $payment->relationLoaded('refunds'))) {
            $grossPaid = (int) $booking->payments
                ->filter(fn (Payment $payment): bool => in_array($payment->status, [
                    PaymentStatus::Successful,
                    PaymentStatus::Refunded,
                    PaymentStatus::PartiallyRefunded,
                ], true))
                ->sum('amount');
            $refunded = (int) $booking->payments
                ->flatMap(fn (Payment $payment) => $payment->refunds)
                ->where('status', 'COMPLETED')
                ->sum('amount');
        } else {
            $grossPaid = (int) $booking->payments()
                ->whereIn('status', [
                    PaymentStatus::Successful->value,
                    PaymentStatus::Refunded->value,
                    PaymentStatus::PartiallyRefunded->value,
                ])
                ->sum('amount');
            $refunded = (int) Refund::query()
                ->where('status', 'COMPLETED')
                ->whereHas('payment', fn ($query) => $query->where('booking_id', $booking->id))
                ->sum('amount');
        }
        $total = max(0, (int) $booking->total_amount);
        $paid = min($total, max(0, $grossPaid - $refunded));
        $balance = max(0, $total - $paid);
        $status = $balance === 0 && $paid > 0
            ? 'PAID'
            : ($paid > 0 ? 'PARTIALLY_PAID' : ($refunded > 0 ? 'REFUNDED' : 'UNPAID'));

        return compact('total', 'paid', 'balance', 'status');
    }

    public function hasPaymentInFlight(Booking $booking): bool
    {
        $isInFlight = fn (Payment $payment): bool => $payment->status === PaymentStatus::Processing
            || ($payment->status === PaymentStatus::Pending && (
                in_array($payment->payment_method, [PaymentMethod::Card, PaymentMethod::BankTransfer], true)
                || $payment->phone_number !== null
                || $payment->transaction_reference !== null
                || $payment->daraja_checkout_request_id !== null
                || $payment->provider_response !== null
            ));

        if ($booking->relationLoaded('payments')) {
            return $booking->payments->contains($isInFlight);
        }

        return $booking->payments()
            ->where(function ($query): void {
                $query->where('status', PaymentStatus::Processing->value)
                    ->orWhere(function ($pending): void {
                        $pending->where('status', PaymentStatus::Pending->value)
                            ->where(function ($actionable): void {
                                $actionable->whereIn('payment_method', [
                                    PaymentMethod::Card->value,
                                    PaymentMethod::BankTransfer->value,
                                ])
                                    ->orWhereNotNull('phone_number')
                                    ->orWhereNotNull('transaction_reference')
                                    ->orWhereNotNull('daraja_checkout_request_id')
                                    ->orWhereNotNull('provider_response');
                            });
                    });
            })
            ->exists();
    }

    public function createAttempt(
        Booking $booking,
        PaymentMethod $method,
        int $amount,
        string $provider,
        ?User $actor = null,
        ?string $phoneNumber = null,
    ): Payment {
        return DB::transaction(function () use ($booking, $method, $amount, $provider, $actor, $phoneNumber): Payment {
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $payment = $lockedBooking->payments()
                ->where('status', PaymentStatus::Pending->value)
                ->whereNull('transaction_reference')
                ->whereNull('daraja_checkout_request_id')
                ->whereNull('provider_response')
                ->oldest('created_at')
                ->lockForUpdate()
                ->first();

            $this->assertAvailableAmount($lockedBooking, $amount, $payment?->id);

            if (! $payment) {
                $payment = new Payment([
                    'booking_id' => $lockedBooking->id,
                    'merchant_reference' => 'HTP-'.Str::ulid(),
                ]);
            }

            $payment->forceFill([
                'payment_method' => $method,
                'provider' => $provider,
                'amount' => $amount,
                'phone_number' => $phoneNumber,
                'status' => PaymentStatus::Pending,
                'failure_reason' => null,
            ])->save();

            $this->audit($payment, 'PAYMENT_ATTEMPT_CREATED', [
                'method' => $method->value,
                'amount' => $amount,
                'actor_id' => $actor?->id,
            ]);

            return $payment;
        }, attempts: 3);
    }

    public function recordManualPayment(
        Booking $booking,
        User $admin,
        PaymentMethod $method,
        int $amount,
        ?string $reference,
        ?string $notes,
        ?string $ipAddress = null,
    ): Payment {
        abort_unless($admin->role === Role::Admin, 403);
        abort_unless(in_array($method, [PaymentMethod::Cash, PaymentMethod::BankTransfer], true), 422);

        return DB::transaction(function () use ($booking, $admin, $method, $amount, $reference, $notes, $ipAddress): Payment {
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();
            $this->assertAvailableAmount($lockedBooking, $amount);
            $isCash = $method === PaymentMethod::Cash;
            $payment = $lockedBooking->payments()->create([
                'status' => $isCash ? PaymentStatus::Successful : PaymentStatus::Pending,
                'provider' => 'MANUAL',
                'payment_method' => $method,
                'transaction_reference' => $reference,
                'merchant_reference' => 'HTP-'.Str::ulid(),
                'amount' => $amount,
                'receipt_number' => $isCash ? $this->receiptNumber() : null,
                'received_by' => $isCash ? $admin->id : null,
                'notes' => $notes,
                'paid_at' => $isCash ? now() : null,
            ]);

            $this->audit($payment, $isCash ? 'CASH_PAYMENT_RECORDED' : 'BANK_TRANSFER_RECORDED', [
                'admin_id' => $admin->id,
                'amount' => $amount,
                'reference' => $reference,
                'ip_address' => $ipAddress,
            ]);

            if ($isCash) {
                $this->afterSuccessfulPayment($payment, $lockedBooking);
            }

            return $payment;
        }, attempts: 3);
    }

    public function verifyBankTransfer(Payment $payment, User $admin, ?string $ipAddress = null): Payment
    {
        abort_unless($admin->role === Role::Admin, 403);

        return DB::transaction(function () use ($payment, $admin, $ipAddress): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedPayment->payment_method === PaymentMethod::BankTransfer, 422);
            abort_unless($lockedPayment->status === PaymentStatus::Pending, 409, 'This bank transfer is no longer pending.');

            $this->audit($lockedPayment, 'BANK_TRANSFER_VERIFIED', [
                'admin_id' => $admin->id,
                'ip_address' => $ipAddress,
            ]);

            return $this->markSuccessful($lockedPayment, $admin);
        }, attempts: 3);
    }

    public function markFailed(Payment $payment, string $reason, array $providerResponse = []): Payment
    {
        return DB::transaction(function () use ($payment, $reason, $providerResponse): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (in_array($lockedPayment->status, [
                PaymentStatus::Successful,
                PaymentStatus::Refunded,
                PaymentStatus::PartiallyRefunded,
            ], true)) {
                return $lockedPayment;
            }

            $lockedPayment->forceFill([
                'status' => PaymentStatus::Failed,
                'failure_reason' => $reason,
                'provider_response' => array_merge($lockedPayment->provider_response ?? [], $providerResponse),
            ])->save();
            $this->audit($lockedPayment, 'PAYMENT_FAILED', ['reason' => $reason]);

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    public function markProcessing(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($lockedPayment->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'This payment attempt has already been started or is no longer available.',
                ]);
            }

            $lockedPayment->forceFill(['status' => PaymentStatus::Processing])->save();
            $this->audit($lockedPayment, 'PAYMENT_PROCESSING', []);

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordProviderEvent(Payment $payment, string $event, array $payload): void
    {
        $this->audit($payment, $event, $payload);
    }

    public function ensureReceipt(Payment $payment): Payment
    {
        if ($payment->receipt_number) {
            return $payment;
        }

        return DB::transaction(function () use ($payment): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if (! $lockedPayment->receipt_number) {
                $lockedPayment->forceFill(['receipt_number' => $this->receiptNumber()])->save();
                $this->audit($lockedPayment, 'RECEIPT_GENERATED', []);
            }

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    public function markSuccessful(
        Payment $payment,
        ?User $actor = null,
        ?string $transactionReference = null,
        array $providerResponse = [],
    ): Payment
    {
        return DB::transaction(function () use ($payment, $actor, $transactionReference, $providerResponse): Payment {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $booking = Booking::query()->whereKey($lockedPayment->booking_id)->lockForUpdate()->firstOrFail();

            if (in_array($lockedPayment->status, [
                PaymentStatus::Successful,
                PaymentStatus::Refunded,
                PaymentStatus::PartiallyRefunded,
            ], true)) {
                return $lockedPayment;
            }

            $this->assertAvailableAmount($booking, $lockedPayment->amount, $lockedPayment->id);

            $lockedPayment->forceFill([
                'status' => PaymentStatus::Successful,
                'transaction_reference' => $transactionReference ?? $lockedPayment->transaction_reference,
                'receipt_number' => $lockedPayment->receipt_number ?: $this->receiptNumber(),
                'received_by' => $lockedPayment->payment_method === PaymentMethod::BankTransfer
                    ? ($actor?->id ?? $lockedPayment->received_by)
                    : $lockedPayment->received_by,
                'provider_response' => array_merge($lockedPayment->provider_response ?? [], $providerResponse),
                'paid_at' => now(),
                'failure_reason' => null,
            ])->save();

            $this->audit($lockedPayment, 'PAYMENT_CONFIRMED', [
                'actor_id' => $actor?->id,
                'transaction_reference' => $transactionReference,
            ]);
            $this->afterSuccessfulPayment($lockedPayment, $booking);

            return $lockedPayment->refresh();
        }, attempts: 3);
    }

    public function createRefund(Payment $payment, User $admin, int $amount, string $reason, ?string $ipAddress = null): Refund
    {
        abort_unless($admin->role === Role::Admin, 403);

        return DB::transaction(function () use ($payment, $admin, $amount, $reason, $ipAddress): Refund {
            $lockedPayment = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($lockedPayment->status, [
                PaymentStatus::Successful,
                PaymentStatus::PartiallyRefunded,
            ], true), 422, 'Only successful payments can be refunded.');

            $refunded = (int) $lockedPayment->refunds()
                ->whereIn('status', ['PENDING', 'COMPLETED'])
                ->sum('amount');
            $refundable = max(0, $lockedPayment->amount - $refunded);
            if ($amount < 1 || $amount > $refundable) {
                throw ValidationException::withMessages([
                    'amount' => 'The refund amount must be within the remaining refundable amount.',
                ]);
            }

            $refund = $lockedPayment->refunds()->create([
                'amount' => $amount,
                'reason' => $reason,
                'status' => 'PENDING',
                'reference' => 'HTR-'.Str::ulid(),
                'authorized_by' => $admin->id,
            ]);

            $this->audit($lockedPayment, 'REFUND_REQUESTED', [
                'admin_id' => $admin->id,
                'refund_id' => $refund->id,
                'amount' => $amount,
                'reason' => $reason,
                'ip_address' => $ipAddress,
            ]);

            return $refund;
        }, attempts: 3);
    }

    public function completeRefund(Refund $refund, User $admin, ?string $providerReference = null, ?string $ipAddress = null): Refund
    {
        abort_unless($admin->role === Role::Admin, 403);

        return DB::transaction(function () use ($refund, $admin, $providerReference, $ipAddress): Refund {
            $lockedRefund = Refund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
            if ($lockedRefund->status === 'COMPLETED') {
                return $lockedRefund;
            }

            abort_unless($lockedRefund->status === 'PENDING', 409, 'This refund is no longer pending.');
            $lockedRefund->forceFill([
                'status' => 'COMPLETED',
                'provider_reference' => $providerReference,
                'authorized_by' => $admin->id,
            ])->save();

            $payment = $lockedRefund->payment()->lockForUpdate()->firstOrFail();
            $completedAmount = (int) $payment->refunds()->where('status', 'COMPLETED')->sum('amount');
            $payment->forceFill([
                'status' => $completedAmount >= $payment->amount
                    ? PaymentStatus::Refunded
                    : PaymentStatus::PartiallyRefunded,
            ])->save();
            $booking = Booking::query()->whereKey($payment->booking_id)->lockForUpdate()->firstOrFail();
            if ($this->summary($booking)['status'] === 'REFUNDED'
                && ! in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Completed, BookingStatus::Refunded], true)) {
                $booking->forceFill(['status' => BookingStatus::Refunded])->save();
            }
            $this->audit($payment, 'REFUND_COMPLETED', [
                'admin_id' => $admin->id,
                'refund_id' => $lockedRefund->id,
                'provider_reference' => $providerReference,
                'ip_address' => $ipAddress,
            ]);

            return $lockedRefund->refresh();
        }, attempts: 3);
    }

    private function assertAvailableAmount(Booking $booking, int $amount, ?string $ignorePaymentId = null): void
    {
        if (in_array($booking->status, [BookingStatus::Cancelled, BookingStatus::Refunded], true)) {
            throw ValidationException::withMessages([
                'payment' => 'This booking is cancelled or refunded and cannot accept another payment.',
            ]);
        }

        $summary = $this->summary($booking);
        $reserved = (int) $booking->payments()
            ->where(function ($query): void {
                $query->where('status', PaymentStatus::Processing->value)
                    ->orWhere(function ($pending): void {
                        $pending->where('status', PaymentStatus::Pending->value)
                            ->where(function ($manual): void {
                                $manual->whereIn('payment_method', [
                                    PaymentMethod::Card->value,
                                    PaymentMethod::BankTransfer->value,
                                ])
                                    ->orWhereNotNull('phone_number')
                                    ->orWhereNotNull('transaction_reference');
                            });
                    });
            })
            ->when($ignorePaymentId, fn ($query) => $query->where('id', '!=', $ignorePaymentId))
            ->sum('amount');
        $available = max(0, $summary['balance'] - $reserved);

        if ($amount < 1 || $amount > $available) {
            throw ValidationException::withMessages([
                'amount' => "The amount must be between KES 1 and the available balance of KES {$available}.",
            ]);
        }
    }

    private function afterSuccessfulPayment(Payment $payment, Booking $booking): void
    {
        $summary = $this->summary($booking);
        if ($summary['balance'] === 0
            && in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed], true)) {
            $booking->forceFill(['status' => BookingStatus::Paid])->save();
        }

        Notification::query()->create([
            'user_id' => $booking->traveler_id,
            'title' => 'Payment received',
            'body' => "Your payment of {$booking->currency} ".number_format($payment->amount)." for booking {$booking->reference} was successful.",
            'type' => 'PAYMENT',
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function audit(Payment $payment, string $event, array $payload): void
    {
        PaymentAudit::query()->create([
            'payment_id' => $payment->id,
            'event_type' => $event,
            'checkout_request_id' => $payment->daraja_checkout_request_id,
            'callback_hash' => hash('sha256', Str::uuid()->toString()),
            'payload' => $payload,
        ]);
    }

    private function receiptNumber(): string
    {
        return 'HTL-'.Str::ulid();
    }
}
