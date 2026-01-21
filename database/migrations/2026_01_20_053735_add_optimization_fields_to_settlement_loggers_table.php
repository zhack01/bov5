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
        Schema::table('settlement_loggers', function (Blueprint $table) {
            $table->integer('operator_id')->nullable()->index()->after('user_id');
            $table->string('client_name')->nullable()->after('operator_id');

            // Store the raw round_id for high-speed transaction history lookups
            $table->string('round_id')->nullable()->index()->after('client_name');
            
            // For audit security, store the IP and Fingerprint we discussed earlier
            $table->string('created_from_ip')->nullable()->after('round_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settlement_loggers', function (Blueprint $table) {
            $table->dropColumn([
                'operator_id', 
                'client_name', 
                'round_id', 
                'created_from_ip', 
            ]);
        });
    }
};
