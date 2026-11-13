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
        Schema::create('video_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('final_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('title');
            $table->text('script')->nullable();
            $table->string('aspect_ratio', 10)->default('9:16');
            $table->string('language', 10)->default('en');
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('estimated_cost_micros')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_projects');
    }
};
