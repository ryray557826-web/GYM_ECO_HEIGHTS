<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MemberManagementController extends Controller
{
    public function index()
    {
        $members = Member::with(['customer', 'latestSubscription.package'])->get();
        return view('owner.members', compact('members'));
    }

    public function update(Request $request, Member $member)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'contact_number' => 'nullable|string|max:30',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'address' => 'nullable|string|max:255',
            'membership_status' => 'required|in:active,expired,pending,suspended',
            'end_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $member) {
            $customer = $member->customer;

            // 1. Update Customer Record
            if ($customer) {
                $customer->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'contact_number' => $request->contact_number,
                    'emergency_contact_phone' => $request->emergency_contact_phone,
                    'address' => $request->address,
                ]);
            }

            // 2. Update Member Status
            $memberData = [];
            if (Schema::hasColumn('members', 'membership_status')) {
                $memberData['membership_status'] = $request->membership_status;
            }
            if (Schema::hasColumn('members', 'status')) {
                $memberData['status'] = $request->membership_status;
            }
            $member->update($memberData);

            // 3. Update active subscription end date if specified
            if ($request->filled('end_date') && $member->latestSubscription) {
                $sub = $member->latestSubscription;
                $subData = [
                    'status' => Carbon::parse($request->end_date)->isPast() ? 'expired' : 'active',
                ];

                if (Schema::hasColumn('member_subscriptions', 'end_time')) {
                    $subData['end_time'] = Carbon::parse($request->end_date)->endOfDay();
                }
                if (Schema::hasColumn('member_subscriptions', 'end_date')) {
                    $subData['end_date'] = Carbon::parse($request->end_date)->toDateString();
                }

                $sub->update($subData);
            }

            // 4. Log to Audit Trail
            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code' => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id' => auth()->id(),
                    'action' => "Member details updated for {$customer->full_name}",
                    'validity_period' => $request->filled('end_date') ? "Until {$request->end_date}" : null,
                    'performed_by' => 'Owner',
                ];
                if (Schema::hasColumn('audit_logs', 'entity_type')) $auditData['entity_type'] = Member::class;
                if (Schema::hasColumn('audit_logs', 'entity_id')) $auditData['entity_id'] = $member->id;

                AuditLog::create($auditData);
            }
        });

        return back()->with('success', "Member {$member->member_code} details updated successfully.");
    }
}