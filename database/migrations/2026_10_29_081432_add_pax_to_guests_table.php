<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            // Default 2 Dewasa, 2 Anak sesuai kebijakan standar Anda
            $table->integer('adult_pax')->default(2)->after('job_type');
            $table->integer('child_pax')->default(2)->after('adult_pax');
        });
    }

    public function down(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn(['adult_pax', 'child_pax']);
        });
    }
};
