<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // 1. STORED PROCEDURE: CalculateProfitLossSummary
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
                -- Compute Total Revenue
                SELECT COALESCE(SUM(amount), 0.00)
                INTO p_total_revenue
                FROM revenues
                WHERE revenue_date BETWEEN p_start_date AND p_end_date;

                -- Compute Total Expenses
                SELECT COALESCE(SUM(amount), 0.00)
                INTO p_total_expenses
                FROM expenses
                WHERE expense_date BETWEEN p_start_date AND p_end_date;

                -- Compute Net Income
                SET p_net_income = p_total_revenue - p_total_expenses;

                -- Compute Profit/Loss Ratio: (Net Income / Total Revenue)
                IF p_total_revenue > 0 THEN
                    SET p_profit_loss_ratio = ROUND((p_net_income / p_total_revenue), 4);
                ELSE
                    SET p_profit_loss_ratio = 0.0000;
                END IF;
            END;
        ");

        // 2. STORED PROCEDURE: ExpireOverdueSubscriptions
        DB::unprepared("DROP PROCEDURE IF EXISTS ExpireOverdueSubscriptions;");
        DB::unprepared("
            CREATE PROCEDURE ExpireOverdueSubscriptions()
            BEGIN
                UPDATE member_subscriptions
                SET status = 'expired'
                WHERE status = 'active'
                  AND end_time < NOW();

                UPDATE members m
                JOIN member_subscriptions ms ON m.id = ms.member_id
                SET m.membership_status = 'expired'
                WHERE ms.status = 'expired'
                  AND m.membership_status = 'active';
            END;
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS ExpireOverdueSubscriptions;");
        DB::unprepared("DROP PROCEDURE IF EXISTS CalculateProfitLossSummary;");
    }
};