<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Equipment Categories
        Schema::create('equipment_categories', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 30)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Gym Equipment Inventory
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->string('equipment_code', 30)->unique();
            $table->foreignId('category_id')->constrained('equipment_categories')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('brand', 100)->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('location_in_gym', 100);
            $table->enum('status', ['operational', 'needs_maintenance', 'under_repair', 'retired'])->default('operational');
            $table->date('purchase_date')->nullable();
            $table->date('next_maintenance_due')->nullable();
            $table->timestamps();
        });

        // 3. Maintenance Records
        Schema::create('equipment_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('maintenance_code', 30)->unique();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->date('maintenance_date');
            $table->string('facility_or_location', 150);
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->string('technician_name', 100)->nullable();
            $table->text('issue_description')->nullable();
            $table->text('work_done')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed'])->default('completed');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_maintenances');
        Schema::dropIfExists('equipment');
        Schema::dropIfExists('equipment_categories');
    }
};