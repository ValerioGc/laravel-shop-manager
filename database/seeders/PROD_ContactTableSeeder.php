<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Image;

class PROD_ContactTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Assumi che le immagini siano state già seedate
        $images = Image::all()->keyBy('path');

        $contactData = [
            [
                'label_eng' => '+39 333333333',
                'label_ita' => '+39 333333333',
                'link_value' => 'tel:+33333333333',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_eng' => 'info@shop.com',
                'label_ita' => 'info@shop.com',
                'link_value' => 'mailto:info@shop.com',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_eng' => 'Facebook',
                'label_ita' => 'Facebook',
                'link_value' => 'https://wwww.facebook.com/shop',
                "created_at" => now(),
                "updated_at"=> now()
            ],
            [
                'label_eng' => 'Instagram',
                'label_ita' => 'Instagram',
                'link_value' => 'https://www.instagram.com/shop',
                "created_at" => now(),
                "updated_at"=> now()
            ]
        ];

        DB::table('contacts')->insert($contactData);
    }
}
