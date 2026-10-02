@extends('layouts.admin')

@section('title', 'Referrals | Havenedge Tourlink Admin')

@section('content')
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <header class="border-b border-slate-200 pb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Growth</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Referrals &amp; rewards</h1>
            <p class="mt-1 text-sm text-slate-600">A referral becomes successful once the referred account verifies its email and an administrator approves it.</p>
        </header>

        @if(session('status'))<p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>@endif
        @if($errors->any())<div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950"><p class="font-bold">The action could not be completed.</p>@foreach($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach</div>@endif

        <section class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6" aria-label="Referral totals">
            @foreach([['Total',$statistics['total']],['Pending',$statistics['pending']],['Approved',$statistics['approved']],['Rewarded',$statistics['rewarded']],['Rewards to review',$statistics['pending_rewards']],['Rewards paid',$settings['reward_currency'].' '.number_format($statistics['rewards_paid'])]] as [$label,$value])
                <div class="rounded border border-slate-200 bg-white p-4"><p class="text-[11px] font-bold uppercase tracking-wide text-slate-500">{{ $label }}</p><p class="mt-1 text-lg font-black text-slate-950">{{ $value }}</p></div>
            @endforeach
        </section>

        <section class="mt-8 border-b border-slate-200 pb-8" aria-labelledby="referral-settings-heading">
            <h2 id="referral-settings-heading" class="text-lg font-extrabold text-slate-950">Programme settings</h2>
            <form method="POST" action="{{ route('admin.referrals.settings') }}" class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@csrf @method('PUT')
                <label class="flex min-h-11 items-center gap-2 rounded border border-slate-300 px-3 text-sm font-semibold text-slate-800"><input type="checkbox" name="enabled" value="1" @checked($settings['enabled']) class="size-4"> Programme enabled</label>
                <label class="flex min-h-11 items-center gap-2 rounded border border-slate-300 px-3 text-sm font-semibold text-slate-800"><input type="checkbox" name="reward_enabled" value="1" @checked($settings['reward_enabled']) class="size-4"> Rewards enabled</label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Reward trigger
                    <select name="reward_trigger" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal">
                        @foreach($rewardTriggers as $trigger)<option value="{{ $trigger }}" @selected($settings['reward_trigger']->value === $trigger)>{{ str($trigger)->replace('_',' ')->title() }}</option>@endforeach
                    </select>
                </label>
                <label class="flex min-h-11 items-center gap-2 rounded border border-slate-300 px-3 text-sm font-semibold text-slate-800"><input type="checkbox" name="reward_requires_approval" value="1" @checked($settings['reward_requires_approval']) class="size-4"> Review rewards before paying</label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Reward amount<input type="number" name="reward_amount" min="0" max="10000000" step="1" value="{{ $settings['reward_amount'] }}" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Currency<input name="reward_currency" maxlength="3" value="{{ $settings['reward_currency'] }}" class="min-h-11 rounded border border-slate-300 px-3 uppercase text-sm font-normal"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Code prefix<input name="code_prefix" maxlength="4" pattern="[A-Za-z]*" value="{{ $settings['code_prefix'] }}" class="min-h-11 rounded border border-slate-300 px-3 uppercase text-sm font-normal"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Code length<input type="number" name="code_length" min="4" max="12" value="{{ $settings['code_length'] }}" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                <button class="self-end rounded bg-emerald-800 px-4 py-3 text-sm font-bold text-white hover:bg-emerald-900">Save settings</button>
            </form>
        </section>

        <section class="mt-8" aria-labelledby="referral-list-heading">
            <h2 id="referral-list-heading" class="border-b border-slate-200 pb-3 text-lg font-extrabold text-slate-950">Referral records</h2>

            <form method="GET" action="{{ route('admin.referrals.index') }}" class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Search<input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email or code" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Status
                    <select name="status" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal"><option value="">All statuses</option>@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ str($status->value)->replace('_',' ')->title() }}</option>@endforeach</select>
                </label>
                <label class="grid gap-1 text-xs font-semibold text-slate-700">Reward status
                    <select name="reward_status" class="min-h-11 rounded border border-slate-300 px-3 text-sm font-normal"><option value="">All reward states</option>@foreach($rewardStatuses as $status)<option value="{{ $status->value }}" @selected(($filters['reward_status'] ?? '') === $status->value)>{{ str($status->value)->title() }}</option>@endforeach</select>
                </label>
                <button class="self-end rounded border border-slate-300 px-4 py-3 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            </form>

            <div class="mt-6 space-y-4">
                @forelse($referrals as $referral)
                    <article class="rounded border border-slate-200 bg-white p-5">
                        <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start">
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $referral->referral_code }}</p>
                                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-950">{{ $referral->referrer?->name ?? 'Unknown referrer' }}</p>
                                        <p class="truncate text-xs text-slate-600">{{ $referral->referrer?->email }}</p>
                                        <p class="mt-1 text-[11px] font-semibold text-slate-500">Referrer</p>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-slate-950">{{ $referral->referredUser?->name ?? 'Withheld' }}</p>
                                        <p class="truncate text-xs text-slate-600">{{ $referral->referredUser?->email }}</p>
                                        <p class="mt-1 text-[11px] font-semibold text-slate-500">Referred {{ $referral->created_at?->format('M j, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-800">{{ str($referral->status->value)->replace('_',' ')->title() }}</span>
                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-800">Reward: {{ str($referral->reward_status->value)->title() }}</span>
                            </div>
                        </div>

                        <dl class="mt-4 grid gap-2 text-xs text-slate-600 sm:grid-cols-3">
                            <div><dt class="font-bold uppercase tracking-wide text-slate-500">Verified</dt><dd class="mt-0.5">{{ $referral->verified_at?->format('M j, Y H:i') ?? 'Not yet' }}</dd></div>
                            <div><dt class="font-bold uppercase tracking-wide text-slate-500">Approved</dt><dd class="mt-0.5">{{ $referral->approved_at?->format('M j, Y H:i') ?? 'Not yet' }}</dd></div>
                            <div><dt class="font-bold uppercase tracking-wide text-slate-500">Reward amount</dt><dd class="mt-0.5 font-semibold text-slate-800">{{ $referral->reward_amount > 0 ? $settings['reward_currency'].' '.number_format($referral->reward_amount) : '—' }} @if($referral->rewarded_at)(paid {{ $referral->rewarded_at->format('M j, Y') }})@endif</dd></div>
                        </dl>

                        @if($referral->reward_status === \App\ReferralRewardStatus::Pending)
                            <div class="mt-4 flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('admin.referrals.reward', $referral) }}">@csrf
                                    <input type="hidden" name="reward_status" value="APPROVED">
                                    <button class="min-h-11 rounded bg-emerald-800 px-4 text-sm font-bold text-white hover:bg-emerald-900">Release reward</button>
                                </form>
                                <form method="POST" action="{{ route('admin.referrals.reward', $referral) }}">@csrf
                                    <input type="hidden" name="reward_status" value="REJECTED">
                                    <button class="min-h-11 rounded border border-red-300 px-4 text-sm font-bold text-red-800 hover:bg-red-50">Reject reward</button>
                                </form>
                            </div>
                        @endif
                    </article>
                @empty
                    <p class="rounded border border-slate-200 bg-white px-5 py-12 text-center text-sm text-slate-600">No referrals match these filters.</p>
                @endforelse
            </div>

            <div class="mt-6">{{ $referrals->links() }}</div>
        </section>
    </div>
@endsection
