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
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_scan_mode')->default('camera')->after('role');
            $table->foreignId('preferred_event_id')->nullable()->after('preferred_scan_mode')->constrained('events')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['preferred_event_id']);
            $table->dropColumn(['preferred_scan_mode', 'preferred_event_id']);
        });
    }
};
