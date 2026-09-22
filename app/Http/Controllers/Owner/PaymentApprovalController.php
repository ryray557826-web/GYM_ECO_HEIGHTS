<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Member;
use App\Models\Package;
use App\Models\PaymentMethod;
use App\Models\MemberSubscription;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentApprovalController extends Controller
{
    public function index()
    {
        $members = Member::with('customer')->get();
        $paymentMethods = PaymentMethod::all();
        $pendingPayments = Payment::where('status', 'pending')->with(['customer', 'member'])->latest()->get();
        $payments = Payment::with(['customer', 'member', 'method'])->latest()->paginate(20);

        return view('owner.payments', compact('members', 'paymentMethods', 'pendingPayments', 'payments'));
    }

    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id' => $payment->id,
                'amount' => $payment->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            if ($payment->member_subscription_id) {
                $sub = $payment->subscription;
                $sub->update([
                    'start_time' => Carbon::now(),
                    'end_time' => Carbon::now()->addDays(30),
                    'status' => 'active'
                ]);

                if ($payment->member) {
                    $payment->member->update(['membership_status' => 'active']);
                }
            }

            AuditLog::create([
                'log_code' => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id' => auth()->id(),
                'action' => "Payment #{$payment->payment_code} Approved (₱{$payment->amount})",
                'entity_type' => Payment::class,
                'entity_id' => $payment->id,
                'validity_period' => '1 Month',
                'performed_by' => 'Owner'
            ]);
        });

        return back()->with('success', "Payment {$payment->payment_code} verified.");
    }

    public function reject(Payment $payment)
    {
        $payment->update(['status' => 'rejected']);
        return back()->with('warning', "Payment {$payment->payment_code} rejected.");
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'plan_type' => 'required|in:monthly,per_session',
            'amount' => 'required|numeric|min:1',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');

            $payment = Payment::create([
                'payment_code' => $paymentCode,
                'customer_id' => $member->customer_id,
                'member_id' => $member->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $request->amount,
                'payment_type' => $request->plan_type === 'monthly' ? 'monthly_subscription' : 'per_session',
                'reference_number' => $request->reference_number,
                'status' => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id' => $payment->id,
                'amount' => $request->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            if ($request->plan_type === 'monthly') {
                $pkg = Package::firstOrCreate(
                    ['plan_type' => 'monthly'],
                    ['package_code' => 'PKG-MTH-750', 'name' => 'Monthly Pass', 'price' => 750, 'duration_in_days' => 30]
                );

                $sub = MemberSubscription::create([
                    'member_id' => $member->id,
                    'package_id' => $pkg->id,
                    'start_time' => Carbon::now(),
                    'end_time' => Carbon::now()->addDays(30),
                    'status' => 'active'
                ]);

                $payment->update(['member_subscription_id' => $sub->id]);
                $member->update(['membership_status' => 'active']);
            }
        });

        return back()->with('success', 'Manual payment logged and activated.');
    }
}