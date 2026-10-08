<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\paramBiaya;

class TabelParamBiaya extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
        paramBiaya::create([
            'pla' => 1000000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5571,
        ]);

         paramBiaya::create([
            'pla' => 1500000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5857,
        ]);

        paramBiaya::create([
            'pla' => 2000000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5143,
        ]);

        paramBiaya::create([
            'pla' => 2500000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5429,
        ]);

        paramBiaya::create([
            'pla' => 3000000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5714,
        ]);

        paramBiaya::create([
            'pla' => 3500000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5000,
        ]);

        paramBiaya::create([
            'pla' => 4000000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5286,
        ]);

        paramBiaya::create([
            'pla' => 4500000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5571,
        ]);

        paramBiaya::create([
            'pla' => 5000000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5857,
        ]);

        paramBiaya::create([
            'pla' => 5500000,
            'margin' => 24,
            'jw' => 35,
            'tab' => 5143,
        ]);

        paramBiaya::create([
            'pla' => 6000000,
            'margin' => 24,
            'jw' => 35,
            'tab'=> 5429,
        ]);
    }
}
