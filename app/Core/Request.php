<?php
/**
 * HTTP Request Handler
 */

namespace App\Core;

class Request
{
    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Check if request is GET
     */
    public static function isGet(): bool
    {
        return self::method() === 'GET';
    }

    /**
     * Check if request is POST
     */
    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    /**
     * Check if request is PUT
     */
    public static function isPut(): bool
    {
        return self::method() === 'PUT';
    }

    /**
     * Check if request is DELETE
     */
    public static function isDelete(): bool
    {
        return self::method() === 'DELETE';
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax(): bool
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Get all input data
     */
    public static function all(): array
    {
        return array_merge($_GET, $_POST);
    }

    /**
     * Get input value
     */
    public static function input(string $key, $default = null)
    {
        return self::all()[$key] ?? $default;
    }

    /**
     * Get only specified inputs
     */
    public static function only(array $keys): array
    {
        $data = [];
        foreach ($keys as $key) {
            if (isset(self::all()[$key])) {
                $data[$key] = self::all()[$key];
            }
        }
        return $data;
    }

    /**
     * Get all except specified inputs
     */
    public static function except(array $keys): array
    {
        $data = self::all();
        foreach ($keys as $key) {
            unset($data[$key]);
        }
        return $data;
    }

    /**
     * Check if input exists
     */
    public static function has(string $key): bool
    {
        return isset(self::all()[$key]);
    }

    /**
     * Get query parameter
     */
    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get POST parameter
     */
    public static function post(string $key, $default = null)
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Get uploaded file
     */
    public static function file(string $key): ?array
    {
        return $_FILES[$key] ?? null;
    }

    /**
     * Get server variable
     */
    public static function server(string $key, $default = null)
    {
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get request header
     */
    public static function header(string $key, $default = null)
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $_SERVER[$key] ?? $default;
    }

    /**
     * Get client IP address
     */
    public static function ip(): string
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Get JSON body
     */
    public static function json(): ?array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true);
    }

    /**
     * Validate input
     */
    public static function validate(array $rules): array
    {
        $errors = [];
        $data = self::all();

        foreach ($rules as $field => $rule) {
            $ruleList = is_array($rule) ? $rule : explode('|', $rule);

            foreach ($ruleList as $r) {
                $value = $data[$field] ?? null;

                if ($r === 'required' && empty($value)) {
                    $errors[$field][] = ucfirst($field) . ' is required';
                }

                if ($r === 'email' && !empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = ucfirst($field) . ' must be a valid email';
                }

                if (strpos($r, 'min:') === 0) {
                    $min = (int)substr($r, 4);
                    if (!empty($value) && strlen($value) < $min) {
                        $errors[$field][] = ucfirst($field) . " must be at least $min characters";
                    }
                }

                if (strpos($r, 'max:') === 0) {
                    $max = (int)substr($r, 4);
                    if (!empty($value) && strlen($value) > $max) {
                        $errors[$field][] = ucfirst($field) . " must not exceed $max characters";
                    }
                }

                if ($r === 'numeric' && !empty($value) && !is_numeric($value)) {
                    $errors[$field][] = ucfirst($field) . ' must be a number';
                }
            }
        }

        if (!empty($errors)) {
            throw new \RuntimeException(json_encode($errors));
        }

        return self::only(array_keys($rules));
    }
}
