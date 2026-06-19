<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_matches', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('event_category_id')->constrained('events')->cascadeOnDelete();
            $table->string('match_code', 32)->nullable()->after('event_id');
            $table->unsignedSmallInteger('round_number')->default(1)->after('stage_label');
            $table->string('match_type', 20)->default('normal')->after('round_number');
            $table->string('status', 20)->default('pending')->after('match_type');
            $table->foreignId('winner_id')->nullable()->after('blue_event_competitor_id')
                ->constrained('event_competitors')->nullOnDelete();
        });

        Schema::table('category_matches', function (Blueprint $table) {
            $table->unique(['event_category_id', 'match_code']);
            $table->index(['event_category_id', 'round_number', 'bout_order']);
        });

        DB::table('category_matches')
            ->whereNull('event_id')
            ->update([
                'event_id' => DB::raw('(SELECT event_id FROM event_categories WHERE event_categories.id = category_matches.event_category_id)'),
            ]);
    }

    public function down(): void
    {
        Schema::table('category_matches', function (Blueprint $table) {
            $table->dropUnique(['event_category_id', 'match_code']);
            $table->dropIndex(['event_category_id', 'round_number', 'bout_order']);
            $table->dropConstrainedForeignId('winner_id');
            $table->dropConstrainedForeignId('event_id');
            $table->dropColumn(['match_code', 'round_number', 'match_type', 'status']);
        });
    }
};
