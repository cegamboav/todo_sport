<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('internal_code', 32);
            $table->string('name');
            $table->foreignId('modality_id')->constrained()->restrictOnDelete();
            $table->foreignId('ring_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('competition_order')->default(0);
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->string('reference_age')->nullable();
            $table->string('reference_grade')->nullable();
            $table->string('reference_weight')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'internal_code']);
            $table->index(['event_id', 'competition_order']);
            $table->index(['event_id', 'status']);
        });

        Schema::create('category_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_category_id')->constrained('event_categories')->cascadeOnDelete();
            $table->foreignId('event_competitor_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_category_id', 'event_competitor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_competitors');
        Schema::dropIfExists('event_categories');
    }
};
