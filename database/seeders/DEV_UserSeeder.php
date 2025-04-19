<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DEV_UserSeeder extends Seeder
{
    /**
     * Run the database users seeds.
     */
    public function run(): void
    {
        $user = [
            'name' => 'dev',
            'email' => 'dev@test.it',
            'password' => bcrypt('test1234'),
        ];

        DB::table('users')->insert($user);        
    }
}
