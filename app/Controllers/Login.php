<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\BranchModel;
use App\Models\SupplierModel;
use CodeIgniter\Controller;

class Login extends Controller
{
    protected $userModel;
    protected $branchModel;
    protected $supplierModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->branchModel = new BranchModel();
        $this->supplierModel = new SupplierModel();
    }

    public function index()
    {
        // Check if user is already logged in
        if (session()->get('user_id')) {
            return $this->redirectToDashboard();
        }

        return view('auth/login');
    }

    public function auth()
    {
        // Debug: Check if we're receiving the request
        log_message('debug', 'Login auth method called');
        
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        if (empty($username) || empty($password)) {
            return redirect()->back()->with('error', 'Please enter both username and password.');
        }

        $user = $this->userModel->authenticate($username, $password);

        if ($user) {
            // Set session data
            $sessionData = [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role'],
                'branch_id' => $user['branch_id'],
                'is_logged_in' => true
            ];

            // If user is a supplier, find and set supplier_id by matching email
            if ($user['role'] === 'supplier') {
                $supplier = $this->supplierModel->where('email', $user['email'])->first();
                if ($supplier) {
                    $sessionData['supplier_id'] = $supplier['id'];
                }
            }

            session()->set($sessionData);

            return $this->redirectToDashboard();
        } else {
            return redirect()->back()->with('error', 'Invalid username or password.');
        }
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'));
    }

    private function redirectToDashboard()
    {
        // Redirect all users to unified dashboard
        return redirect()->to(base_url('dashboard'));
    }
}
