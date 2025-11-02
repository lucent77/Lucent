<?php
/**
 * Simple Logger
 */

namespace App\Core;

class Logger
{
    private string $logPath;
    private string $channel;

    public function __construct(string $logPath, string $channel = 'app')
    {
        $this->logPath = $logPath;
        $this->channel = $channel;

        // Create log directory if not exists
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
    }

    /**
     * Log debug message
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('DEBUG', $message, $context);
    }

    /**
     * Log info message
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    /**
     * Log warning message
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    /**
     * Log error message
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * Log critical message
     */
    public function critical(string $message, array $context = []): void
    {
        $this->log('CRITICAL', $message, $context);
    }

    /**
     * Write log entry
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | ' . json_encode($context) : '';

        $logMessage = sprintf(
            "[%s] [%s] %s: %s%s\n",
            $date,
            $this->channel,
            $level,
            $message,
            $contextStr
        );

        $logFile = $this->logPath . '/' . $this->channel . '-' . date('Y-m-d') . '.log';

        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }

    /**
     * Create logger instance for specific channel
     */
    public static function channel(string $channel, string $logPath): Logger
    {
        return new self($logPath, $channel);
    }
}
