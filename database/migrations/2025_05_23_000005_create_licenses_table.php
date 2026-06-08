<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('license_id')->nullable();
            $table->string('organization_name')->nullable();
            $table->string('license_type')->nullable();
            $table->string('edition')->nullable();
            $table->date('issued_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->unsignedSmallInteger('grace_days')->default(14);
            $table->unsignedSmallInteger('max_rings')->nullable();
            $table->json('features_json')->nullable();
            $table->json('payload_json')->nullable();
            $table->text('signature')->nullable();
            $table->string('key_id')->nullable();
            $table->string('status')->default('missing');
            $table->timestamp('imported_at')->nullable();
            $table->foreignId('imported_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->uuid('installation_id')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();

            $table->index('is_current');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licenses');
    }
};
