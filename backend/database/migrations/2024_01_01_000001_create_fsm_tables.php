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
        // Drop default Laravel users tables and recreate
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->unique();
            $table->string('password');
            $table->enum('role', ['admin', 'inspector'])->default('inspector');
            $table->enum('lang_pref', ['vn', 'en', 'kh'])->default('vn');
            $table->rememberToken();
            $table->timestamps();
        });

        // Cabinets table - Master Data
        Schema::create('cabinets', function (Blueprint $table) {
            $table->string('cabinet_code')->primary();
            $table->string('bts_site');
            $table->string('name');
            $table->string('type');
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->timestamps();
        });

        // Checklists table
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->tinyInteger('min_pass_score')->default(80);
            $table->tinyInteger('max_critical_allowed')->default(1);
            $table->timestamps();
        });

        // Checklist Items table
        Schema::create('checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->string('category');
            $table->text('content_vn');
            $table->text('content_en');
            $table->text('content_kh');
            $table->tinyInteger('max_score')->default(0);
            $table->boolean('is_critical')->default(false);
            $table->timestamps();
        });

        // Inspection Batches table
        Schema::create('inspection_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');
            $table->timestamps();
        });

        // Plan Details table
        Schema::create('plan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('inspection_batches')->onDelete('cascade');
            $table->string('cabinet_code');
            $table->foreign('cabinet_code')->references('cabinet_code')->on('cabinets')->onDelete('cascade');
            $table->enum('status', ['planned', 'done'])->default('planned');
            $table->timestamps();
        });

        // Inspections table
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('checklist_id')->constrained()->onDelete('cascade');
            $table->foreignId('plan_detail_id')->nullable()->constrained()->onDelete('set null');
            $table->string('cabinet_code');
            $table->foreign('cabinet_code')->references('cabinet_code')->on('cabinets')->onDelete('cascade');
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->tinyInteger('total_score')->default(0);
            $table->tinyInteger('critical_errors_count')->default(0);
            $table->enum('final_result', ['PASS', 'FAIL'])->nullable();
            $table->timestamps();
        });

        // Inspection Details table
        Schema::create('inspection_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->onDelete('cascade');
            $table->foreignId('item_id')->constrained('checklist_items')->onDelete('cascade');
            $table->boolean('is_failed')->default(false);
            $table->tinyInteger('score_awarded')->default(0);
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inspection_details');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('plan_details');
        Schema::dropIfExists('inspection_batches');
        Schema::dropIfExists('checklist_items');
        Schema::dropIfExists('checklists');
        Schema::dropIfExists('cabinets');
        Schema::dropIfExists('users');
    }
};
