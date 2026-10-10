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
            $table->string('position', 50)->default('front_desk');
            $table->string('contact_number', 30);
            $table->date('hire_date');
            $table->string('status', 50)->default('active');
            $table->timestamps();
        });

        // 2. Customers Table
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

        // 3. Members Table (Uses string columns to prevent SQLite CHECK constraint crashes)
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
            $table->string('member_code', 30)->unique();
            $table->date('joined_date');
            
            // Verification Status: 'pending' vs 'verified'
            $table->string('verification_status', 50)->default('pending');
            
            // Pass Access Status: 'active', 'expired', 'suspended', 'pending', 'cancelled'
            $table->string('membership_status', 50)->default('pending');
            
            // Suspension Comment
            $table->text('suspension_reason')->nullable();
            
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