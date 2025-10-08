<?php
namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tables to ensure consistent IDs
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('transactions')->truncate();
        DB::table('users')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Create 10 test users with fixed password and random balance
        $users = [
            [
                'name'     => 'John Doe',
                'email'    => 'john@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Jane Smith',
                'email'    => 'jane@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Bob Johnson',
                'email'    => 'bob@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Alice Williams',
                'email'    => 'alice@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Charlie Brown',
                'email'    => 'charlie@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'David Miller',
                'email'    => 'david@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Emma Davis',
                'email'    => 'emma@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Frank Wilson',
                'email'    => 'frank@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Grace Taylor',
                'email'    => 'grace@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
            [
                'name'     => 'Henry Anderson',
                'email'    => 'henry@example.com',
                'password' => Hash::make('password'),
                'balance'  => rand(1000, 10000) + (rand(0, 99) / 100),
            ],
        ];

        foreach ($users as $userData) {
            User::create($userData);
        }

        $this->command->info('Created 10 test users with consistent IDs (1-10)');
        $this->command->info('Password for all users: "password"');
        $this->command->info('Balances: Random between $1000-$10000');
        $this->command->table(
            ['ID', 'Name', 'Email'],
            [
                [1, 'John Doe', 'john@example.com'],
                [2, 'Jane Smith', 'jane@example.com'],
                [3, 'Bob Johnson', 'bob@example.com'],
                [4, 'Alice Williams', 'alice@example.com'],
                [5, 'Charlie Brown', 'charlie@example.com'],
                [6, 'David Miller', 'david@example.com'],
                [7, 'Emma Davis', 'emma@example.com'],
                [8, 'Frank Wilson', 'frank@example.com'],
                [9, 'Grace Taylor', 'grace@example.com'],
                [10, 'Henry Anderson', 'henry@example.com'],
            ]
        );
    }
}
