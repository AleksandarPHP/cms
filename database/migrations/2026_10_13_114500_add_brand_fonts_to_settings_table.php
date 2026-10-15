<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('settings', 'brand_font_primary')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->string('brand_font_primary', 80)->nullable()->after('logo');
            $table->string('brand_font_secondary', 80)->nullable()->after('brand_font_primary');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('settings', 'brand_font_primary')) {
            return;
        }

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['brand_font_primary', 'brand_font_secondary']);
        });
    }
};
