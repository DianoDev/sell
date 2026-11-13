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
        Schema::create('ad_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('platform_connection_id')->constrained()->cascadeOnDelete();
            $table->string('external_id', 64);
            $table->string('name');
            $table->char('currency', 3);
            $table->string('timezone', 64)->default('UTC');
            $table->string('status', 20)->default('active');
            $table->boolean('is_sync_enabled')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['platform_connection_id', 'external_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_accounts');
    }
};
