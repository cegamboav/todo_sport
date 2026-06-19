<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('category_competitors', function (Blueprint $table) {
            $table->boolean('admin_override')->default(false)->after('sort_order');
        });
    }

    public function down(): void
    {
        Schema::table('category_competitors', function (Blueprint $table) {
            $table->dropColumn('admin_override');
        });
    }
};
