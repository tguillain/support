<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where RecordTicketResolutionDelay writes its verdict.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->unsignedInteger('resolution_hours')->nullable()->after('resolved_at');
            $table->boolean('sla_met')->nullable()->after('resolution_hours');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['resolution_hours', 'sla_met']);
        });
    }
};
