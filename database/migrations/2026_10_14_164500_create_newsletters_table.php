<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('issue_number')->unique();
            $table->string('locale', 5)->default('de');
            $table->string('title')->nullable();
            $table->text('text')->nullable();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('step')->default(1);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('newsletter_blog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('newsletter_id')->constrained()->cascadeOnDelete();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->unique(['newsletter_id', 'blog_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_blog');
        Schema::dropIfExists('newsletters');
    }
};
