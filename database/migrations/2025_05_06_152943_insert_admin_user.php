<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('members')->insert([
            'username' => 'admin',
            'password_hash' => Hash::make('admin123'), // Using a default password - should be changed after first login
            'full_name' => 'System Administrator',
            'role' => 'admin',
            'status' => 'active',
            'created_at' => now()
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('members')->where('username', 'admin')->delete();
    }
};
