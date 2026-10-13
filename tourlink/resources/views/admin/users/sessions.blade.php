@extends('layouts.admin')

@section('title', 'Active sessions | Havenedge Tourlink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="flex flex-wrap items-end justify-between gap-4 border-b border-slate-200 pb-6">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
                <h1 class="mt-2 text-3xl font-black text-slate-950">Active account sessions</h1>
                <p class="mt-2 text-sm text-slate-600">Each account can have one active browser or API session. Terminating a session immediately blocks its next authenticated request.</p>
            </div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex min-h-11 items-center rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Manage users</a>
        </header>

        @if (session('status'))
            <p role="status" class="mt-5 border-l-4 border-emerald-700 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-950">{{ session('status') }}</p>
        @endif

        <div class="mt-6 overflow-x-auto rounded border border-slate-200 bg-white">
            <table class="w-full min-w-[850px] text-left text-sm">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-bold uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Account</th>
                        <th class="px-4 py-3">Client</th>
                        <th class="px-4 py-3">IP address</th>
                        <th class="px-4 py-3">Last activity</th>
                        <th class="px-4 py-3">Expires</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sessions as $session)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-950">{{ $session->user?->name ?? 'Deleted account' }}</p>
                                <p class="mt-1 text-xs text-slate-600">{{ $session->user?->email }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ str($session->user?->role?->value ?? 'UNKNOWN')->replace('_', ' ')->title() }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <p class="font-semibold">{{ $session->session_type === 'token' ? 'API token' : 'Web browser' }}</p>
                                <p class="mt-1 max-w-xs break-words text-xs text-slate-500">{{ $session->user_agent ?: 'Client not reported' }}</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $session->ip_address ?: 'Unavailable' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $session->last_activity_at->format('Y-m-d H:i T') }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $session->expires_at->format('Y-m-d H:i T') }}</td>
                            <td class="px-4 py-4">
                                @if ($session->user)
                                    <form method="POST" action="{{ route('admin.sessions.destroy', $session->user) }}" onsubmit="return confirm('Terminate this account session?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="min-h-10 rounded bg-red-700 px-3 text-xs font-bold text-white hover:bg-red-800">Terminate</button>
                                    </form>
                                @else
                                    <span class="text-xs text-slate-500">No account</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-10 text-center text-slate-600">There are no active sessions.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $sessions->links() }}</div>
    </div>
@endsection
