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
        Schema::table('settings', function (Blueprint $table) {
            $table->string('wa_default_method', 20)->default('wa_me')->after('login_overlay_opacity');
            $table->text('wa_message_template')->nullable()->after('wa_default_method');
            $table->string('wa_gateway_provider', 30)->default('fonnte')->after('wa_message_template');
            $table->string('wa_gateway_api_key')->nullable()->after('wa_gateway_provider');
            $table->string('wa_gateway_sender', 50)->nullable()->after('wa_gateway_api_key');
            $table->string('wa_gateway_endpoint')->nullable()->after('wa_gateway_sender');
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->timestamp('wa_sent_at')->nullable()->after('is_attended');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'wa_default_method',
                'wa_message_template',
                'wa_gateway_provider',
                'wa_gateway_api_key',
                'wa_gateway_sender',
                'wa_gateway_endpoint',
            ]);
        });

        Schema::table('guests', function (Blueprint $table) {
            $table->dropColumn('wa_sent_at');
        });
    }
};
