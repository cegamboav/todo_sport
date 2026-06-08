<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_categories', function (Blueprint $table) {
            $table->string('gender_scope')->default('mixed')->after('modality_id');
        });

        Schema::create('category_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->foreignId('red_event_competitor_id')->nullable()->constrained('event_competitors')->nullOnDelete();
            $table->foreignId('blue_event_competitor_id')->nullable()->constrained('event_competitors')->nullOnDelete();
            $table->unsignedInteger('bout_order')->default(1);
            $table->string('stage_label')->default('R1');
            $table->timestamps();

            $table->index(['event_category_id', 'bout_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_matches');

        Schema::table('event_categories', function (Blueprint $table) {
            $table->dropColumn('gender_scope');
        });
    }
};
