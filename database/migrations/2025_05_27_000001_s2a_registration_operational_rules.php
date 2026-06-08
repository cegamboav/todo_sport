<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registration_items', function (Blueprint $table) {
            $table->boolean('is_billable')->default(true)->after('amount');
            $table->boolean('admin_override')->default(false)->after('is_billable');
        });
    }

    public function down(): void
    {
        Schema::table('registration_items', function (Blueprint $table) {
            $table->dropColumn(['is_billable', 'admin_override']);
        });
    }
};
