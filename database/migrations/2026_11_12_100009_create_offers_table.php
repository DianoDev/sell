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
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_connection_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_product_id', 64)->nullable();
            $table->string('name');
            $table->string('checkout_url', 2048);
            $table->string('tracking_parameter', 50)->nullable();
            $table->unsignedBigInteger('commission_micros')->nullable();
            $table->decimal('commission_percent', 5, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('external_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
