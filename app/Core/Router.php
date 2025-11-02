<?php
/**
 * Simple Router for handling HTTP requests
 */

namespace App\Core;

class Router
{
    private array $routes = [];
    private array $middlewares = [];

    /**
     * Add GET route
     */
    public function get(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add route for any method
     */
    public function any(string $path, $handler, array $middleware = []): void
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler, $middleware);
        }
    }

    /**
     * Add route to collection
     */
    private function addRoute(string $method, string $path, $handler, array $middleware = []): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->normalizePath($path),
            'handler' => $handler,
            'middleware' => $middleware,
            'pattern' => $this->convertToRegex($path)
        ];
    }

    /**
     * Add global middleware
     */
    public function addMiddleware(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Dispatch request to appropriate handler
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = $this->getCurrentPath();

        // Handle method override for forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Find matching route
        $matchedRoute = $this->findRoute($method, $path);

        if ($matchedRoute === null) {
            $this->handleNotFound();
            return;
        }

        // Extract parameters from path
        $params = $this->extractParams($matchedRoute['pattern'], $path);

        try {
            // Execute global middlewares
            foreach ($this->middlewares as $middleware) {
                $result = call_user_func($middleware);
                if ($result === false) {
                    return; // Middleware stopped execution
                }
            }

            // Execute route-specific middlewares
            foreach ($matchedRoute['middleware'] as $middleware) {
                $result = call_user_func($middleware);
                if ($result === false) {
                    return;
                }
            }

            // Execute handler
            $handler = $matchedRoute['handler'];

            if (is_callable($handler)) {
                call_user_func_array($handler, $params);
            } elseif (is_string($handler) && strpos($handler, '@') !== false) {
                $this->handleControllerAction($handler, $params);
            } else {
                throw new \RuntimeException('Invalid route handler');
            }

        } catch (\Exception $e) {
            $this->handleError($e);
        }
    }

    /**
     * Find matching route
     */
    private function findRoute(string $method, string $path): ?array
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $path)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Handle controller@action format
     */
    private function handleControllerAction(string $handler, array $params): void
    {
        [$controllerName, $action] = explode('@', $handler);

        $controllerClass = "App\\Controllers\\" . $controllerName;

        if (!class_exists($controllerClass)) {
            throw new \RuntimeException("Controller not found: $controllerClass");
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Method not found: $controllerClass::$action");
        }

        call_user_func_array([$controller, $action], $params);
    }

    /**
     * Convert route path to regex pattern
     */
    private function convertToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = preg_quote($path, '/');

        // Convert {param} to named capture groups
        $pattern = preg_replace('/\\\{(\w+)\\\}/', '(?P<$1>[^/]+)', $pattern);

        // Convert {param?} to optional named capture groups
        $pattern = preg_replace('/\\\{(\w+)\\\?\\\}/', '(?P<$1>[^/]+)?', $pattern);

        return '/^' . $pattern . '$/';
    }

    /**
     * Extract parameters from matched path
     */
    private function extractParams(string $pattern, string $path): array
    {
        preg_match($pattern, $path, $matches);

        // Filter out numeric keys and full match
        return array_filter($matches, function($key) {
            return !is_numeric($key) && $key !== 0;
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Get current request path
     */
    private function getCurrentPath(): string
    {
        $path = $_GET['_route'] ?? '/';
        return $this->normalizePath($path);
    }

    /**
     * Normalize path (remove trailing slashes, etc.)
     */
    private function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return $path === '' ? '/' : '/' . $path;
    }

    /**
     * Handle 404 Not Found
     */
    private function handleNotFound(): void
    {
        http_response_code(404);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Route not found',
                'code' => 404
            ]);
        } else {
            require __DIR__ . '/../../views/errors/404.php';
        }
    }

    /**
     * Handle errors
     */
    private function handleError(\Exception $e): void
    {
        error_log("Router error: " . $e->getMessage());
        http_response_code(500);

        if ($this->isApiRequest()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Internal server error',
                'error' => getenv('APP_DEBUG') ? $e->getMessage() : null,
                'code' => 500
            ]);
        } else {
            require __DIR__ . '/../../views/errors/500.php';
        }
    }

    /**
     * Check if request is API request
     */
    private function isApiRequest(): bool
    {
        $path = $this->getCurrentPath();
        return strpos($path, '/api/') === 0 ||
               (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    /**
     * Redirect to URL
     */
    public static function redirect(string $url, int $code = 302): void
    {
        header("Location: $url", true, $code);
        exit;
    }

    /**
     * Get current URL
     */
    public static function getCurrentUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Generate URL for named route (simple implementation)
     */
    public static function url(string $path): string
    {
        $path = ltrim($path, '/');
        return '/' . $path;
    }
}
