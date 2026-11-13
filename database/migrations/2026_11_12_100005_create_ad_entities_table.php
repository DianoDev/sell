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
        Schema::create('ad_entities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ad_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ad_entities')->cascadeOnDelete();
            $table->string('level', 20);
            $table->string('external_id', 128);
            $table->string('name', 512);
            $table->string('status', 20)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['ad_account_id', 'level', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_entities');
    }
};
