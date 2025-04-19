<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DEV_ShowTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $shows = [
            // Past shows
            [
                'label_ita' => 'Comiconn 2023',
                'label_eng' => 'Comiconn 2023',
                'start_date' => Carbon::now()->subDays(30)->format('Y-m-d'),
                'end_date' => Carbon::now()->subDays(29)->format('Y-m-d'),
                'location' => 'San Diego',
                'description_ita' => 'Descrizione Fiera Passata 3 ITA',
                'description_eng' => 'Description Past Fair 3 ENG',
                'link' => 'https://example.com/fiera-passata-3',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'label_ita' => 'Fiera Passata 1',
                'label_eng' => 'Fiera Passata 1',
                'start_date' => Carbon::now()->subDays(25)->format('Y-m-d'),
                'end_date' => Carbon::now()->subDays(24)->format('Y-m-d'),
                'location' => 'Roma',
                'description_ita' => 'Descrizione Fiera Passata 4 ITA',
                'description_eng' => 'Description Past Fair 4 ENG',
                'link' => 'https://example.com/fiera-passata-4',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'label_ita' => 'Fiera Passata 5',
                'label_eng' => 'Fiera Passata 5',
                'start_date' => Carbon::now()->subDays(10)->format('Y-m-d'),
                'end_date' => Carbon::now()->subDays(9)->format('Y-m-d'),
                'location' => 'Roma',
                'description_ita' => 'Descrizione Fiera Passata 5 ITA',
                'description_eng' => 'Description Past Fair 5 ENG',
                'link' => 'https://example.com/fiera-passata-5',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            // Future shows
            [
                'label_ita' => 'Lucca Comics 2025',
                'label_eng' => 'Lucca Comics 2025',
                'start_date' => Carbon::now()->addDays(5)->format('Y-m-d'),
                'end_date' => Carbon::now()->addDays(6)->format('Y-m-d'),
                'location' => 'Lucca',
                'description_ita' => 'Descrizione Fiera Futura 1 ITA',
                'description_eng' => 'Description Future Fair 1 ENG',
                'link' => 'https://example.com/fiera-futura-1',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'label_ita' => 'Comiconn 2025',
                'label_eng' => 'Comiconn 2025',
                'start_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
                'end_date' => Carbon::now()->addDays(11)->format('Y-m-d'),
                'location' => 'San Diego',
                'description_ita' => 'Descrizione Fiera Futura 2 ITA',
                'description_eng' => 'Description Future Fair 2 ENG',
                'link' => 'https://example.com/fiera-futura-2',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'label_ita' => 'Romics 2025',
                'label_eng' => 'Romics 2025',
                'start_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
                'location' => 'Roma',
                'end_date' => null,
                'description_ita' => 'Descrizione Fiera Futura 3 ITA',
                'description_eng' => 'Description Future Fair 3 ENG',
                'link' => 'https://example.com/fiera-futura-3',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('shows')->insert($shows);
    }
}
