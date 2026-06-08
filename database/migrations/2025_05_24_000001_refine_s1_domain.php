<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('grades', 'category')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->string('category', 10)->default('kup')->after('name');
            });
        }

        if (Schema::hasColumn('referees', 'availability') && ! Schema::hasColumn('referees', 'specialty')) {
            Schema::table('referees', function (Blueprint $table) {
                $table->string('specialty')->default('central')->after('grade_id');
            });

            DB::table('referees')->update(['specialty' => 'central']);

            Schema::table('referees', function (Blueprint $table) {
                $table->dropIndex(['availability']);
                $table->dropColumn('availability');
                $table->index('specialty');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('referees', 'specialty') && ! Schema::hasColumn('referees', 'availability')) {
            Schema::table('referees', function (Blueprint $table) {
                $table->string('availability')->default('available')->after('grade_id');
            });

            DB::table('referees')->update(['availability' => 'available']);

            Schema::table('referees', function (Blueprint $table) {
                $table->dropIndex(['specialty']);
                $table->dropColumn('specialty');
                $table->index('availability');
            });
        }

        if (Schema::hasColumn('grades', 'category')) {
            Schema::table('grades', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
