<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->string('category_vn')->nullable()->after('category');
            $table->string('category_en')->nullable()->after('category_vn');
            $table->string('category_kh')->nullable()->after('category_en');
        });
    }

    public function down(): void
    {
        Schema::table('checklist_items', function (Blueprint $table) {
            $table->dropColumn(['category_vn', 'category_en', 'category_kh']);
        });
    }
};
