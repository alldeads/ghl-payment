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
        Schema::create('xendit_payments', function (Blueprint $table) {
            $table->id();
            $table->string('location_id')->index();
            $table->string('external_id')->unique();
            $table->string('xendit_invoice_id')->nullable()->unique();
            $table->string('status')->default('PENDING')->index();
            $table->string('currency', 6)->default('IDR');
            $table->decimal('amount', 15, 2);
            $table->string('payer_email')->nullable();
            $table->string('description')->nullable();
            $table->string('invoice_url')->nullable();
            $table->json('metadata')->nullable();
            $table->json('last_webhook_payload')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('xendit_payments');
    }
};
