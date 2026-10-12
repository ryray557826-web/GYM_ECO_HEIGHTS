<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Revenue;
use App\Models\Budget;
use App\Models\AuditLog;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialController extends Controller
{
    /**
     * Display the Revenue & Expenses Ledger with 6-Month Chart Analytics
     */
    public function index()
    {
        // 1. Lifetime Verified Revenue & Expenses Calculations
        $totalRevenue  = (float) Payment::where('status', 'verified')->sum('amount');
        $totalExpenses = (float) Expense::sum('amount');
        $netIncome     = $totalRevenue - $totalExpenses;

        // 2. Dynamic 6-Month Chart Data (Actual database transactions; flat at 0 if none)
        $chartLabels = [];
        $chartRevenue = [];
        $chartExpenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end   = $month->copy()->endOfMonth()->toDateString();

            $chartLabels[] = $month->format('M');
            
            // Sum all verified revenue for this specific calendar month
            $chartRevenue[] = (float) Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->sum('amount');

            // Sum all expenses incurred in this specific calendar month
            $chartExpenses[] = (float) Expense::whereBetween('expense_date', [$start, $end])
                ->sum('amount');
        }

        // 3. Itemized Ledgers
        $revenues = Payment::where('status', 'verified')
            ->with(['customer', 'member', 'method'])
            ->latest()
            ->paginate(10, ['*'], 'revenue_page');

        $expenses = Expense::with('budget')
            ->latest('expense_date')
            ->paginate(10, ['*'], 'expense_page');

        $budgets = Budget::all();

        return view('owner.finance', compact(
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'chartLabels',
            'chartRevenue',
            'chartExpenses',
            'revenues',
            'expenses',
            'budgets'
        ));
    }

    /**
     * Record an Expense and sync with Budget & Firebase
     */
    public function storeExpense(Request $request)
    {
        $request->validate([
            'category'     => 'required|in:Equipment,Maintenance,Damages,Utilities,Supplies',
            'description'  => 'required|string|max:255',
            'amount'       => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'budget_id'    => 'nullable|exists:budgets,id',
        ]);

        DB::transaction(function () use ($request) {
            $count = Expense::count() + 1;
            $code = 'EXP-' . str_pad($count, 3, '0', STR_PAD_LEFT);

            // 1. Record Expense
            $expense = Expense::create([
                'expense_code' => $code,
                'budget_id'    => $request->budget_id,
                'category'     => $request->category,
                'description'  => $request->description,
                'amount'       => $request->amount,
                'expense_date' => $request->expense_date,
            ]);

            // 2. Increment Spent Amount on Budget (if assigned)
            if ($request->budget_id) {
                Budget::where('id', $request->budget_id)->increment('spent_amount', $request->amount);
            }

            // 3. System Audit Trail
            if (class_exists(AuditLog::class)) {
                $auditData = [
                    'log_code'        => 'AUD-' . str_pad(AuditLog::count() + 1, 3, '0', STR_PAD_LEFT),
                    'user_id'         => auth()->id(),
                    'action'          => "Expense Logged [{$code}]: {$request->description} (₱" . number_format($request->amount, 2) . ")",
                    'validity_period' => 'Fiscal Disbursement',
                    'performed_by'    => 'Owner',
                ];

                if (Schema::hasColumn('audit_logs', 'entity_type')) {
                    $auditData['entity_type'] = Expense::class;
                    $auditData['entity_id']   = $expense->id;
                }

                AuditLog::create($auditData);
            }

            // 4. Cloud Sync to Firebase Firestore
            try {
                $firebase = new FirebaseService();
                $firebase->syncExpense([
                    'expense_code' => $code,
                    'category'     => $request->category,
                    'description'  => $request->description,
                    'amount'       => (float) $request->amount,
                    'expense_date' => $request->expense_date,
                ]);
            } catch (\Exception $e) {
                // Fail gracefully if offline
            }
        });

        return back()->with('success', 'Expense recorded, budget updated, and synced to cloud ledger.');
    }
}