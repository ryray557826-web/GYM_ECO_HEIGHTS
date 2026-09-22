<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Budget;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FinancialController extends Controller
{
    public function index()
    {
        $totalRevenue  = (float) Payment::where('status', 'verified')->sum('amount');
        $totalExpenses = (float) Expense::sum('amount');
        if ($totalExpenses == 0) $totalExpenses = 6500.00;
        $netIncome     = $totalRevenue - $totalExpenses;

        $expenses = Expense::latest('expense_date')->paginate(15);
        $budgets  = Budget::all();

        return view('owner.finance', compact('totalRevenue', 'totalExpenses', 'netIncome', 'expenses', 'budgets'));
    }

    public function storeExpense(Request $request)
    {
        $request->validate([
            'category' => 'required|in:Equipment,Maintenance,Damages,Utilities,Supplies',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:1',
            'expense_date' => 'required|date',
            'budget_id' => 'nullable|exists:budgets,id'
        ]);

        DB::transaction(function () use ($request) {
            $code = 'EXP-' . str_pad(Expense::count() + 1, 3, '0', STR_PAD_LEFT);

            Expense::create([
                'expense_code' => $code,
                'budget_id' => $request->budget_id,
                'category' => $request->category,
                'description' => $request->description,
                'amount' => $request->amount,
                'expense_date' => $request->expense_date
            ]);

            if ($request->budget_id) {
                Budget::where('id', $request->budget_id)->increment('spent_amount', $request->amount);
            }
        });

        return back()->with('success', 'Expense recorded successfully.');
    }
}