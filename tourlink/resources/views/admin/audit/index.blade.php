@extends('layouts.admin')

@section('title', 'Audit log | TourLink')

@section('content')
    <div class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        <header class="border-b border-slate-200 pb-6">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800">Administration</p>
            <h1 class="mt-2 text-3xl font-black text-slate-950">Audit log</h1>
            <p class="mt-2 text-sm text-slate-600">Review recorded administrative changes.</p>
        </header>

        <form method="GET" class="mt-6 grid gap-3 border-b border-slate-200 pb-6 sm:grid-cols-2 lg:grid-cols-[minmax(220px,1fr)_200px_240px_auto_auto]">
            <label class="sr-only" for="audit-search">Search log</label>
            <input id="audit-search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Entity, action or record ID" class="min-h-11 rounded border border-slate-300 px-3 text-sm">
            <label class="sr-only" for="audit-entity">Entity</label>
            <select id="audit-entity" name="entity" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All entities</option>
                @foreach ($entities as $entity)<option value="{{ $entity }}" @selected(($filters['entity'] ?? '') === $entity)>{{ $entity }}</option>@endforeach
            </select>
            <label class="sr-only" for="audit-action">Action</label>
            <select id="audit-action" name="action" class="min-h-11 rounded border border-slate-300 bg-white px-3 text-sm">
                <option value="">All actions</option>
                @foreach ($actions as $action)<option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ str($action)->replace('.', ' ')->replace('_', ' ')->title() }}</option>@endforeach
            </select>
            <button class="min-h-11 rounded border border-slate-300 px-4 text-sm font-bold text-slate-800 hover:bg-slate-50">Filter</button>
            <a href="{{ route('admin.audit.index') }}" class="grid min-h-11 place-items-center text-sm font-semibold text-slate-600 hover:text-slate-950">Clear</a>
        </form>

        <div class="mt-2 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="border-b border-slate-200 text-xs font-bold uppercase text-slate-500"><tr><th class="py-3 pr-4">When</th><th class="py-3 pr-4">Administrator</th><th class="py-3 pr-4">Action</th><th class="py-3 pr-4">Entity</th><th class="py-3 pr-4">Record</th><th class="py-3">Details</th></tr></thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-slate-100 align-top">
                            <td class="whitespace-nowrap py-4 pr-4 text-xs text-slate-600">{{ $log->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="py-4 pr-4">{{ $log->admin?->name ?? 'Unknown admin' }}</td>
                            <td class="py-4 pr-4 font-semibold text-slate-900">{{ str($log->action)->replace('.', ' ')->replace('_', ' ')->title() }}</td>
                            <td class="py-4 pr-4">{{ $log->entity }}</td>
                            <td class="max-w-48 truncate py-4 pr-4 font-mono text-xs" title="{{ $log->entity_id }}">{{ $log->entity_id ?? '—' }}</td>
                            <td class="py-4"><details><summary class="cursor-pointer text-xs font-bold text-emerald-800 hover:underline">View details</summary><pre class="mt-2 max-w-xl overflow-x-auto whitespace-pre-wrap rounded bg-slate-50 p-3 text-xs text-slate-700">{{ json_encode($log->metadata ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-10 text-center text-sm text-slate-600">No audit entries match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $logs->links() }}</div>
    </div>
@endsection
