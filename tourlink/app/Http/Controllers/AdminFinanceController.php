<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\Models\User;
use App\PaymentMethod;
use App\PaymentStatus;
use App\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminFinanceController extends Controller
{
    public function payments(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'staff' => ['nullable', 'string', 'max:36', Rule::exists('users', 'id')->where('role', Role::Admin->value)],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (($filters['from'] ?? null) && ($filters['to'] ?? null) && $filters['to'] < $filters['from']) {
            throw ValidationException::withMessages(['to' => 'The end date must be on or after the start date.']);
        }
        $query = Payment::query()
            ->with(['booking.traveler:id,name,email', 'booking.trip:id,name', 'booking.vehicle:id,name', 'refunds', 'booking.commission', 'receiver:id,name'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('merchant_reference', 'like', '%'.$search.'%')
                    ->orWhere('transaction_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('booking', fn (Builder $booking): Builder => $booking->where('reference', 'like', '%'.$search.'%')->orWhereHas('traveler', fn (Builder $user): Builder => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->when($filters['method'] ?? null, fn (Builder $query, string $method): Builder => $query->where('payment_method', $method))
            ->when($filters['staff'] ?? null, fn (Builder $query, string $staff): Builder => $query->where('received_by', $staff))
            ->when($filters['from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('created_at', '<=', $date));
        $metrics = [
            'cash_collected' => (int) (clone $query)->where('payment_method', PaymentMethod::Cash->value)->where('status', PaymentStatus::Successful->value)->sum('amount'),
            'bank_collected' => (int) (clone $query)->where('payment_method', PaymentMethod::BankTransfer->value)->where('status', PaymentStatus::Successful->value)->sum('amount'),
            'pending_bank_count' => (int) (clone $query)->where('payment_method', PaymentMethod::BankTransfer->value)->where('status', PaymentStatus::Pending->value)->count(),
        ];
        $records = $query
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', [
            'section' => 'payments',
            'records' => $records,
            'filters' => $filters,
            'metrics' => $metrics,
            'staffOptions' => User::query()->where('role', Role::Admin->value)->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function refunds(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120']]);
        $records = Refund::query()
            ->with(['payment.booking.traveler:id,name,email'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('reason', 'like', '%'.$search.'%')->orWhereHas('payment', fn (Builder $payment): Builder => $payment->where('merchant_reference', 'like', '%'.$search.'%')->orWhereHas('booking', fn (Builder $booking): Builder => $booking->where('reference', 'like', '%'.$search.'%'))))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', ['section' => 'refunds', 'records' => $records, 'filters' => $filters]);
    }

    public function commissions(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120']]);
        $records = Commission::query()
            ->with(['booking.traveler:id,name,email', 'booking.trip:id,name', 'booking.vehicle:id,name'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('payout_status', 'like', '%'.$search.'%')->orWhereHas('booking', fn (Builder $booking): Builder => $booking->where('reference', 'like', '%'.$search.'%')->orWhereHas('traveler', fn (Builder $user): Builder => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%'))))
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', ['section' => 'commissions', 'records' => $records, 'filters' => $filters]);
    }

    public function payouts(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:120']]);
        $records = Payout::query()
            ->join('users', 'users.id', '=', 'payouts.provider_id')
            ->select('payouts.*', 'users.name AS provider_name', 'users.email AS provider_email', 'users.role AS provider_role')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('payouts.reference', 'like', '%'.$search.'%')->orWhere('users.name', 'like', '%'.$search.'%')->orWhere('users.email', 'like', '%'.$search.'%'))
            ->latest('payouts.created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', ['section' => 'payouts', 'records' => $records, 'filters' => $filters]);
    }
}
