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
        Schema::create('ad_metrics_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_entity_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->unsignedBigInteger('cost_micros')->default(0);
            $table->char('currency', 3);
            $table->decimal('conversions', 14, 4)->default(0);
            $table->unsignedBigInteger('conversion_value_micros')->default(0);
            $table->timestamps();

            $table->unique(['ad_entity_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_metrics_daily');
    }
};
