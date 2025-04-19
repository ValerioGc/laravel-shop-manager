<?php

namespace database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PROD_ImageTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $imageData = [
            [
                'path' => '/storage/contacts/tel.png',
                'order' => null,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                'path' => '/storage/contacts/em.svg',
                'order' => null,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                'path' => '/storage/contacts/fb.png',
                'order' => null,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                'path' => '/storage/contacts/inst.png',
                'order' => null,
                "created_at" => now(),
                "updated_at" => now()
            ]
        ];

        DB::table('images')->insert($imageData);
    }
}
