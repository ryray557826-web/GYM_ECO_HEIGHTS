<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\MemberSubscription;
use App\Models\Package;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberPaymentController extends Controller
{
    public function submitPayment(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'reference_number' => 'required|string|max:100'
        ]);

        $user = Auth::user();
        $customer = $user->customer;
        $member = $customer ? $customer->member : null;

        if (!$member) {
            return back()->withErrors(['error' => 'No active member profile linked to your account.']);
        }

        $package = Package::findOrFail($request->package_id);

        DB::transaction(function () use ($request, $customer, $member, $package) {
            $sub = MemberSubscription::create([
                'member_id' => $member->id,
                'package_id' => $package->id,
                'start_time' => Carbon::now(),
                'end_time' => Carbon::now()->addDays($package->duration_in_days),
                'status' => 'pending'
            ]);

            $code = 'REQ-' . Carbon::now()->format('YmdHis');
            Payment::create([
                'payment_code' => $code,
                'customer_id' => $customer->id,
                'member_id' => $member->id,
                'member_subscription_id' => $sub->id,
                'payment_method_id' => $request->payment_method_id,
                'amount' => $package->price,
                'payment_type' => 'monthly_subscription',
                'reference_number' => $request->reference_number,
                'status' => 'pending'
            ]);
        });

        return back()->with('success', 'Payment request submitted! Awaiting owner verification.');
    }
}