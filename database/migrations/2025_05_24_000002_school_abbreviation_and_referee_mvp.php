<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('schools', 'short_code') && ! Schema::hasColumn('schools', 'abbreviation')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('abbreviation', 15)->nullable()->after('name');
            });

            foreach (DB::table('schools')->get(['id', 'short_code']) as $school) {
                DB::table('schools')
                    ->where('id', $school->id)
                    ->update(['abbreviation' => strtoupper(trim((string) $school->short_code))]);
            }

            Schema::table('schools', function (Blueprint $table) {
                $table->dropUnique(['short_code']);
                $table->dropColumn('short_code');
                $table->unique('abbreviation');
            });
        }

        DB::table('referees')
            ->whereNotIn('specialty', ['table', 'corner'])
            ->update(['specialty' => 'corner']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('schools', 'abbreviation') && ! Schema::hasColumn('schools', 'short_code')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('short_code', 20)->nullable()->after('name');
            });

            foreach (DB::table('schools')->get(['id', 'abbreviation']) as $school) {
                DB::table('schools')
                    ->where('id', $school->id)
                    ->update(['short_code' => $school->abbreviation]);
            }

            Schema::table('schools', function (Blueprint $table) {
                $table->dropUnique(['abbreviation']);
                $table->dropColumn('abbreviation');
                $table->unique('short_code');
            });
        }
    }
};
