<?php
/**
 * Auth Controller
 * Handles authentication and user session management
 */

namespace App\Controllers;

use App\Core\Request;
use App\Core\View;

class AuthController extends BaseController
{
    /**
     * Show login page
     */
    public function showLogin(): void
    {
        if ($this->auth->check()) {
            $this->redirect('/dashboard');
        }

        $this->view('auth.login', [
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Process login
     */
    public function login(): void
    {
        $this->verifyCsrf();

        $username = Request::input('username');
        $password = Request::input('password');

        if (empty($username) || empty($password)) {
            if (Request::isAjax()) {
                $this->error('Username and password are required');
            } else {
                $_SESSION['login_error'] = 'Username and password are required';
                $this->redirect('/login');
            }
            return;
        }

        // Attempt login
        if ($this->auth->login($username, $password)) {
            if (Request::isAjax()) {
                $this->success(['redirect' => '/dashboard'], 'Login successful');
            } else {
                $this->redirect('/dashboard');
            }
        } else {
            if (Request::isAjax()) {
                $this->error('Invalid credentials', null, 401);
            } else {
                $_SESSION['login_error'] = 'Invalid username or password';
                $this->redirect('/login');
            }
        }
    }

    /**
     * Logout
     */
    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect('/login');
    }

    /**
     * Show change password page
     */
    public function showChangePassword(): void
    {
        $this->requireAuth();

        $this->view('auth.change-password', [
            'csrf_token' => $this->auth->generateCsrfToken()
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(): void
    {
        $this->requireAuth();
        $this->verifyCsrf();

        $currentPassword = Request::input('current_password');
        $newPassword = Request::input('new_password');
        $confirmPassword = Request::input('confirm_password');

        // Validate
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $this->error('All fields are required');
            return;
        }

        if ($newPassword !== $confirmPassword) {
            $this->error('New password and confirmation do not match');
            return;
        }

        if (strlen($newPassword) < 8) {
            $this->error('Password must be at least 8 characters long');
            return;
        }

        // Verify current password
        $userRepo = new \App\Repositories\UserRepository();
        $user = $userRepo->find($this->auth->id());

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $this->error('Current password is incorrect');
            return;
        }

        // Update password
        $userRepo->update($this->auth->id(), [
            'password' => $newPassword
        ]);

        $this->success(null, 'Password changed successfully');
    }

    /**
     * Get current user info (API)
     */
    public function me(): void
    {
        $this->requireAuth();

        $user = $this->auth->user();
        unset($user['password_hash']); // Don't expose password hash

        $this->success($user);
    }
}
