<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Payment;
use App\Models\Attendance;
use App\Models\Expense;
use App\Models\AuditLog;
use App\Models\Announcement;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    /**
     * Display the Owner Check-in & Operations Dashboard
     */
    public function index()
    {
        $startOfYear = Carbon::now()->startOfYear()->toDateString();
        $endOfYear = Carbon::now()->endOfYear()->toDateString();

        // 1. Calculate Real Financial Metrics (Stored Procedure with Eloquent Fallback)
        try {
            DB::statement("CALL CalculateProfitLossSummary('{$startOfYear}', '{$endOfYear}', @rev, @exp, @net, @ratio)");
            $spResult = DB::select("SELECT @rev AS total_revenue, @exp AS total_expenses, @net AS net_income, @ratio AS profit_loss_ratio")[0];

            $totalRevenue  = (float) ($spResult->total_revenue ?? 0);
            $totalExpenses = (float) ($spResult->total_expenses ?? 0);
            $netIncome     = (float) ($spResult->net_income ?? 0);
            $ratio         = (float) ($spResult->profit_loss_ratio ?? 0);
        } catch (\Exception $e) {
            // Natural database calculations without hardcoded demo offsets
            $totalRevenue  = (float) Payment::where('status', 'verified')->sum('amount');
            $totalExpenses = (float) Expense::sum('amount');
            $netIncome     = $totalRevenue - $totalExpenses;
            $ratio         = $totalRevenue > 0 ? round($netIncome / $totalRevenue, 4) : 0.0;
        }

        // 2. Member & Pending Approvals Metrics
        $activeMembersCount = Member::where('membership_status', 'active')->count();
        $pendingPaymentsCount = Payment::where('status', 'pending')->count();
        $pendingMembersCount  = Member::where('membership_status', 'pending')->count();
        $pendingCount = $pendingPaymentsCount + $pendingMembersCount;

        // 3. Attendance Entries for Today
        $todayDate = Carbon::today()->toDateString();
        $todayEntries = Attendance::where('attendance_date', $todayDate)
            ->with(['customer', 'member'])
            ->orderBy('check_in_time', 'desc')
            ->get();

        // 4. Recent Walk-In / Per-Session Entries
        $recentPerSession = Attendance::where('entry_type', 'per_session')
            ->with(['customer', 'member', 'payment'])
            ->latest('attendance_date')
            ->latest('check_in_time')
            ->take(10)
            ->get();

        return view('owner.checkin', compact(
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'ratio',
            'activeMembersCount',
            'pendingCount',
            'todayEntries',
            'recentPerSession'
        ));
    }

    /**
     * Display the Full Tabular Attendance Log Sheet
     */
    public function attendanceLogs(Request $request)
    {
        $query = Attendance::with(['customer', 'member.latestSubscription.package'])
            ->latest('attendance_date')
            ->latest('check_in_time');

        if ($request->filled('date')) {
            $query->where('attendance_date', $request->date);
        }

        $attendances = $query->paginate(20)->withQueryString();

        return view('owner.attendance', compact('attendances'));
    }

    /**
     * Display the System Audit Trail Logs
     */
    public function auditLogs()
    {
        $auditLogs = AuditLog::with('user')->latest()->paginate(25);
        return view('owner.audit-logs', compact('auditLogs'));
    }

    /**
     * Publish a Gym Announcement (Saved locally and synced to Firebase)
     */
    public function storeAnnouncement(Request $request)
    {
        $request->validate([
            'title'   => 'required|string|max:150',
            'message' => 'required|string',
            'badge'   => 'required|in:IMPORTANT,SCHEDULE,PROMO,INFO',
        ]);

        $announcement = Announcement::create([
            'title'             => $request->title,
            'message'           => $request->message,
            'badge'             => $request->badge,
            'posted_date'       => Carbon::today(),
            'posted_by_user_id' => auth()->id(),
        ]);

        // Optional cloud sync to Firebase Firestore
        try {
            $firebase = new FirebaseService();
            $firebase->addDocument('announcements', [
                'title'       => $announcement->title,
                'badge'       => $announcement->badge,
                'message'     => $announcement->message,
                'posted_date' => $announcement->posted_date->toDateString(),
            ]);
        } catch (\Exception $e) {
            // Fail gracefully if running offline
        }

        if (class_exists(AuditLog::class)) {
            AuditLog::create([
                'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                'user_id'         => auth()->id(),
                'action'          => "Gym Announcement Published: {$announcement->title}",
                'validity_period' => 'Live',
                'performed_by'    => 'Owner',
            ]);
        }

        return back()->with('success', 'Announcement published and attached live to member dashboards.');
    }

    /**
     * Delete an Announcement
     */
    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();
        return back()->with('success', 'Announcement removed.');
    }
}