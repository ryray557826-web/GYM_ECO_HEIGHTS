<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Table for tracking individual physical pieces of equipment
        Schema::create('equipment_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->string('unit_code', 50)->unique(); // E.g., EQP-001-U1, EQP-001-U2
            $table->string('unit_label', 100);        // E.g., Barbell #1, Barbell #2
            $table->string('serial_number', 100)->nullable();
            $table->enum('status', ['operational', 'needs_maintenance', 'under_repair', 'retired'])->default('operational');
            $table->date('last_maintenance_date')->nullable();
            $table->date('next_maintenance_due')->nullable();
            $table->timestamps();
        });

        // 2. Link maintenance records to specific units
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            if (!Schema::hasColumn('equipment_maintenances', 'equipment_unit_id')) {
                $table->foreignId('equipment_unit_id')->nullable()->after('equipment_id')->constrained('equipment_units')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('equipment_maintenances', function (Blueprint $table) {
            $table->dropForeign(['equipment_unit_id']);
            $table->dropColumn('equipment_unit_id');
        });
        Schema::dropIfExists('equipment_units');
    }
};