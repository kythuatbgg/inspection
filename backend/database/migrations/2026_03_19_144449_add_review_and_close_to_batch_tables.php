<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add review columns to plan_details
        Schema::table('plan_details', function (Blueprint $table) {
            $table->string('review_status')->default('pending')->after('status'); // pending, approved, rejected
            $table->text('review_note')->nullable()->after('review_status');
            $table->timestamp('reviewed_at')->nullable()->after('review_note');
        });

        // Add closed_at to inspection_batches
        Schema::table('inspection_batches', function (Blueprint $table) {
            $table->timestamp('closed_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('plan_details', function (Blueprint $table) {
            $table->dropColumn(['review_status', 'review_note', 'reviewed_at']);
        });

        Schema::table('inspection_batches', function (Blueprint $table) {
            $table->dropColumn('closed_at');
        });
    }
};
