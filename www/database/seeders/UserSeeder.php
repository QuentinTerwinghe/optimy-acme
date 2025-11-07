<?php

namespace Database\Seeders;

use App\Models\User;
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
                'name' => 'Admin User',
                'email' => 'admin@acme.corp',
                'password' => 'admin',
                'role' => 'admin',
            ],
            [
                'name' => 'Campaign Manager',
                'email' => 'campaign@acme.corp',
                'password' => 'campaign',
                'role' => 'campaign_manager',
            ],
            [
                'name' => 'Regular User',
                'email' => 'user@acme.corp',
                'password' => 'user',
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
            [
                ['admin@acme.corp', 'admin', 'Admin User', 'admin'],
                ['campaign@acme.corp', 'campaign', 'Campaign Manager', 'campaign_manager'],
                ['user@acme.corp', 'user', 'Regular User', 'user'],
            ]
        );

        $this->command->info('');
        $this->command->warn('⚠️  These credentials are for development only!');
        $this->command->info('');
    }
}
