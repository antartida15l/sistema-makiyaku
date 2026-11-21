<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CultivosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Llamar a todos los seeders regionales
        $this->call([
            CultivosCostaNorteSeeder::class,
            CultivosCostaCentralSurSeeder::class,
            CultivosSierraSeeder::class,
            CultivosSelvaSeeder::class,
        ]);
    }
}