<?php
// backend/utils/logger.php
// Simple file-based error logger for the application
// Logs errors to backend/logs/error.log

class Logger {
    private static $logFile;

    /**
     * Initialize the log file path
     */
    private static function init() {
        if (!self::$logFile) {
            $logDir = realpath(__DIR__ . '/..') . '/logs';
            // Create logs directory if it doesn't exist
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            self::$logFile = $logDir . '/error.log';
        }
    }

    /**
     * Log an error message
     * @param string $message Error message
     * @param string $context Additional context (e.g., file name, function)
     */
    public static function error($message, $context = '') {
        self::init();
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [ERROR] $context: $message" . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Log an info message
     * @param string $message Info message
     * @param string $context Additional context
     */
    public static function info($message, $context = '') {
        self::init();
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [INFO] $context: $message" . PHP_EOL;
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}
?>
