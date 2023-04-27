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
        Schema::create('payout_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained(table: 'bank_accounts');
            $table->string('payout_reference');
            $table->string('payout_provider_reference');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_records');
    }
};
