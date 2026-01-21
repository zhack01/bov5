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
        Schema::create('settlement_loggers', function (Blueprint $table) {
            $table->id();
            $table->string('jira_ticket_id')->unique();
            $table->string('round_id_hash')->index(); // Searchable hash
            $table->text('encrypted_round_id');       // Encrypted actual ID
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending'); 
            $table->foreignId('user_id'); // Person who requested it
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_loggers');
    }
};
