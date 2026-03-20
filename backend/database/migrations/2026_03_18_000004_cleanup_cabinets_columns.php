<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add note column if not exists
        if (!Schema::hasColumn('cabinets', 'note')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->text('note')->nullable()->after('lng');
            });
        }

        // Drop unused columns if they exist
        if (Schema::hasColumn('cabinets', 'name')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->dropColumn('name');
            });
        }

        if (Schema::hasColumn('cabinets', 'type')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        if (Schema::hasColumn('cabinets', 'address')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->dropColumn('address');
            });
        }

        if (Schema::hasColumn('cabinets', 'area')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->dropColumn('area');
            });
        }

        if (Schema::hasColumn('cabinets', 'status')) {
            Schema::table('cabinets', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed for this cleanup migration
    }
};
