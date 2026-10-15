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
        if (Schema::hasColumn('social_posts', 'post_type')) {
            return;
        }

        Schema::table('social_posts', function (Blueprint $table) {
            $table->string('post_type')->nullable()->after('tone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('social_posts', 'post_type')) {
            return;
        }

        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropColumn('post_type');
        });
    }
};
