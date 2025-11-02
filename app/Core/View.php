<?php
/**
 * View Renderer
 */

namespace App\Core;

class View
{
    private static array $shared = [];

    /**
     * Render view file
     */
    public static function render(string $view, array $data = []): void
    {
        $viewFile = self::getViewPath($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        // Extract data to variables
        extract(array_merge(self::$shared, $data));

        // Start output buffering
        ob_start();

        // Include view file
        require $viewFile;

        // Get buffer content and clean
        $content = ob_get_clean();

        echo $content;
    }

    /**
     * Render view and return as string
     */
    public static function make(string $view, array $data = []): string
    {
        $viewFile = self::getViewPath($view);

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $view");
        }

        extract(array_merge(self::$shared, $data));

        ob_start();
        require $viewFile;
        return ob_get_clean();
    }

    /**
     * Share data with all views
     */
    public static function share(string $key, $value): void
    {
        self::$shared[$key] = $value;
    }

    /**
     * Get view file path
     */
    private static function getViewPath(string $view): string
    {
        $view = str_replace('.', '/', $view);
        return __DIR__ . '/../../views/' . $view . '.php';
    }

    /**
     * Escape HTML
     */
    public static function e(?string $string): string
    {
        return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Render JSON response
     */
    public static function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Success JSON response
     */
    public static function success($data = null, string $message = 'Success', int $code = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $code);
    }

    /**
     * Error JSON response
     */
    public static function error(string $message = 'Error', $data = null, int $code = 400): void
    {
        self::json([
            'success' => false,
            'message' => $message,
            'data' => $data
        ], $code);
    }
}
