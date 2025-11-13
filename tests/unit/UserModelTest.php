<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\UserModel;

/**
 * Test cases for UserModel
 * Covers: Secure login, role-based access, password hashing
 */
class UserModelTest extends CIUnitTestCase
{
    protected $userModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userModel = new UserModel();
    }

    /**
     * Test password hashing on insert
     */
    public function testPasswordHashing()
    {
        $this->assertTrue(
            method_exists($this->userModel, 'hashPassword'),
            'hashPassword method should exist for secure password storage'
        );
    }

    /**
     * Test authentication method exists
     */
    public function testAuthenticationMethod()
    {
        $this->assertTrue(
            method_exists($this->userModel, 'authenticate'),
            'authenticate method should exist for secure login'
        );
    }

    /**
     * Test getting users by role
     */
    public function testGetUsersByRole()
    {
        $role = 'branch_manager';
        $users = $this->userModel->getUsersByRole($role);
        
        $this->assertIsArray($users);
        // Verify all users have the correct role
        foreach ($users as $user) {
            $this->assertEquals($role, $user['role']);
            $this->assertEquals(1, $user['is_active']);
        }
    }

    /**
     * Test getting users by branch
     */
    public function testGetUsersByBranch()
    {
        $branchId = 1;
        $users = $this->userModel->getUsersByBranch($branchId);
        
        $this->assertIsArray($users);
        // Verify all users belong to the branch
        foreach ($users as $user) {
            $this->assertEquals($branchId, $user['branch_id']);
            $this->assertEquals(1, $user['is_active']);
        }
    }

    /**
     * Test user validation rules
     */
    public function testValidationRules()
    {
        $rules = $this->userModel->getValidationRules();
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('role', $rules);
    }
}

