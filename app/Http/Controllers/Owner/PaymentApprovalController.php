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
use Illuminate\Support\Facades\Schema;

class PaymentApprovalController extends Controller
{
    public function index()
    {
        $members = Member::with('customer')->get();
        $packages = Package::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::all();
        $pendingPayments = Payment::where('status', 'pending')->with(['customer', 'member'])->latest()->get();
        $payments = Payment::with(['customer', 'member', 'method'])->latest()->paginate(20);

        return view('owner.payments', compact('members', 'packages', 'paymentMethods', 'pendingPayments', 'payments'));
    }

    public function verify(Payment $payment)
    {
        DB::transaction(function () use ($payment) {
            $payment->update([
                'status'      => 'verified',
                'verified_at' => Carbon::now(),
                'verified_by' => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'amount'       => $payment->amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            $member = $payment->member ?? ($payment->customer ? $payment->customer->member : null);
            $daysToAdd = 30;
            $pointsAwarded = 15;

            if ($payment->member_subscription_id && $payment->subscription && $payment->subscription->package) {
                $pkg = $payment->subscription->package;
                $daysToAdd = $pkg->duration_in_days;

                if ($daysToAdd >= 365 || $payment->amount >= 7000) {
                    $pointsAwarded = 180;
                } elseif ($daysToAdd >= 90 || $payment->amount >= 2000) {
                    $pointsAwarded = 45;
                } else {
                    $pointsAwarded = 15;
                }
            } elseif ($payment->payment_type === 'per_session' || $payment->amount <= 50) {
                $pointsAwarded = 3;
                $daysToAdd = 1;
            }

            if ($member) {
                $member->increment('reward_points', $pointsAwarded);
                $member->update(['membership_status' => 'active']);
            }

            if ($payment->member_subscription_id && $payment->subscription) {
                $payment->subscription->update([
                    'start_time' => Carbon::now(),
                    'end_time'   => Carbon::now()->addDays($daysToAdd),
                    'status'     => 'active'
                ]);
            }

            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Payment #{$payment->payment_code} Verified (₱{$payment->amount}) & +{$pointsAwarded} PTS credited",
                    'validity_period' => "{$daysToAdd} Days",
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', "Payment {$payment->payment_code} verified and points credited.");
    }

    public function reject(Payment $payment)
    {
        $payment->update(['status' => 'rejected']);
        return back()->with('warning', "Payment {$payment->payment_code} rejected.");
    }

    public function storeManual(Request $request)
    {
        $request->validate([
            'member_id'         => 'required|exists:members,id',
            'package_id'        => 'required|exists:packages,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number'  => 'nullable|string'
        ]);

        DB::transaction(function () use ($request) {
            $member = Member::with('customer')->findOrFail($request->member_id);
            $package = Package::findOrFail($request->package_id);
            $paymentCode = 'PAY-' . Carbon::now()->format('YmdHis');

            $isDaily = $package->plan_type === 'daily';
            $amount = $package->price; // FIXED AMOUNT

            $payment = Payment::create([
                'payment_code'      => $paymentCode,
                'customer_id'       => $member->customer_id,
                'member_id'         => $member->id,
                'payment_method_id' => $request->payment_method_id,
                'amount'            => $amount,
                'payment_type'      => $isDaily ? 'per_session' : 'monthly_subscription',
                'reference_number'  => $request->reference_number,
                'status'            => 'verified',
                'verified_at'       => Carbon::now(),
                'verified_by'       => auth()->id()
            ]);

            Revenue::create([
                'revenue_code' => 'REV-' . $payment->id,
                'payment_id'   => $payment->id,
                'amount'       => $amount,
                'revenue_date' => Carbon::today()->toDateString()
            ]);

            // Points allocation
            $pointsAwarded = 15;
            if ($package->duration_in_days >= 365) $pointsAwarded = 180;
            elseif ($package->duration_in_days >= 90) $pointsAwarded = 45;
            elseif ($isDaily) $pointsAwarded = 3;

            $member->increment('reward_points', $pointsAwarded);
            $member->update(['membership_status' => 'active']);

            $sub = MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $package->id,
                'start_time' => Carbon::now(),
                'end_time'   => Carbon::now()->addDays($package->duration_in_days),
                'status'     => 'active'
            ]);

            $payment->update(['member_subscription_id' => $sub->id]);

            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Manual Payment for {$package->name} (₱{$amount}) with +{$pointsAwarded} PTS credited",
                    'validity_period' => "{$package->duration_in_days} Days",
                    'performed_by'    => 'Owner'
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Payment::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $payment->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', 'Manual payment logged with fixed rate, points credited, and pass activated.');
    }
}