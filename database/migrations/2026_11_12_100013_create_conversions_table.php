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
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('click_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('platform_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('webhook_event_id')->nullable()->constrained()->nullOnDelete();
            $table->string('network', 40);
            $table->string('external_order_id', 100);
            $table->string('external_transaction_id', 100);
            $table->string('event', 20);
            $table->bigInteger('amount_micros');
            $table->char('currency', 3);
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->unique(['network', 'external_transaction_id', 'event']);
            $table->index('external_order_id');
            $table->index('occurred_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};
