<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Package;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\Auth;

class MemberDashboardController extends Controller
{
    public function index()
    {
        $customer = Auth::user()->customer;
        $member = $customer ? $customer->member : null;

        if (!$member) {
            return redirect()->route('login')->withErrors(['identifier' => 'No active member profile linked to this user.']);
        }

        $member->load([
            'customer',
            'latestSubscription.package',
            'payments.method',
            'attendances.gymNote',
            'gymNotes'
        ]);

        $packages = Package::where('is_active', true)->get();
        $paymentMethods = PaymentMethod::all();

return view('member.overview', compact('member', 'packages', 'paymentMethods'));    }
}