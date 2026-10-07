<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $piece = Unit::query()->updateOrCreate(
            ['name' => 'Adet'],
            ['multiplier' => 1, 'parent_id' => null, 'is_base' => true, 'is_active' => true],
        );

        $pack = Unit::query()->updateOrCreate(
            ['name' => 'Paket'],
            ['multiplier' => 10, 'parent_id' => $piece->id, 'is_base' => false, 'is_active' => true],
        );

        Unit::query()->updateOrCreate(
            ['name' => 'Koli'],
            ['multiplier' => 20, 'parent_id' => $pack->id, 'is_base' => false, 'is_active' => true],
        );
    }
}
