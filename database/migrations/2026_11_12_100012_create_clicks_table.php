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
        Schema::create('clicks', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('presell_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ad_entity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('platform', 40)->nullable();
            $table->string('platform_click_id', 512)->nullable();
            $table->string('external_campaign_id', 64)->nullable();
            $table->string('external_ad_group_id', 64)->nullable();
            $table->string('external_ad_id', 64)->nullable();
            $table->string('external_keyword_id', 64)->nullable();
            $table->string('keyword')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->char('country', 2)->nullable();
            $table->string('device', 20)->nullable();
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->boolean('is_invalid')->default(false);
            $table->string('invalid_reason', 60)->nullable();
            $table->timestamp('checkout_redirected_at')->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index(['platform', 'external_campaign_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clicks');
    }
};
