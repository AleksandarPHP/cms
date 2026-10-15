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
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreignId('content_plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('plan_position')->nullable()->after('content_plan_id');
            $table->string('post_title')->nullable()->after('plan_position');
            $table->unsignedTinyInteger('generation_attempts')->default(0)->after('post_title');
            $table->index(['content_plan_id', 'plan_position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropIndex(['content_plan_id', 'plan_position']);
            $table->dropConstrainedForeignId('content_plan_id');
            $table->dropColumn(['plan_position', 'post_title', 'generation_attempts']);
        });
    }
};
