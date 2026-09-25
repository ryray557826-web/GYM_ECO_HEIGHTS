<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Gym Attendances
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->foreignId('logged_by_employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('attendance_date');
            $table->time('check_in_time');
            $table->time('check_out_time')->nullable();
            $table->enum('entry_type', ['membership', 'per_session']);
            $table->string('notes', 200)->nullable();
            $table->timestamps();
        });

        // 2. Gym Notes (Workout logs)
        Schema::create('gym_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('workout_date');
            $table->string('routine_title', 150);
            $table->text('notes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_notes');
        Schema::dropIfExists('attendances');
    }
};