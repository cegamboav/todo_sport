<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->string('gender')->nullable()->after('last_name');
            $table->date('birth_date')->nullable()->after('gender');
            $table->foreignId('grade_id')->nullable()->after('birth_date')->constrained('grades')->nullOnDelete();
            $table->decimal('weight_kg', 5, 2)->nullable()->after('grade_id');
            $table->unsignedSmallInteger('height_cm')->nullable()->after('weight_kg');
            $table->text('medical_notes')->nullable()->after('height_cm');
            $table->string('status')->default('active')->after('medical_notes');
            $table->softDeletes();
        });

        Schema::table('competitors', function (Blueprint $table) {
            $table->dropIndex(['school_id']);
        });

        Schema::table('competitors', function (Blueprint $table) {
            $table->foreign('school_id')->references('id')->on('schools')->restrictOnDelete();
            $table->index('status');
            $table->index('grade_id');
        });
    }

    public function down(): void
    {
        Schema::table('competitors', function (Blueprint $table) {
            $table->dropForeign(['school_id']);
            $table->dropForeign(['grade_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['grade_id']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'gender',
                'birth_date',
                'grade_id',
                'weight_kg',
                'height_cm',
                'medical_notes',
                'status',
            ]);
        });

        Schema::table('competitors', function (Blueprint $table) {
            $table->index('school_id');
        });
    }
};
