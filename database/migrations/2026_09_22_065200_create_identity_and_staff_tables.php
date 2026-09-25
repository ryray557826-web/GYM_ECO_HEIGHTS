<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Employees Table
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 30)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('position', ['owner', 'front_desk', 'attendant', 'encoder', 'collector'])->default('front_desk');
            $table->string('contact_number', 30);
            $table->date('hire_date');
            $table->enum('status', ['active', 'on_leave', 'resigned'])->default('active');
            $table->timestamps();
        });

        // 2. Customers Table (Demographics)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('contact_number', 30);
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('age')->nullable();
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });

        // 3. Members Table (Gym ID & Reward Points)
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('member_code', 30)->unique(); // E.g., ECO-001
            $table->date('joined_date');
            $table->enum('membership_status', ['active', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->unsignedInteger('reward_points')->default(0);
            $table->text('medical_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('employees');
    }
};