<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Budgets
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->string('budget_code', 30)->unique();
            $table->string('category_name', 100);
            $table->year('fiscal_year')->default(2026);
            $table->decimal('allocated_amount', 12, 2);
            $table->decimal('spent_amount', 12, 2)->default(0.00);
            $table->timestamps();
        });

        // 2. Revenues Table
        Schema::create('revenues', function (Blueprint $table) {
            $table->id();
            $table->string('revenue_code', 30)->unique();
            $table->foreignId('payment_id')->unique()->constrained('payments')->cascadeOnDelete();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('revenue_date');
            $table->timestamps();
        });

        // 3. Expenses Table
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('expense_code', 30)->unique();
            $table->foreignId('budget_id')->nullable()->constrained('budgets')->nullOnDelete();
            $table->foreignId('maintenance_id')->nullable()->constrained('equipment_maintenances')->nullOnDelete();
            $table->enum('category', ['Equipment', 'Maintenance', 'Damages', 'Utilities', 'Supplies'])->default('Maintenance');
            $table->string('description', 255);
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('revenues');
        Schema::dropIfExists('budgets');
    }
};