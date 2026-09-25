<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Only run stored procedures if MySQL is the active driver
        if (DB::getDriverName() === 'mysql') {
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
                    SELECT COALESCE(SUM(amount), 0.00) INTO p_total_revenue
                    FROM revenues WHERE revenue_date BETWEEN p_start_date AND p_end_date;

                    SELECT COALESCE(SUM(amount), 0.00) INTO p_total_expenses
                    FROM expenses WHERE expense_date BETWEEN p_start_date AND p_end_date;

                    SET p_net_income = p_total_revenue - p_total_expenses;

                    IF p_total_revenue > 0 THEN
                        SET p_profit_loss_ratio = ROUND((p_net_income / p_total_revenue), 4);
                    ELSE
                        SET p_profit_loss_ratio = 0.0000;
                    END IF;
                END;
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::unprepared("DROP PROCEDURE IF EXISTS CalculateProfitLossSummary;");
        }
    }
};