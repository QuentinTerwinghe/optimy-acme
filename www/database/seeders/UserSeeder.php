<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'User 1',
                'email' => 'user1@acme.corp',
                'password' => 'user1',
            ],
            [
                'name' => 'User 2',
                'email' => 'user2@acme.corp',
                'password' => 'user2',
            ],
            [
                'name' => 'User 3',
                'email' => 'user3@acme.corp',
                'password' => 'user3',
            ],
        ];

        $this->command->info('');
        $this->command->info('Creating test users...');
        $this->command->info('');

        foreach ($users as $userData) {
            User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                ]
            );
        }

        // Display created users with their credentials
        $this->command->info('╔════════════════════════════════════════════════════════╗');
        $this->command->info('║           TEST USERS CREATED SUCCESSFULLY              ║');
        $this->command->info('╚════════════════════════════════════════════════════════╝');
        $this->command->info('');

        $this->command->table(
            ['Email', 'Password', 'Name'],
            [
                ['user1@acme.corp', 'user1', 'User 1'],
                ['user2@acme.corp', 'user2', 'User 2'],
                ['user3@acme.corp', 'user3', 'User 3'],
            ]
        );

        $this->command->info('');
        $this->command->warn('⚠️  These credentials are for development only!');
        $this->command->info('');
    }
}
