<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'newsletter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('newsletter')->default(false);
            });
        }

        DB::table('users')->update(['newsletter' => true]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'newsletter')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('newsletter');
            });
        }
    }
};
