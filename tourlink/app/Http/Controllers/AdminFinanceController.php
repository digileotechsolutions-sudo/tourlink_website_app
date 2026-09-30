<?php

namespace App\Http\Controllers;

use App\Models\Commission;
use App\Models\Payment;
use App\Models\Payout;
use App\Models\Refund;
use App\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminFinanceController extends Controller
{
    public function payments(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);
        $records = Payment::query()
            ->with(['booking.traveler:id,name,email', 'booking.trip:id,name', 'booking.vehicle:id,name', 'refunds', 'booking.commission'])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                $query->where('merchant_reference', 'like', '%'.$search.'%')
                    ->orWhere('transaction_reference', 'like', '%'.$search.'%')
                    ->orWhereHas('booking', fn (Builder $booking): Builder => $booking->where('reference', 'like', '%'.$search.'%')->orWhereHas('traveler', fn (Builder $user): Builder => $user->where('name', 'like', '%'.$search.'%')->orWhere('email', 'like', '%'.$search.'%')));
            }))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status): Builder => $query->where('status', $status))
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.finance.index', ['section' => 'payments', 'records' => $records, 'filters' => $filters]);
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
