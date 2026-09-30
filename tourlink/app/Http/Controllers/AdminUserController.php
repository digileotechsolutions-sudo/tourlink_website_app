<?php

namespace App\Http\Controllers;

use App\AccountApprovalStatus;
use App\AccountStatus;
use App\Models\AdminLog;
use App\Models\User;
use App\Role;
use App\Services\Verification\OtpDeliveryService;
use App\VerificationLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class AdminUserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'role' => ['nullable', Rule::enum(Role::class)],
            'approval_status' => ['nullable', Rule::enum(AccountApprovalStatus::class)],
            'account_status' => ['nullable', Rule::enum(AccountStatus::class)],
        ]);

        $users = User::query()
            ->where('email', 'not like', 'deleted-%@tourlink.invalid')
            ->withCount(['bookings', 'trips', 'vehicles'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            }))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role): Builder => $query->where('role', $role))
            ->when($filters['approval_status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('approval_status', $status))
            ->when($filters['account_status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('account_status', $status))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'filters'));
    }

    public function update(Request $request, User $user, OtpDeliveryService $delivery): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'min:8', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'role' => ['required', Rule::enum(Role::class)],
            'approval_status' => ['required', Rule::enum(AccountApprovalStatus::class)],
            'account_status' => ['required', Rule::enum(AccountStatus::class)],
            'verification_level' => ['required', Rule::enum(VerificationLevel::class)],
            'approval_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $approvalChanged = $user->approval_status->value !== $data['approval_status'];

        $isDisablingAdmin = $data['role'] !== Role::Admin->value
            || $data['account_status'] !== AccountStatus::Active->value
            || $data['approval_status'] !== AccountApprovalStatus::Approved->value;

        if ($user->is($request->user()) && $isDisablingAdmin) {
            throw ValidationException::withMessages(['account_status' => 'You cannot remove your own administrator access.']);
        }

        if ($user->role === Role::Admin && $isDisablingAdmin
            && User::query()->where('role', Role::Admin)->where('account_status', AccountStatus::Active)->where('approval_status', AccountApprovalStatus::Approved)->count() <= 1) {
            throw ValidationException::withMessages(['role' => 'At least one active, approved administrator must remain.']);
        }

        $changes = [];
        DB::transaction(function () use ($request, $user, $data, &$changes): void {
            $attributes = $data;
            $roleChanged = $user->role->value !== $data['role'];

            if ($user->email !== $data['email']) {
                $attributes['email_verified_at'] = null;
            }
            if ($user->phone !== $data['phone']) {
                $attributes['phone_verified_at'] = null;
            }
            if ($user->approval_status->value !== $data['approval_status']) {
                $attributes['approval_reviewed_at'] = now();
            }

            $user->forceFill($attributes)->save();
            $changes = array_keys($attributes);

            if ($roleChanged) {
                match (Role::from($data['role'])) {
                    Role::Traveler => $user->travelerProfile()->firstOrCreate([], ['preferred_currency' => 'KES']),
                    Role::Operator => $user->operatorProfile()->firstOrCreate([], [
                        'company_name' => $user->name,
                        'slug' => Str::slug($user->name).'-'.Str::lower(Str::random(8)),
                    ]),
                    Role::VehicleOwner => $user->vehicleOwnerProfile()->firstOrCreate([], ['business_name' => $user->name]),
                    Role::Admin => null,
                };
            }

            AdminLog::query()->create([
                'admin_id' => $request->user()->id,
                'action' => isset($data['approval_status']) ? 'user.updated' : 'user.updated',
                'entity' => 'User',
                'entity_id' => $user->id,
                'metadata' => ['fields' => $changes],
            ]);
        });

        if ($approvalChanged && in_array($data['approval_status'], [AccountApprovalStatus::Approved->value, AccountApprovalStatus::Rejected->value], true)) {
            try {
                $delivery->sendAccountApproval($user, $data['approval_status'] === AccountApprovalStatus::Approved->value, $data['approval_note'] ?? null);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return back()->with('status', "{$user->name}'s account was updated.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'You cannot remove your own admin account.']);
        }

        if ($user->role === Role::Admin) {
            return back()->withErrors(['user' => 'Admin accounts cannot be removed.']);
        }

        DB::transaction(function () use ($request, $user): void {
            $user->forceFill([
                'name' => 'Deleted user',
                'email' => 'deleted-'.$user->id.'@tourlink.invalid',
                'phone' => null,
                'avatar_url' => null,
                'account_status' => AccountStatus::Inactive,
                'approval_status' => AccountApprovalStatus::Rejected,
                'approval_note' => 'Account removed by an administrator.',
            ])->save();

            AdminLog::query()->create([
                'admin_id' => $request->user()->id,
                'action' => 'user.anonymized',
                'entity' => 'User',
                'entity_id' => $user->id,
                'metadata' => ['mode' => 'anonymized'],
            ]);
        });

        return back()->with('status', 'Account details anonymized.');
    }
}
