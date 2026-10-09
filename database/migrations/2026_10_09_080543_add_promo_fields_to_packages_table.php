<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            if (!Schema::hasColumn('packages', 'is_promo')) {
                $table->boolean('is_promo')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('packages', 'promo_badge')) {
                $table->string('promo_badge', 50)->nullable()->after('is_promo');
            }
            if (!Schema::hasColumn('packages', 'description')) {
                $table->text('description')->nullable()->after('promo_badge');
            }
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn(['is_promo', 'promo_badge', 'description']);
        });
    }
};