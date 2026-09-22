<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('log_code', 30)->unique(); // AUD-001
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 150);            // Membership Renewed, Status Override, Payment Approved
            $table->string('entity_type', 100);       // App\Models\Member, App\Models\Payment
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();   // Tracks what changed
            $table->json('new_values')->nullable();
            $table->string('validity_period', 50)->nullable(); // 1 Month, 24 Hours
            $table->string('performed_by', 100)->default('Owner');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};