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
        Schema::table('inspections', function (Blueprint $table) {
            $table->json('overall_photos')->nullable()->after('cabinet_code');
        });

        Schema::table('inspection_details', function (Blueprint $table) {
            $table->text('note')->nullable()->after('image_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn('overall_photos');
        });

        Schema::table('inspection_details', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
