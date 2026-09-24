<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Member;
use App\Models\Package;
use App\Models\MemberSubscription;
use App\Models\PaymentMethod;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MemberDashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $member = null;

        if ($user->customer && $user->customer->member) {
            $member = $user->customer->member;
        }

        if (!$member) {
            $customer = Customer::where('user_id', $user->id)->orWhere('email', $user->email)->first();
            if ($customer) {
                if (!$customer->user_id) $customer->update(['user_id' => $user->id]);
                $member = $customer->member;
            }
        }

        if (!$member && isset($user->member_id)) {
            $member = Member::where('member_code', $user->member_id)->first();
        }

        if (!$member) {
            Auth::logout();
            return redirect()->route('login')->withErrors(['identifier' => 'No active member profile linked.']);
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

        return view('member.overview', compact('member', 'packages', 'paymentMethods'));
    }
// Redeem 500 Points for 1 Month Free Access
    public function redeemPoints(Request $request)
    {
        $user = Auth::user();
        $member = $user->customer ? $user->customer->member : null;

        if (!$member || $member->reward_points < 500) {
            return back()->withErrors(['error' => 'You need at least 500 reward points to redeem 1 Month of Free Access.']);
        }

        DB::transaction(function () use ($member, $user) {
            // 1. Deduct 500 points
            $member->decrement('reward_points', 500);

            // 2. Fetch or create Monthly Package
            $pkg = Package::firstOrCreate(
                ['plan_type' => 'monthly'],
                ['package_code' => 'PKG-MTH-750', 'name' => 'Monthly Free Reward Pass', 'price' => 750, 'duration_in_days' => 30]
            );

            // 3. Extend or activate subscription by 30 days
            $currentSub = $member->latestSubscription;
            $newStart = ($currentSub && $currentSub->status === 'active' && Carbon::parse($currentSub->end_time)->isFuture())
                ? Carbon::parse($currentSub->end_time)
                : Carbon::now();

            $newEnd = (clone $newStart)->addDays(30);

            MemberSubscription::create([
                'member_id' => $member->id,
                'package_id' => $pkg->id,
                'start_time' => $newStart,
                'end_time' => $newEnd,
                'status' => 'active'
            ]);

            $member->update(['membership_status' => 'active']);

            AuditLog::create([
                'log_code' => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id' => $user->id,
                'action' => "Reward Points Redeemed: 500 Points for 1 Month Free Gym Access",
                'entity_type' => Member::class,
                'entity_id' => $member->id,
                'validity_period' => '1 Month (Free Reward)',
                'performed_by' => 'Member Self-Redeem'
            ]);
        });

        return back()->with('success', '🎉 Congratulations! You have successfully redeemed 500 points for 1 Month of Free Gym Access!');
    }
}