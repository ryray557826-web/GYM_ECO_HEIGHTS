<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Users Table (Authentication Credentials)
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('email')->unique();
                $table->string('password');
                $table->enum('role', ['owner', 'staff', 'member'])->default('member');
                $table->enum('account_status', ['active', 'pending', 'suspended'])->default('pending');
                $table->timestamp('email_verified_at')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // 2. Employees / Staff Table (Future-proofing as per doc pg 5 & 16)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('employee_code', 30)->unique(); // E.g., EMP-001
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('position', ['owner', 'front_desk', 'attendant', 'encoder', 'collector'])->default('front_desk');
            $table->string('contact_number', 30);
            $table->date('hire_date');
            $table->enum('status', ['active', 'on_leave', 'resigned'])->default('active');
            $table->timestamps();
        });

        // 3. Customers Table (Personal demographics for walk-ins & registered persons)
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

        // 4. Members Table (1:1 with Customer, gym membership card and enrollment ID)
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('member_code', 30)->unique(); // E.g., ECO-001
            $table->date('joined_date');
            $table->enum('membership_status', ['active', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->text('medical_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('users');
    }
};