@php
    $layout = match (auth()->user()->role) {
        \App\Role::Admin => 'layouts.admin',
        \App\Role::Operator => 'layouts.operator',
        \App\Role::VehicleOwner => 'layouts.vehicle-owner',
        default => 'layouts.traveler',
    };
@endphp
@extends($layout)

@section('title', 'Referrals | Havenedge Tourlink')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 p-4 sm:p-8">
    <div>
        <p class="text-xs font-black uppercase tracking-widest text-emerald-800">Referral programme</p>
        <h1 class="mt-2 text-3xl font-black">Invite friends, earn rewards</h1>
        <p class="mt-2 text-slate-600">Share your personal link. A referral only counts once the person verifies their email and an administrator approves their account.</p>
    </div>

    @if (session('status'))
        <p role="status" class="border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
    @endif
    @if ($errors->any())
        <div role="alert" class="border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">
            <p class="font-bold">We could not complete that action.</p>
            @foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
        </div>
    @endif

    @if ($referredBy)
        <p class="border-l-4 border-sky-600 bg-sky-50 px-4 py-3 text-sm text-sky-950">You joined through a referral from <strong>{{ $referredBy['name'] }}</strong>. Thank you for being here.</p>
    @endif

    <section class="grid gap-4 sm:grid-cols-2">
        <div class="rounded border border-slate-200 bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">My referral code</p>
            <p class="mt-2 font-mono text-2xl font-black tracking-widest" data-referral-code>{{ $referralCode }}</p>
            <button type="button" data-copy="{{ $referralCode }}" data-copied-label="Code copied" class="mt-4 min-h-11 w-full rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Copy code</button>
        </div>

        <div class="rounded border border-slate-200 bg-white p-5">
            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">My referral link</p>
            <p class="mt-2 break-all text-sm text-slate-700" data-referral-link>{{ $referralLink }}</p>
            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                <button type="button" data-copy="{{ $referralLink }}" data-copied-label="Link copied" class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Copy link</button>
                <button type="button" data-share data-share-title="Join me on Havenedge Tourlink" data-share-text="I signed up to Havenedge Tourlink with my referral link. Use mine to join:" class="min-h-11 rounded bg-emerald-800 px-4 text-sm font-bold text-white hover:bg-emerald-900">Share referral link</button>
            </div>
            <p class="mt-2 text-xs text-slate-500" data-share-status role="status"></p>
        </div>
    </section>

    @if ($rewards)
        <p class="rounded border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <strong>{{ $rewards['currency'] }} {{ number_format($rewards['amount']) }}</strong> per successful referral, issued once the account is
            {{ $rewards['trigger'] === \App\ReferralStatus::Verified ? 'email verified' : 'approved by an administrator' }}@if ($rewards['requires_approval']) and an administrator releases the reward @endif.
        </p>
    @else
        <p class="rounded border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">Referral tracking is active. Rewards are not being issued at the moment.</p>
    @endif

    <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ([['Total', $statistics['total']], ['Pending', $statistics['pending']], ['Verified', $statistics['verified']], ['Approved', $statistics['approved']], ['Rewarded', $statistics['successful']], ['Rewards', number_format($statistics['rewards'])]] as [$label, $value])
            <div class="rounded border border-slate-200 bg-white p-4">
                <p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p>
                <p class="mt-1 text-xl font-black">{{ $value }}</p>
            </div>
        @endforeach
    </section>

    <section class="rounded border border-slate-200 bg-white">
        <div class="border-b border-slate-200 px-5 py-4">
            <h2 class="text-lg font-black">Referral history</h2>
            <p class="mt-1 text-sm text-slate-600">A referral becomes successful only after email verification and administrator approval.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                    <tr><th class="px-5 py-3">Person</th><th class="px-5 py-3">Joined</th><th class="px-5 py-3">Status</th><th class="px-5 py-3">Reward</th></tr>
                </thead>
                <tbody>
                    @forelse ($history as $referral)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-950">{{ $referral->referredUser?->name ?? 'Withheld' }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $referral->referredUser?->email }}</p>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-600">{{ $referral->created_at?->format('d M Y') }}</td>
                            <td class="px-5 py-4">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold">{{ $statusLabels[$referral->status->value] }}</span>
                                @if ($referral->reward_status !== \App\ReferralRewardStatus::None)
                                    <p class="mt-1 text-[11px] font-semibold text-slate-500">Reward {{ str($referral->reward_status->value)->title() }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-800">
                                {{ $referral->reward_status === \App\ReferralRewardStatus::Paid ? ($rewards['currency'] ?? 'KES').' '.number_format($referral->reward_amount) : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-12 text-center text-sm text-slate-600">No referrals yet. Share your link to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($history, 'links'))
            <div class="border-t border-slate-200 px-5 py-4">{{ $history->links() }}</div>
        @endif
    </section>
</div>
@endsection
