<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\Payments\PaymentGatewaySettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120']]);
        $settings = Setting::query()
            ->select(['id', 'key', 'updated_at'])
            ->where('key', 'not like', 'payments.%')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('key', 'like', '%'.$search.'%'))
            ->orderBy('key')
            ->paginate(30)
            ->withQueryString();
        $gatewaySettings = app(PaymentGatewaySettings::class);
        $paymentGatewayState = $gatewaySettings->configuredState();
        $paymentGatewayEnvironments = [
            'mpesa' => $gatewaySettings->get('mpesa', 'environment'),
            'pesapal' => $gatewaySettings->get('pesapal', 'environment'),
        ];

        return view('admin.settings.index', compact('settings', 'filters', 'paymentGatewayState', 'paymentGatewayEnvironments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Z][A-Z0-9_.-]*$/', 'unique:settings,key'],
            'value' => ['required', 'string', 'max:10000'],
        ]);
        abort_if(str_starts_with(strtolower($data['key']), 'payments.'), 422, 'Payment gateway credentials must be managed in Payment settings.');

        DB::transaction(function () use ($request, $data): void {
            $setting = Setting::query()->create($data);
            $this->audit($request, 'setting.created', $setting, ['key', 'value']);
        });

        return back()->with('status', 'Setting created.');
    }

    public function update(Request $request, Setting $setting): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Z][A-Z0-9_.-]*$/', Rule::unique('settings', 'key')->ignore($setting->id)],
            'value' => ['nullable', 'string', 'max:10000'],
        ]);
        abort_if(
            str_starts_with(strtolower($setting->key), 'payments.')
                || str_starts_with(strtolower($data['key']), 'payments.'),
            422,
            'Payment gateway credentials must be managed in Payment settings.',
        );

        $changes = ['key' => $data['key']];
        if (($data['value'] ?? '') !== '') {
            $changes['value'] = $data['value'];
        }

        DB::transaction(function () use ($request, $setting, $changes): void {
            $setting->update($changes);
            $this->audit($request, 'setting.updated', $setting, array_keys($changes));
        });

        return back()->with('status', 'Setting updated.');
    }

    public function updatePaymentGateways(Request $request, PaymentGatewaySettings $gatewaySettings): RedirectResponse
    {
        $data = $request->validate([
            'mpesa_environment' => ['required', Rule::in(['sandbox', 'production'])],
            'mpesa_consumer_key' => ['nullable', 'string', 'max:1000'],
            'mpesa_consumer_secret' => ['nullable', 'string', 'max:1000'],
            'mpesa_shortcode' => ['nullable', 'string', 'max:100'],
            'mpesa_passkey' => ['nullable', 'string', 'max:1000'],
            'mpesa_callback_url' => ['nullable', 'url', 'max:2000'],
            'clear_mpesa_consumer_key' => ['nullable', 'boolean'],
            'clear_mpesa_consumer_secret' => ['nullable', 'boolean'],
            'clear_mpesa_shortcode' => ['nullable', 'boolean'],
            'clear_mpesa_passkey' => ['nullable', 'boolean'],
            'clear_mpesa_callback_url' => ['nullable', 'boolean'],
            'pesapal_environment' => ['required', Rule::in(['sandbox', 'production'])],
            'pesapal_consumer_key' => ['nullable', 'string', 'max:1000'],
            'pesapal_consumer_secret' => ['nullable', 'string', 'max:1000'],
            'pesapal_ipn_id' => ['nullable', 'string', 'max:255'],
            'clear_pesapal_consumer_key' => ['nullable', 'boolean'],
            'clear_pesapal_consumer_secret' => ['nullable', 'boolean'],
            'clear_pesapal_ipn_id' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($request, $data, $gatewaySettings): void {
            $mpesaValues = [
                'environment' => $data['mpesa_environment'],
                'consumer_key' => $data['mpesa_consumer_key'] ?? '',
                'consumer_secret' => $data['mpesa_consumer_secret'] ?? '',
                'shortcode' => $data['mpesa_shortcode'] ?? '',
                'passkey' => $data['mpesa_passkey'] ?? '',
                'callback_url' => $data['mpesa_callback_url'] ?? '',
            ];
            $pesapalValues = [
                'environment' => $data['pesapal_environment'],
                'consumer_key' => $data['pesapal_consumer_key'] ?? '',
                'consumer_secret' => $data['pesapal_consumer_secret'] ?? '',
                'ipn_id' => $data['pesapal_ipn_id'] ?? '',
            ];
            $mpesaClear = [
                'consumer_key' => (bool) ($data['clear_mpesa_consumer_key'] ?? false),
                'consumer_secret' => (bool) ($data['clear_mpesa_consumer_secret'] ?? false),
                'shortcode' => (bool) ($data['clear_mpesa_shortcode'] ?? false),
                'passkey' => (bool) ($data['clear_mpesa_passkey'] ?? false),
                'callback_url' => (bool) ($data['clear_mpesa_callback_url'] ?? false),
            ];
            $pesapalClear = [
                'consumer_key' => (bool) ($data['clear_pesapal_consumer_key'] ?? false),
                'consumer_secret' => (bool) ($data['clear_pesapal_consumer_secret'] ?? false),
                'ipn_id' => (bool) ($data['clear_pesapal_ipn_id'] ?? false),
            ];
            $changed = [
                'mpesa' => $gatewaySettings->save('mpesa', $mpesaValues, $mpesaClear),
                'pesapal' => $gatewaySettings->save('pesapal', $pesapalValues, $pesapalClear),
            ];

            $request->user()->adminLogs()->create([
                'action' => 'payment_gateways.updated',
                'entity' => 'PaymentGatewaySettings',
                'entity_id' => null,
                'metadata' => [
                    'fields' => [
                        'mpesa' => $changed['mpesa'],
                        'mpesa.environment.updated' => true,
                        'pesapal' => $changed['pesapal'],
                        'pesapal.environment.updated' => true,
                    ],
                ],
            ]);
        });

        return back()->with('status', 'Payment gateway settings saved. Secret values are encrypted and are never displayed.');
    }

    private function audit(Request $request, string $action, Setting $setting, array $fields): void
    {
        $request->user()->adminLogs()->create([
            'action' => $action,
            'entity' => 'Setting',
            'entity_id' => $setting->id,
            'metadata' => ['key' => $setting->key, 'fields' => $fields],
        ]);
    }
}
