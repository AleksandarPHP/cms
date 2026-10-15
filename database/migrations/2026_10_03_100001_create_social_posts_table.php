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
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', [
                'draft',
                'processing',
                'pending_approval',
                'scheduled',
                'published',
                'failed',
            ])->default('draft');
            $table->text('user_prompt')->nullable();
            $table->string('source_url')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('enable_web_search')->default(false);
            $table->string('tone')->default('professional');
            $table->text('fb_content')->nullable();
            $table->text('ig_content')->nullable();
            $table->json('hashtags')->nullable();
            $table->text('ai_visual_suggestion')->nullable();
            $table->json('platforms')->nullable();
            $table->string('timezone')->default('Europe/Zurich');
            $table->json('meta_response')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('published_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
