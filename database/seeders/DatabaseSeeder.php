<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PROD_FaqTableSeeder::class);
        $this->call(PROD_ContactTableSeeder::class);
        $this->call(PROD_UserSeeder::class);
        $this->call(PROD_ImageTableSeeder::class);
        $this->call(PROD_ConditionTableSeeder::class);

        $this->call(DEV_ShowTableSeeder::class);
        $this->call(DEV_CategoryTableSeeder::class);
        $this->call(DEV_ProductTableSeeder::class);
        $this->call(DEV_CategoryProductSeeder::class);
        $this->call(DEV_UserSeeder::class);
    }
}
