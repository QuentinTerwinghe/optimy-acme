<?php

namespace Database\Seeders;

use App\Models\Auth\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates dedicated test users with specific roles:
     * - admin@acme.corp with 'admin' role
     * - campaign@acme.corp with 'campaign_manager' role
     * - user@acme.corp with 'user' role
     */
    public function run(): void
    {
        $users = [
            [
                'email' => 'admin@acme.corp',
                'password' => 'admin',
                'name' => 'Admin User',
                'role' => 'admin',
            ],
            [
                'email' => 'campaign@acme.corp',
                'password' => 'campaign',
                'name' => 'Campaign Manager',
                'role' => 'campaign_manager',
            ],
            [
                'email' => 'user@acme.corp',
                'password' => 'user',
                'name' => 'Regular User',
                'role' => 'user',
            ],
            [
                'email' => 'donator1@acme.corp',
                'password' => 'user',
                'name' => 'Donator User 1',
                'role' => 'user',
            ],
            [
                'email' => 'donator2@acme.corp',
                'password' => 'user',
                'name' => 'Donator User 2',
                'role' => 'user',
            ],
            [
                'email' => 'donator3@acme.corp',
                'password' => 'user',
                'name' => 'Donator User 3',
                'role' => 'user',
            ],
        ];

        $this->command->info('');
        $this->command->info('Creating test users with roles...');
        $this->command->info('');

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );

            // Assign role to user
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }

        // Display created users with their credentials
        $this->command->info('╔════════════════════════════════════════════════════════╗');
        $this->command->info('║           TEST USERS CREATED SUCCESSFULLY              ║');
        $this->command->info('╚════════════════════════════════════════════════════════╝');
        $this->command->info('');

        $this->command->table(
            ['Email', 'Password', 'Name', 'Role'],
            $users
        );

        $this->command->info('');
        $this->command->warn('⚠️  These credentials are for development only!');
        $this->command->info('');
    }
}
