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
            return redirect()->route('login')->withErrors(['identifier' => 'No active member profile linked to this user.']);
        }

        // Load all relations for the member dashboard
        $member->load([
            'customer',
            'latestSubscription.package',
            'payments.method',
            'attendances.gymNote',
            'gymNotes'
        ]);

        // =========================================================================
        // AUTOMATIC REWARD POINTS RECONCILIATION
        // Points schedule: Daily = 3 pts, Monthly = 15 pts, Quarterly = 45 pts, Yearly = 180 pts
        // =========================================================================
        $earnedPoints = 0;
        foreach ($member->payments->where('status', 'verified') as $pay) {
            if ($pay->payment_type === 'monthly_subscription' || $pay->amount >= 700) {
                $days = ($pay->subscription && $pay->subscription->package) ? $pay->subscription->package->duration_in_days : 30;
                if ($days >= 365 || $pay->amount >= 7000) {
                    $earnedPoints += 180; // Yearly
                } elseif ($days >= 90 || $pay->amount >= 2000) {
                    $earnedPoints += 45;  // Quarterly
                } else {
                    $earnedPoints += 15;  // Monthly
                }
            } else {
                $earnedPoints += 3;       // Daily per-session
            }
        }

        // Deduct points if any 500-pt reward was already redeemed
        $redeemedCount = AuditLog::where('user_id', $user->id)
            ->where('action', 'like', '%Reward Points Redeemed%')
            ->count();

        $calculatedPoints = max(0, $earnedPoints - ($redeemedCount * 500));

        // Sync points in database if different
        if ($member->reward_points !== $calculatedPoints) {
            $member->update(['reward_points' => $calculatedPoints]);
            $member->reward_points = $calculatedPoints;
        }

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
            $member->decrement('reward_points', 500);

            $pkg = Package::firstOrCreate(
                ['plan_type' => 'monthly'],
                ['package_code' => 'PKG-MTH-750', 'name' => 'Monthly Free Reward Pass', 'price' => 750, 'duration_in_days' => 30]
            );

            $currentSub = $member->latestSubscription;
            $newStart = ($currentSub && $currentSub->status === 'active' && Carbon::parse($currentSub->end_time)->isFuture())
                ? Carbon::parse($currentSub->end_time)
                : Carbon::now();

            $newEnd = (clone $newStart)->addDays(30);

            MemberSubscription::create([
                'member_id'  => $member->id,
                'package_id' => $pkg->id,
                'start_time' => $newStart,
                'end_time'   => $newEnd,
                'status'     => 'active'
            ]);

            $member->update(['membership_status' => 'active']);

            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => $user->id,
                'action'          => "Reward Points Redeemed: 500 Points for 1 Month Free Gym Access",
                'entity_type'     => Member::class,
                'entity_id'       => $member->id,
                'validity_period' => '1 Month (Free Reward)',
                'performed_by'    => 'Member Self-Redeem'
            ]);
        });

        return back()->with('success', '🎉 Congratulations! You have successfully redeemed 500 points for 1 Month of Free Gym Access!');
    }
}