<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        $roles = [
            ['role_name' => 'Manager'],
            ['role_name' => 'Supervisor'],
            ['role_name' => 'Waiter'],
            ['role_name' => 'Cashier'],
            ['role_name' => 'Kitchen'],
            ['role_name' => 'Supplier'],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['role_name' => $role['role_name']], $role);
        }
    }
}
