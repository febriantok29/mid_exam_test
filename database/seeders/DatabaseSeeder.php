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
        // We don't need to seed admin user here as it's already handled by the migration
        
        $this->call([
            MemberSeeder::class,
            BookSeeder::class,
            BorrowingSeeder::class,
        ]);
    }
}
