<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DEV_CategoryProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $CategoryProduct = [
            // Action Figures
            ['product_id' => 1, 'category_id' => 1, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 2, 'category_id' => 1, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 3, 'category_id' => 1, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 4, 'category_id' => 1, "created_at" => now(), "updated_at" => now()],
            // Table games
            ['product_id' => 5, 'category_id' => 2, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 6, 'category_id' => 2, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 7, 'category_id' => 2, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 8, 'category_id' => 2, "created_at" => now(), "updated_at" => now()],
            // Dolls
            ['product_id' => 9, 'category_id' => 3, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 10, 'category_id' => 3, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 11, 'category_id' => 3, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 12, 'category_id' => 3, "created_at" => now(), "updated_at" => now()],
            // Robots
            ['product_id' => 13, 'category_id' => 4, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 14, 'category_id' => 4, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 15, 'category_id' => 4, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 16, 'category_id' => 4, "created_at" => now(), "updated_at" => now()],
            // Marvel (sub category of "Action Figures" and "Comics")
            ['product_id' => 17, 'category_id' => 5, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 18, 'category_id' => 5, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 19, 'category_id' => 5, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 20, 'category_id' => 5, "created_at" => now(), "updated_at" => now()],
            // DC (sub category of "Action Figures" and "Comics")
            ['product_id' => 21, 'category_id' => 6, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 22, 'category_id' => 6, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 23, 'category_id' => 6, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 24, 'category_id' => 6, "created_at" => now(), "updated_at" => now()],
            // Comics (Main category)
            ['product_id' => 25, 'category_id' => 19, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 26, 'category_id' => 19, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 27, 'category_id' => 19, "created_at" => now(), "updated_at" => now()],
            ['product_id' => 28, 'category_id' => 19, "created_at" => now(), "updated_at" => now()],
        ];

        DB::table('categories_products')->insert($CategoryProduct);
    }
}
