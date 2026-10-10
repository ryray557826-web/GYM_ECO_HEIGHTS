<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Budget;
use App\Models\AuditLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FinancialController extends Controller
{
    public function index()
    {
        $totalRevenue  = (float) Payment::where('status', 'verified')->sum('amount');
        $totalExpenses = (float) Expense::sum('amount');
        $netIncome     = $totalRevenue - $totalExpenses;

        $expenses = Expense::with('budget')->latest('expense_date')->paginate(15);
        $budgets  = Budget::all();

        // 6-Month dynamic calculation (Completely flat/0 when no records exist)
        $chartLabels = [];
        $chartRevenue = [];
        $chartExpenses = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth()->toDateString();
            $end   = $month->copy()->endOfMonth()->toDateString();

            $chartLabels[] = $month->format('M');
            $chartRevenue[] = (float) Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start . ' 00:00:00', $end . ' 23:59:59'])
                ->sum('amount');
            $chartExpenses[] = (float) Expense::whereBetween('expense_date', [$start, $end])->sum('amount');
        }

        return view('owner.finance', compact(
            'totalRevenue',
            'totalExpenses',
            'netIncome',
            'expenses',
            'budgets',
            'chartLabels',
            'chartRevenue',
            'chartExpenses'
        ));
    }

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

            $expense = Expense::create([
                'expense_code' => $code,
                'budget_id'    => $request->budget_id,
                'category'     => $request->category,
                'description'  => $request->description,
                'amount'       => $request->amount,
                'expense_date' => $request->expense_date,
            ]);

            if ($request->budget_id) {
                Budget::where('id', $request->budget_id)->increment('spent_amount', $request->amount);
            }

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
        });

        return back()->with('success', 'Expense recorded successfully.');
    }
}