<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plan_details', function (Blueprint $table) {
            $table->foreignId('account_id')->nullable()->after('cabinet_code')->constrained('accounts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('plan_details', function (Blueprint $table) {
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
        });
    }
};
