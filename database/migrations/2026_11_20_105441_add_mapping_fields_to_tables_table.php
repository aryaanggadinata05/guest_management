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
        Schema::table('tables', function (Blueprint $table) {
            $table->decimal('pos_x', 6, 2)->nullable()->after('capacity');
            $table->decimal('pos_y', 6, 2)->nullable()->after('pos_x');
            $table->string('zone')->nullable()->after('pos_y');
            $table->string('pic_name')->nullable()->after('zone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn(['pos_x', 'pos_y', 'zone', 'pic_name']);
        });
    }
};
