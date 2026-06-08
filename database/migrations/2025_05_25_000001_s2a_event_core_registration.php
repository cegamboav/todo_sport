<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modalities', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->date('event_date')->nullable()->after('name');
            $table->string('venue', 200)->nullable()->after('event_date');
            $table->foreignId('host_school_id')->nullable()->after('venue')->constrained('schools')->nullOnDelete();
            $table->text('notes')->nullable()->after('status');
        });

        DB::table('events')->where('status', 'open')->update(['status' => 'operational']);
        DB::table('events')->where('status', 'closed')->update(['status' => 'finished']);

        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('third_place_mode', 30)->default('no_bronze');
            $table->boolean('allow_team_forms')->default(false);
            $table->string('bronze_mode', 40)->nullable();
            $table->timestamps();
        });

        Schema::create('event_modalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->decimal('price', 10, 2)->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'modality_id']);
        });

        Schema::create('event_combos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->decimal('price', 10, 2);
            $table->boolean('enabled')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'name']);
        });

        Schema::create('event_combo_modalities', function (Blueprint $table) {
            $table->foreignId('event_combo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('modality_id')->constrained()->cascadeOnDelete();

            $table->primary(['event_combo_id', 'modality_id']);
        });

        Schema::create('event_competitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('competitor_id')->constrained()->cascadeOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'competitor_id']);
        });

        Schema::create('registration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_competitor_id')->constrained()->cascadeOnDelete();
            $table->string('item_type', 20);
            $table->foreignId('event_modality_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('event_combo_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label', 160);
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->timestamps();

            $table->index(['event_competitor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_items');
        Schema::dropIfExists('event_competitors');
        Schema::dropIfExists('event_combo_modalities');
        Schema::dropIfExists('event_combos');
        Schema::dropIfExists('event_modalities');
        Schema::dropIfExists('event_settings');

        Schema::table('events', function (Blueprint $table) {
            $table->dropConstrainedForeignId('host_school_id');
            $table->dropColumn(['event_date', 'venue', 'notes']);
        });

        DB::table('events')->where('status', 'operational')->update(['status' => 'open']);
        DB::table('events')->where('status', 'finished')->update(['status' => 'closed']);

        Schema::dropIfExists('modalities');
    }
};
