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
        Schema::create('content_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('topic');
            $table->unsignedTinyInteger('duration_months');
            $table->unsignedTinyInteger('posts_per_week')->default(2);
            $table->json('publish_days')->nullable();
            $table->string('publish_time', 5)->default('09:00');
            $table->string('tone')->default('professional');
            $table->json('platforms')->nullable();
            $table->string('timezone')->default('Europe/Zurich');
            $table->boolean('enable_web_search')->default(true);
            $table->string('status')->default('generating');
            $table->text('research_summary')->nullable();
            $table->unsignedSmallInteger('posts_expected')->default(0);
            $table->unsignedTinyInteger('failure_count')->default(0);
            $table->text('error_message')->nullable();
            $table->dateTime('batch_locked_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_plans');
    }
};
