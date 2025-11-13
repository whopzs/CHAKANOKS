<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Different passwords for each user:
        // admin: 'admin123'
        // bm_dvo01: 'manager123'
        // staff_dvo01: 'staff123'
        // supplier_prime: 'supplier123'
        // supplier_fresh: 'supplier123'
        // logistics_coord: 'logistics123'
        
        $users = [
            [
                'username' => 'admin',
                'email' => 'basteuy28@gmail.com',
                'password' => password_hash('admin123', PASSWORD_DEFAULT),
                'first_name' => 'Central Office',
                'last_name' => 'Admin',
                'role' => 'admin',
                'branch_id' => null,
                'is_active' => 1,
            ],
            [
                'username' => 'bm_dvo01',
                'email' => 'francismalilay@gmail.com',
                'password' => password_hash('manager123', PASSWORD_DEFAULT),
                'first_name' => 'Branch',
                'last_name' => 'Manager',
                'role' => 'branch_manager',
                'branch_id' => 1,
                'is_active' => 1,
            ],
            [
                'username' => 'staff_dvo01',
                'email' => 'staff_dvo01@chakanoks.test',
                'password' => password_hash('staff123', PASSWORD_DEFAULT),
                'first_name' => 'Inventory',
                'last_name' => 'Staff',
                'role' => 'inventory_staff',
                'branch_id' => 1,
                'is_active' => 1,
            ],
            // Supplier Accounts
            [
                'username' => 'supplier_prime',
                'email' => 'sales@primefoods.test',
                'password' => password_hash('supplier123', PASSWORD_DEFAULT),
                'first_name' => 'John',
                'last_name' => 'Smith',
                'role' => 'supplier',
                'branch_id' => null, // Suppliers don't belong to branches
                'is_active' => 1,
            ],
            [
                'username' => 'supplier_fresh',
                'email' => 'orders@freshharvest.test',
                'password' => password_hash('supplier123', PASSWORD_DEFAULT),
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'role' => 'supplier',
                'branch_id' => null,
                'is_active' => 1,
            ],
            // Logistics Coordinator Account
            [
                'username' => 'logistics_coord',
                'email' => 'logistics@chakanoks.test',
                'password' => password_hash('logistics123', PASSWORD_DEFAULT),
                'first_name' => 'Logistics',
                'last_name' => 'Coordinator',
                'role' => 'logistics_coordinator',
                'branch_id' => null, // Logistics coordinators work across all branches
                'is_active' => 1,
            ],
        ];

        // Insert or update users
        foreach ($users as $user) {
            $existing = $this->db->table('users')
                ->where('username', $user['username'])
                ->get()
                ->getRowArray();
            
            if ($existing) {    
                // Update existing user (especially password)
                $this->db->table('users')
                    ->where('username', $user['username'])
                    ->update($user);
            } else {
                // Insert new user
                $this->db->table('users')->insert($user);
            }
        }
    }
}

