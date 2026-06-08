<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('role', 'judge')->update(['role' => 'corner']);
    }

    public function down(): void
    {
        // No revert — judge role removed from enum
    }
};
