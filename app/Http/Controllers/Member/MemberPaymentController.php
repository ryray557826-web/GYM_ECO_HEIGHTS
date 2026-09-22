<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;

class MemberDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // 1. Try relationship via user -> customer -> member
        $member = null;
        if ($user->customer && $user->customer->member) {
            $member = $user->customer->member;
        }

        // 2. Resilient fallback: search customer table by user_id or email
        if (!$member) {
            $customer = Customer::where('user_id', $user->id)
                                ->orWhere('email', $user->email)
                                ->first();

            if ($customer) {
                if (!$customer->user_id) {
                    $customer->update(['user_id' => $user->id]);
                }
                $member = $customer->member;
            }
        }

        // 3. Fallback: search member directly by member_id
        if (!$member && isset($user->member_id)) {
            $member = Member::where('member_code', $user->member_id)->first();
        }

        if (!$member) {
            Auth::logout();
            return redirect()->route('login')->withErrors([
                'identifier' => 'No active member profile linked to this account.'
            ]);
        }

        // Load all relations for the member dashboard
        $member->load([
            'customer',
            'latestSubscription.package',
            'payments.method',
            'attendances.gymNote',
            'gymNotes'
        ]);

        $packages = Package::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::all();

        // Returns member.overview (resolving View not found)
        return view('member.overview', compact('member', 'packages', 'paymentMethods'));
    }
}