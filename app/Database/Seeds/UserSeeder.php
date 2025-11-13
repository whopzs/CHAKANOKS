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

