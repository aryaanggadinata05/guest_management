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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('app_name')->default('Guest Management');
            $table->string('login_subtitle')->default('Sistem Registrasi & Manajemen Tamu Acara');
            $table->string('logo_path')->nullable();
            $table->string('favicon_path')->nullable();
            $table->string('primary_color', 20)->default('#2f76b5');
            $table->string('secondary_color', 20)->default('#1e4e79');
            $table->string('login_bg_type', 20)->default('gradient');
            $table->string('login_bg_image')->nullable();
            $table->unsignedTinyInteger('login_overlay_opacity')->default(45);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
