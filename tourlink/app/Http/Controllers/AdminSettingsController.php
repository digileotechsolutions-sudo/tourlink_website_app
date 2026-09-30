<?php

namespace App\Http\Controllers;

use App\Models\Setting;
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
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('key', 'like', '%'.$search.'%'))
            ->orderBy('key')
            ->paginate(30)
            ->withQueryString();

        return view('admin.settings.index', compact('settings', 'filters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:255', 'regex:/^[A-Z][A-Z0-9_.-]*$/', 'unique:settings,key'],
            'value' => ['required', 'string', 'max:10000'],
        ]);

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
