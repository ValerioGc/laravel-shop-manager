<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PROD_UserSeeder extends Seeder
{
    /**
     * Run the database users seeds.
     */
    public function run(): void
    {

        $users =[
            [
                'name' => 'Developer',
                'email' => 'dev@shop.com',
                'password' => bcrypt('admin!'),
            ],
            [
                'name' => 'Admin',
                'email' => 'admin@shop.com',
                'password' => bcrypt('admin!'),
            ],
        ];

        DB::table('users')->insert($users);
    }
}
