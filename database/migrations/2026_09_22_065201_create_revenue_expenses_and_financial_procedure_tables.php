<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. Budgets Table (Document Page 17-18)
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 30)->unique(); // BUD-2026-EQP
            $table->string('category_name', 100);
            $table->year('fiscal_year')->default(2026);
            $table->decimal('allocated_amount', 12, 2);
            $table->decimal('spent_amount', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 2. Revenues Table (Accounting ledger recognizing revenue against budgets)
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->string('revenue_code', 30)->unique(); // REV-001
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('revenue_date');
            $table->timestamps();
        });

        // 3. Expenses Table (Operational, damages, and equipment maintenance costs)
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code', 30)->unique(); // EXP-001
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->foreignId('maintenance_id')->nullable()->constrained('equipment_maintenances')->nullOnDelete();
            $table->enum('category', ['Equipment', 'Maintenance', 'Damages', 'Utilities', 'Supplies'])->default('Maintenance');
            $table->string('description', 255);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->timestamps();
        });

        // 4. Stored Procedure: CalculateProfitLossSummary
        // Calculates total revenue, total expenses, net income, and profit/loss ratio
        DB::unprepared("DROP PROCEDURE IF EXISTS CalculateProfitLossSummary;");
        DB::unprepared("
            CREATE PROCEDURE CalculateProfitLossSummary(
                IN p_start_date DATE,
                IN p_end_date DATE,
                OUT p_total_revenue DECIMAL(12,2),
                OUT p_total_expenses DECIMAL(12,2),
                OUT p_net_income DECIMAL(12,2),
                OUT p_profit_loss_ratio DECIMAL(10,4)
            )
            BEGIN
                -- 1. Compute Total Verified Revenue
                SELECT COALESCE(SUM(amount), 0.00)
                INTO p_total_revenue
                FROM revenues
                WHERE revenue_date BETWEEN p_start_date AND p_end_date;

                -- 2. Compute Total Expenses
                SELECT COALESCE(SUM(amount), 0.00)
                INTO p_total_expenses
                FROM expenses
                WHERE expense_date BETWEEN p_start_date AND p_end_date;

                -- 3. Compute Net Income
                SET p_net_income = p_total_revenue - p_total_expenses;

                -- 4. Compute Profit/Loss Ratio: (Net Income / Revenue)
                IF p_total_revenue > 0 THEN
                    SET p_profit_loss_ratio = ROUND((p_net_income / p_total_revenue), 4);
                ELSE
                    SET p_profit_loss_ratio = 0.0000;
                END IF;
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS CalculateProfitLossSummary;");
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('revenues');
        Schema::dropIfExists('budgets');
    }
};