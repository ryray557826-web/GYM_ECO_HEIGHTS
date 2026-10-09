<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Budget;
use App\Models\AuditLog;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialController extends Controller
{
    /**
     * Display the Revenue & Expenses Ledger
     */
    public function index()
    {
        // 1. Calculate Live Authentic Ledger Balances
        $totalRevenue  = (float) Payment::where('status', 'verified')->sum('amount');
        $totalExpenses = (float) Expense::sum('amount');
        $netIncome     = $totalRevenue - $totalExpenses;

        // 2. Fetch Itemized Records and Budget Allocations
        $expenses = Expense::with('budget')->latest('expense_date')->paginate(15);
        $budgets  = Budget::all();

        return view('owner.finance', compact(
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'expenses',
            'budgets'
        ));
    }

    /**
     * Record a Gym Disbursement / Expense
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

            // 1. Record the Expense
            $expense = Expense::create([
                'expense_code' => $code,
                'budget_id'    => $request->budget_id,
                'category'     => $request->category,
                'description'  => $request->description,
                'amount'       => $request->amount,
                'expense_date' => $request->expense_date,
            ]);

            // 2. Increment Spent Amount in Budget (if assigned)
            if ($request->budget_id) {
                Budget::where('id', $request->budget_id)->increment('spent_amount', $request->amount);
            }

            // 3. Log to System Audit Trail
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
                    $auditData['entity_id'] = $expense->id;
                }

                AuditLog::create($auditData);
            }

            // 4. Optional Cloud Sync to Firebase Firestore
            try {
                $firebase = new FirebaseService();
                $firebase->addDocument('expenses', [
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

        return back()->with('success', 'Expense recorded and budget balance updated.');
    }
}