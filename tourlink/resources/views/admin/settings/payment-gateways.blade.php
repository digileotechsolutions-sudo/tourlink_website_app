<section class="mt-7 border-b border-slate-200 pb-7" aria-labelledby="payment-gateway-settings-heading">
    <h2 id="payment-gateway-settings-heading" class="text-xl font-extrabold text-slate-950">Payment gateway credentials</h2>
    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Enter Daraja and Pesapal credentials here. Values are encrypted using the application key, never displayed again, and are only changed when you enter a replacement. Keep your production secrets restricted to administrators.</p>
    <form method="POST" action="{{ route('admin.settings.payment-gateways.update') }}" class="mt-5 space-y-6">
        @csrf
        @method('PUT')

        <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5">
            <legend class="px-2 text-base font-extrabold text-slate-950">M-Pesa Daraja</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Environment
                    <select name="mpesa_environment" required class="min-h-11 rounded border border-slate-300 bg-white px-3">
                        <option value="sandbox" @selected(old('mpesa_environment', $paymentGatewayEnvironments['mpesa']) === 'sandbox')>Sandbox</option>
                        <option value="production" @selected(old('mpesa_environment', $paymentGatewayEnvironments['mpesa']) === 'production')>Production</option>
                    </select>
                </label>
                @foreach([
                    'consumer_key' => ['Consumer key', true],
                    'consumer_secret' => ['Consumer secret', true],
                    'shortcode' => ['Business shortcode', false],
                    'passkey' => ['Lipa Na M-Pesa passkey', true],
                    'callback_url' => ['Callback URL (optional)', false],
                ] as $field => [$label, $secret])
                    <div class="grid content-start gap-1">
                        <label for="mpesa-{{ $field }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                        <input id="mpesa-{{ $field }}" name="mpesa_{{ $field }}" type="{{ $secret ? 'password' : 'text' }}" maxlength="{{ $field === 'callback_url' ? 2000 : 1000 }}" autocomplete="new-password" placeholder="{{ $paymentGatewayState['mpesa'][$field]['configured'] ? ($paymentGatewayState['mpesa'][$field]['stored'] ? 'Saved encrypted; leave blank to keep' : 'Provided by server environment; leave blank to keep') : 'Not configured' }}" class="min-h-11 w-full rounded border border-slate-300 px-3 text-sm" @if($field === 'callback_url') inputmode="url" @endif>
                        <p class="text-xs {{ $paymentGatewayState['mpesa'][$field]['configured'] ? 'text-emerald-800' : 'text-amber-800' }}">{{ $paymentGatewayState['mpesa'][$field]['configured'] ? ($paymentGatewayState['mpesa'][$field]['stored'] ? 'Encrypted value stored' : 'Using server environment value') : 'Not configured' }}</p>
                        @if($paymentGatewayState['mpesa'][$field]['stored'])
                            <label class="flex min-h-8 items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" name="clear_mpesa_{{ $field }}" value="1" class="size-4 rounded border-slate-300">Remove stored value and use environment fallback</label>
                        @endif
                    </div>
                @endforeach
            </div>
        </fieldset>

        <fieldset class="rounded-xl border border-slate-200 p-4 sm:p-5">
            <legend class="px-2 text-base font-extrabold text-slate-950">Pesapal hosted card checkout</legend>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="grid gap-1 text-sm font-semibold text-slate-700">Environment
                    <select name="pesapal_environment" required class="min-h-11 rounded border border-slate-300 bg-white px-3">
                        <option value="sandbox" @selected(old('pesapal_environment', $paymentGatewayEnvironments['pesapal']) === 'sandbox')>Sandbox</option>
                        <option value="production" @selected(old('pesapal_environment', $paymentGatewayEnvironments['pesapal']) === 'production')>Production</option>
                    </select>
                </label>
                @foreach([
                    'consumer_key' => ['Consumer key', true],
                    'consumer_secret' => ['Consumer secret', true],
                    'ipn_id' => ['Registered IPN ID', false],
                ] as $field => [$label, $secret])
                    <div class="grid content-start gap-1">
                        <label for="pesapal-{{ $field }}" class="text-sm font-semibold text-slate-700">{{ $label }}</label>
                        <input id="pesapal-{{ $field }}" name="pesapal_{{ $field }}" type="{{ $secret ? 'password' : 'text' }}" maxlength="1000" autocomplete="new-password" placeholder="{{ $paymentGatewayState['pesapal'][$field]['configured'] ? ($paymentGatewayState['pesapal'][$field]['stored'] ? 'Saved encrypted; leave blank to keep' : 'Provided by server environment; leave blank to keep') : 'Not configured' }}" class="min-h-11 w-full rounded border border-slate-300 px-3 text-sm">
                        <p class="text-xs {{ $paymentGatewayState['pesapal'][$field]['configured'] ? 'text-emerald-800' : 'text-amber-800' }}">{{ $paymentGatewayState['pesapal'][$field]['configured'] ? ($paymentGatewayState['pesapal'][$field]['stored'] ? 'Encrypted value stored' : 'Using server environment value') : 'Not configured' }}</p>
                        @if($paymentGatewayState['pesapal'][$field]['stored'])
                            <label class="flex min-h-8 items-center gap-2 text-xs font-medium text-slate-600"><input type="checkbox" name="clear_pesapal_{{ $field }}" value="1" class="size-4 rounded border-slate-300">Remove stored value and use environment fallback</label>
                        @endif
                    </div>
                @endforeach
            </div>
        </fieldset>

        <button class="min-h-11 rounded bg-emerald-800 px-5 text-sm font-bold text-white hover:bg-emerald-900">Save payment credentials</button>
    </form>
</section>
