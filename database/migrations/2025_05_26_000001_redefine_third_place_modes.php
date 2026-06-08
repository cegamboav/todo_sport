<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $map = [
            'none' => 'no_bronze',
            'single' => 'champion_carries_bronze',
            'dual' => 'dual_bronze',
        ];

        foreach ($map as $from => $to) {
            DB::table('event_settings')->where('third_place_mode', $from)->update(['third_place_mode' => $to]);
        }

        DB::table('event_settings')
            ->whereNotIn('third_place_mode', ['no_bronze', 'champion_carries_bronze', 'bronze_match', 'dual_bronze'])
            ->update(['third_place_mode' => 'no_bronze']);
    }

    public function down(): void
    {
        $map = [
            'no_bronze' => 'none',
            'champion_carries_bronze' => 'single',
            'dual_bronze' => 'dual',
        ];

        foreach ($map as $from => $to) {
            DB::table('event_settings')->where('third_place_mode', $from)->update(['third_place_mode' => $to]);
        }
    }
};
