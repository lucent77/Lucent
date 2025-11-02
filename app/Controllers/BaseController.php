<?php
/**
 * Base Controller
 * Provides common functionality for all controllers
 */

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Core\Request;

abstract class BaseController
{
    protected Auth $auth;
    protected array $config;

    public function __construct()
    {
        $this->config = require __DIR__ . '/../../config/config.php';

        // Initialize Auth
        $this->auth = Auth::getInstance($this->config);

        // Share auth instance with all views
        View::share('auth', $this->auth);
        View::share('config', $this->config);
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void
    {
        if ($this->auth->guest()) {
            if (Request::isAjax()) {
                View::error('Unauthorized', null, 401);
            } else {
                header('Location: /login');
                exit;
            }
        }
    }

    /**
     * Require permission
     */
    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();

        if ($this->auth->cannot($permission)) {
            if (Request::isAjax()) {
                View::error('Forbidden', null, 403);
            } else {
                http_response_code(403);
                View::render('errors.403');
                exit;
            }
        }
    }

    /**
     * Require role
     */
    protected function requireRole(string $role): void
    {
        $this->requireAuth();

        if (!$this->auth->hasRole($role)) {
            if (Request::isAjax()) {
                View::error('Forbidden', null, 403);
            } else {
                http_response_code(403);
                View::render('errors.403');
                exit;
            }
        }
    }

    /**
     * Verify CSRF token
     */
    protected function verifyCsrf(): void
    {
        $token = Request::input('csrf_token') ?? Request::header('X-CSRF-TOKEN');

        if (!$this->auth->verifyCsrfToken($token)) {
            if (Request::isAjax()) {
                View::error('Invalid CSRF token', null, 419);
            } else {
                die('Invalid CSRF token');
            }
        }
    }

    /**
     * Render view
     */
    protected function view(string $view, array $data = []): void
    {
        View::render($view, $data);
    }

    /**
     * Redirect
     */
    protected function redirect(string $url, int $code = 302): void
    {
        header("Location: $url", true, $code);
        exit;
    }

    /**
     * JSON response
     */
    protected function json(array $data, int $code = 200): void
    {
        View::json($data, $code);
    }

    /**
     * Success response
     */
    protected function success($data = null, string $message = 'Success'): void
    {
        View::success($data, $message);
    }

    /**
     * Error response
     */
    protected function error(string $message, $data = null, int $code = 400): void
    {
        View::error($message, $data, $code);
    }
}
