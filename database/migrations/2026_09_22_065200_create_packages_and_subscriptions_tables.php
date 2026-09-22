<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Packages Catalog (Monthly ₱750, Daily ₱50, Promos)
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_code', 30)->unique(); // PKG-MTH-750, PKG-DAY-050
            $table->string('name', 100);
            $table->enum('plan_type', ['monthly', 'daily', 'annual', 'special_promo'])->default('monthly');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('duration_in_days');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Package Features (3NF: Amenities included per package)
        Schema::create('package_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
            $table->string('feature_description', 200);
            $table->timestamps();
        });

        // 3. Member Subscriptions (Links Members to Packages)
        Schema::create('member_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('package_id')->constrained('packages')->restrictOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['active', 'expired', 'pending', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_subscriptions');
        Schema::dropIfExists('package_features');
        Schema::dropIfExists('packages');
    }
};