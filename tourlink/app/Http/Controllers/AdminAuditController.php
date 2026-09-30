<?php

namespace App\Http\Controllers;

use App\Models\AdminLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminAuditController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'entity' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
        ]);

        $logs = AdminLog::query()
            ->with('admin:id,name,email')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('entity_id', 'like', '%'.$search.'%')
                    ->orWhere('entity', 'like', '%'.$search.'%')
                    ->orWhere('action', 'like', '%'.$search.'%');
            }))
            ->when($filters['entity'] ?? null, fn (Builder $query, string $entity): Builder => $query->where('entity', $entity))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action): Builder => $query->where('action', $action))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $entities = AdminLog::query()->distinct()->orderBy('entity')->pluck('entity');
        $actions = AdminLog::query()->distinct()->orderBy('action')->pluck('action');

        return view('admin.audit.index', compact('logs', 'filters', 'entities', 'actions'));
    }
}
