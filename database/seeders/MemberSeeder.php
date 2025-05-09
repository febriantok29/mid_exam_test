<?php

namespace Database\Seeders;

use App\Models\Member;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        // Create regular members
        $members = [
            [
                'username' => 'johndoe',
                'full_name' => 'John Doe',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'janesmith',
                'full_name' => 'Jane Smith',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'bobwilson',
                'full_name' => 'Bob Wilson',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'alicejohnson',
                'full_name' => 'Alice Johnson',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'charliebrown',
                'full_name' => 'Charlie Brown',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'dianaross',
                'full_name' => 'Diana Ross',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'edwardkim',
                'full_name' => 'Edward Kim',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'fionagreen',
                'full_name' => 'Fiona Green',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'georgelee',
                'full_name' => 'George Lee',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
            [
                'username' => 'helenwang',
                'full_name' => 'Helen Wang',
                'password_hash' => Hash::make('password123'),
                'role' => 'member',
                'status' => 'active',
            ],
        ];

        foreach ($members as $memberData) {
            Member::create($memberData);
        }
    }
}
