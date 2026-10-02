<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\Payments\PaymentGatewaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class AdminPaymentGatewaySettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);
    }

    public function test_admin_can_save_encrypted_gateway_credentials(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->put(route('admin.settings.payment-gateways.update'), $this->validPayload())
            ->assertRedirect(route('admin.settings.index'))
            ->assertSessionHas('status');

        $consumerSecret = Setting::query()->where('key', 'payments.mpesa.consumer_secret')->value('value');
        $passkey = Setting::query()->where('key', 'payments.mpesa.passkey')->value('value');
        $pesapalSecret = Setting::query()->where('key', 'payments.pesapal.consumer_secret')->value('value');

        $this->assertNotSame('daraja-secret-value', $consumerSecret);
        $this->assertNotSame('daraja-passkey-value', $passkey);
        $this->assertNotSame('pesapal-secret-value', $pesapalSecret);
        $this->assertSame('daraja-secret-value', Crypt::decryptString($consumerSecret));
        $this->assertSame('daraja-passkey-value', Crypt::decryptString($passkey));
        $this->assertSame('pesapal-secret-value', Crypt::decryptString($pesapalSecret));
        $this->assertSame('daraja-secret-value', app(PaymentGatewaySettings::class)->get('mpesa', 'consumer_secret'));
        $this->assertSame('pesapal-secret-value', app(PaymentGatewaySettings::class)->get('pesapal', 'consumer_secret'));
        $this->assertDatabaseHas('admin_logs', ['admin_id' => $admin->id, 'action' => 'payment_gateways.updated']);

        $this->get(route('admin.settings.index'))
            ->assertOk()
            ->assertSee('Encrypted value stored')
            ->assertDontSee('daraja-secret-value')
            ->assertDontSee('pesapal-secret-value');
    }

    public function test_blank_secret_fields_keep_existing_credentials(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($admin)
            ->put(route('admin.settings.payment-gateways.update'), $this->validPayload())
            ->assertSessionHas('status');
        $existingValue = Setting::query()->where('key', 'payments.mpesa.consumer_secret')->value('value');

        $this->put(route('admin.settings.payment-gateways.update'), [
            'mpesa_environment' => 'production',
            'pesapal_environment' => 'production',
            'mpesa_consumer_secret' => '',
            'pesapal_consumer_secret' => '',
        ])->assertSessionHas('status');

        $this->assertSame($existingValue, Setting::query()->where('key', 'payments.mpesa.consumer_secret')->value('value'));
        $this->assertSame('production', app(PaymentGatewaySettings::class)->get('mpesa', 'environment'));
    }

    public function test_admin_can_remove_a_saved_override_without_exposing_the_value(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->actingAs($admin)
            ->put(route('admin.settings.payment-gateways.update'), $this->validPayload())
            ->assertSessionHas('status');

        $this->put(route('admin.settings.payment-gateways.update'), [
            'mpesa_environment' => 'sandbox',
            'pesapal_environment' => 'sandbox',
            'clear_mpesa_consumer_secret' => '1',
        ])->assertSessionHas('status');

        $this->assertDatabaseMissing('settings', ['key' => 'payments.mpesa.consumer_secret']);
        $this->assertFalse(app(PaymentGatewaySettings::class)->isStored('mpesa', 'consumer_secret'));
    }

    public function test_gateway_secrets_are_not_flashed_back_after_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $this->actingAs($admin)
            ->from(route('admin.settings.index'))
            ->put(route('admin.settings.payment-gateways.update'), $this->validPayload([
                'mpesa_callback_url' => 'not-a-url',
            ]))
            ->assertSessionHasErrors('mpesa_callback_url')
            ->assertSessionMissing('_old_input.mpesa_consumer_secret')
            ->assertSessionMissing('_old_input.pesapal_consumer_secret');
    }

    public function test_non_admins_cannot_view_or_update_payment_credentials(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'TRAVELER']))
            ->get(route('admin.settings.index'))
            ->assertForbidden();

        $this->put(route('admin.settings.payment-gateways.update'), $this->validPayload())
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'payments.mpesa.consumer_secret']);
    }

    /**
     * @return array<string, string>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'mpesa_environment' => 'sandbox',
            'mpesa_consumer_key' => 'daraja-client-key',
            'mpesa_consumer_secret' => 'daraja-secret-value',
            'mpesa_shortcode' => '123456',
            'mpesa_passkey' => 'daraja-passkey-value',
            'mpesa_callback_url' => 'https://tourlink.example/api/mpesa/callback',
            'pesapal_environment' => 'sandbox',
            'pesapal_consumer_key' => 'pesapal-client-key',
            'pesapal_consumer_secret' => 'pesapal-secret-value',
            'pesapal_ipn_id' => 'pesapal-ipn-id',
        ], $overrides);
    }
}
