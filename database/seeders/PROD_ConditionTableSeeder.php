<?php

namespace database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PROD_ConditionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $conditionData = [
            [
                'label_eng' => 'damaged',
                'label_ita' => 'rovinato',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_eng' => 'missing parts',
                'label_ita' => 'parti mancanti',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_ita' => 'buono',
                'label_eng' => 'good',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_ita' => 'ottimo',
                'label_eng' => 'excellent',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_ita' => 'perfetto',
                'label_eng' => 'perfect',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_ita' => 'come nuovo',
                'label_eng' => 'like new',
                "created_at" => now(),
                "updated_at"=> now()
            ]
        ];

        DB::table('conditions')->insert($conditionData);
    }
}
