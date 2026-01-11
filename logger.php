<?php
/**
 * ATMICX Error Handler and Logger
 * Provides centralized error handling and logging functionality
 */

class ATMICXLogger {
    private static $logPath = 'logs/system/';
    
    public static function init() {
        // Set custom error handler
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        
        // Ensure log directory exists
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }
    
    public static function log($message, $level = 'INFO', $category = 'general') {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] [{$category}] {$message}\n";
        
        $filename = self::$logPath . date('Y-m-d') . '_' . $category . '.log';
        file_put_contents($filename, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    public static function logPayment($message, $payment_id = null) {
        $prefix = $payment_id ? "[Payment:{$payment_id}]" : "";
        self::log("{$prefix} {$message}", 'INFO', 'payments');
    }
    
    public static function logService($message, $service_id = null) {
        $prefix = $service_id ? "[Service:{$service_id}]" : "";
        self::log("{$prefix} {$message}", 'INFO', 'services');
    }
    
    public static function logError($message, $context = []) {
        $contextStr = $context ? ' Context: ' . json_encode($context) : '';
        self::log($message . $contextStr, 'ERROR', 'errors');
    }
    
    public static function logAPI($action, $result, $user_id = null) {
        $userStr = $user_id ? " User:{$user_id}" : "";
        $status = $result ? 'SUCCESS' : 'FAILED';
        self::log("API Action: {$action} - {$status}{$userStr}", 'INFO', 'api');
    }
    
    public static function errorHandler($severity, $message, $file, $line) {
        if (!(error_reporting() & $severity)) {
            return false;
        }
        
        $errorMsg = "PHP Error: {$message} in {$file}:{$line}";
        self::logError($errorMsg);
        
        // Don't execute PHP's internal error handler
        return true;
    }
    
    public static function exceptionHandler($exception) {
        $errorMsg = "Uncaught Exception: " . $exception->getMessage() . 
                   " in " . $exception->getFile() . ":" . $exception->getLine();
        self::logError($errorMsg);
        
        // Show user-friendly error in production
        if (!defined('DEBUG_MODE')) {
            echo json_encode(['success' => false, 'message' => 'An internal error occurred']);
        }
    }
    
    public static function getRecentLogs($category = 'general', $lines = 100) {
        $filename = self::$logPath . date('Y-m-d') . '_' . $category . '.log';
        
        if (!file_exists($filename)) {
            return [];
        }
        
        $logs = file($filename, FILE_IGNORE_NEW_LINES);
        return array_slice($logs, -$lines);
    }
}

// Initialize the logger
ATMICXLogger::init();
?>