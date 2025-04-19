<?php

namespace database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PROD_FaqTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqData = [
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
          
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "label_ita" => "Lorem ipsum dolor sit amet?",
                "label_eng" => "Lorem ipsum dolor sit amet?",
                "answer_ita" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "answer_eng" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.",
                "created_at" => now(),
                "updated_at" => now()
            ]
        ];

        DB::table('faqs')->insert($faqData);
    }
}
