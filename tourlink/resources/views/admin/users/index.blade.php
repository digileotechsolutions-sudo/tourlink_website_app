@extends('layouts.admin')

@section('title', 'Manage users | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950">Manage users</h1>
                <p class="mt-2 text-sm text-slate-600">Review traveler, operator, and vehicle owner profiles and account access. New accounts must verify their email with an OTP before approval.</p>
            </div>
            <span class="text-sm text-slate-500">{{ number_format($users->total()) }} accounts</span>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <div role="alert" class="mt-5 border-l-4 border-red-700 bg-red-50 px-4 py-3 text-sm text-red-950">
                <p class="font-bold">The account change could not be saved.</p>
                @foreach ($errors->all() as $error)<p class="mt-1">{{ $error }}</p>@endforeach
            </div>
        @endif

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_180px_190px_180px_auto_auto]">
            <label class="sr-only" for="user-search">Search users</label>
            <input id="user-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name, email or phone" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="user-role-filter">Role</label>
            <select id="user-role-filter" name="role" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All roles</option>
                @foreach (\App\Role::cases() as $role)
                    <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ str($role->value)->replace('_', ' ')->title() }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="user-approval-filter">Approval status</label>
            <select id="user-approval-filter" name="approval_status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All approvals</option>
                @foreach (\App\AccountApprovalStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['approval_status'] ?? '') === $status->value)>{{ str($status->value)->title() }}</option>
                @endforeach
            </select>
            <label class="sr-only" for="user-account-filter">Account status</label>
            <select id="user-account-filter" name="account_status" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All account states</option>
                @foreach (\App\AccountStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['account_status'] ?? '') === $status->value)>{{ str($status->value)->title() }}</option>
                @endforeach
            </select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            <a href="{{ route('admin.users.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600 hover:text-slate-950">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500">
                    <tr><th class="py-3 pr-4">Person</th><th class="py-3 pr-4">Role</th><th class="py-3 pr-4">Approval</th><th class="py-3 pr-4">Account</th><th class="py-3 pr-4">Trust</th><th class="py-3 pr-4">Activity</th><th class="py-3">Manage</th></tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="py-4 pr-4">
                                <p class="font-bold text-slate-950">{{ $user->name }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $user->email }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $user->phone ?? 'No phone' }}</p>
                                <p class="mt-2 flex gap-2 text-[10px] font-semibold text-slate-500"><span>{{ $user->email_verified_at ? 'Email verified' : 'Email unverified' }}</span><span>·</span><span>{{ $user->phone_verified_at ? 'Phone verified' : 'Phone unverified' }}</span></p>
                            </td>
                            <td class="py-4 pr-4">{{ str($user->role->value)->replace('_', ' ')->title() }}</td>
                            <td class="py-4 pr-4">{{ str($user->approval_status->value)->title() }}</td>
                            <td class="py-4 pr-4">{{ str($user->account_status->value)->title() }}</td>
                            <td class="py-4 pr-4">{{ str($user->verification_level->value)->title() }}</td>
                            <td class="py-4 pr-4 text-xs text-slate-600"><span>{{ $user->bookings_count }} bookings</span><br><span>{{ $user->trips_count }} trips</span><br><span>{{ $user->vehicles_count }} vehicles</span></td>
                            <td class="py-4">
                                <details>
                                    <summary class="cursor-pointer font-bold text-emerald-800 hover:underline">Edit profile and access</summary>
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="mt-4 grid w-[min(900px,calc(100vw-3rem))] gap-3 border-t border-slate-200 pt-4 sm:grid-cols-2 lg:grid-cols-4">
                                        @csrf @method('PUT')
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Name<input name="name" value="{{ $user->name }}" required minlength="2" maxlength="80" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Email<input type="email" name="email" value="{{ $user->email }}" required maxlength="255" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Phone<input name="phone" value="{{ $user->phone }}" minlength="8" maxlength="30" class="min-h-10 rounded border border-slate-300 px-3 text-sm font-normal"></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Role<select name="role" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm font-normal">@foreach (\App\Role::cases() as $role)<option value="{{ $role->value }}" @selected($user->role === $role)>{{ str($role->value)->replace('_', ' ')->title() }}</option>@endforeach</select></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Approval<select name="approval_status" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm font-normal">@foreach (\App\AccountApprovalStatus::cases() as $status)<option value="{{ $status->value }}" @selected($user->approval_status === $status)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Account status<select name="account_status" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm font-normal">@foreach (\App\AccountStatus::cases() as $status)<option value="{{ $status->value }}" @selected($user->account_status === $status)>{{ str($status->value)->title() }}</option>@endforeach</select></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700">Trust level<select name="verification_level" required class="min-h-10 rounded border border-slate-300 bg-white px-3 text-sm font-normal">@foreach (\App\VerificationLevel::cases() as $level)<option value="{{ $level->value }}" @selected($user->verification_level === $level)>{{ str($level->value)->title() }}</option>@endforeach</select></label>
                                        <label class="grid gap-1 text-xs font-semibold text-slate-700 lg:col-span-4">Approval note<textarea name="approval_note" maxlength="2000" rows="2" class="rounded border border-slate-300 px-3 py-2 text-sm font-normal">{{ $user->approval_note }}</textarea></label>
                                        <p class="text-xs text-slate-500 lg:col-span-4">Changing an email or phone number resets its verification status. Approval notifications are sent when configured.</p>
                                        <button class="justify-self-start rounded bg-emerald-800 px-4 py-2.5 text-sm font-bold text-white lg:col-span-4">Save changes</button>
                                    </form>
                                            @if ($user->role !== \App\Role::Admin)
                                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Anonymize this account? Linked booking records will be retained.')" class="mt-3">
                                            @csrf @method('DELETE')
                                            <button class="rounded border border-red-300 px-4 py-2.5 text-sm font-bold text-red-700">Anonymize account</button>
                                        </form>
                                    @endif
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-10 text-center text-sm text-slate-600">No accounts match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $users->links() }}</div>
    </div>
@endsection
