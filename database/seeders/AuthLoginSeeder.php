<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AuthLoginSeeder extends Seeder
{
    /**
     * Seed login-ready users with roles.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'user', 'guard_name' => 'web']);

        $admin = User::updateOrCreate(
            ['email' => 'admin@socibiz.test'],
            ['name' => 'System Admin', 'password' => 'password123']
        );
        $admin->syncRoles(['admin']);

        $user = User::updateOrCreate(
            ['email' => 'user@socibiz.test'],
            ['name' => 'Demo User', 'password' => 'password123']
        );
        $user->syncRoles(['user']);
    }
}
