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
        if (Schema::hasColumn('content_plans', 'day_post_types')) {
            return;
        }

        Schema::table('content_plans', function (Blueprint $table) {
            $table->json('day_post_types')->nullable()->after('publish_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('content_plans', 'day_post_types')) {
            return;
        }

        Schema::table('content_plans', function (Blueprint $table) {
            $table->dropColumn('day_post_types');
        });
    }
};
