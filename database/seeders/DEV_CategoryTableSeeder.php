<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class DEV_CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $CategoryData = [
            [
                "label_ita" => "Action Figures",
                "label_eng" => "Action Figures",
                "parent_id" =>  null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Giochi in scatola",
                "label_eng" => "Board Games",
                "parent_id" =>  null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Bambole",
                "label_eng" => "Dolls",
                "parent_id" =>  null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Robot",
                "label_eng" => "Robot",
                "parent_id" =>  null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Marvel",
                "label_eng" => "Marvel",
                "parent_id" =>  1,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "DC",
                "label_eng" => "DC",
                "parent_id" => 1,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Iron Man",
                "label_eng" => "Iron Man",
                "parent_id" =>  5,
                "type" => 2,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Spider man",
                "label_eng" => "Spider man",
                "parent_id" => 5,
                "type" => 2,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "SuperMan",
                "label_eng" => "SuperMan",
                "parent_id" =>  6,
                "type" => 2,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Thor",
                "label_eng" => "Thor",
                "parent_id" => 5,
                "type" => 2,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Manga",
                "label_eng" => "Manga",
                "parent_id" => null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "One Piece",
                "label_eng" => "One Piece",
                "parent_id" => 11,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Naruto",
                "label_eng" => "Naruto",
                "parent_id" => 7,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "DragonBall",
                "label_eng" => "Naruto",
                "parent_id" => 7,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Furby",
                "label_eng" => "Furby",
                "parent_id" => 3,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Barbie",
                "label_eng" => "Barbie",
                "parent_id" => 3,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Transformers",
                "label_eng" => "Transformers",
                "parent_id" => 4,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Byonichles",
                "label_eng" => "Byonichles",
                "parent_id" => 4,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Fumetti",
                "label_eng" => "Comics",
                "parent_id" => null,
                "type" => 0,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Marvel",
                "label_eng" => "Marvel",
                "parent_id" => 19,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "DC",
                "label_eng" => "DC",
                "parent_id" => 19,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Topolino",
                "label_eng" => "Mikey Mouse",
                "parent_id" => 19,
                "type" => 1,
                "created_at" => now(),
                "updated_at" => now()
            ]
        ];

        DB::table('categories')->insert($CategoryData);
    }
}
