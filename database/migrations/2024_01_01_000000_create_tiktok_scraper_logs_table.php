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
        Schema::create('tiktok_scraper_logs', function (Blueprint $table) {
            $table->id();
            $table->string('url', 500)->index();
            $table->string('video_id', 50)->nullable()->index();
            $table->string('username', 100)->nullable()->index();
            $table->enum('status', ['success', 'failed'])->index();
            $table->string('error_message', 1000)->nullable();
            $table->string('error_code', 50)->nullable();
            $table->json('response_data')->nullable();
            $table->unsignedInteger('response_time_ms')->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->ipAddress('ip_address')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->boolean('from_cache')->default(false)->index();
            $table->timestamps();

            // Indexes for better performance
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index(['ip_address', 'created_at']);
            $table->index(['from_cache', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tiktok_scraper_logs');
    }
};
