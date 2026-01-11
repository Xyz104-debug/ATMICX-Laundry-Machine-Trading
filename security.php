<?php
/**
 * ATMICX Security and Validation Utilities
 * Provides input validation, security checks, and data sanitization
 */

class ATMICXSecurity {
    
    /**
     * Validate and sanitize input data
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        $sanitized = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required field check
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "{$field} is required";
                continue;
            }
            
            // Skip validation if field is empty and not required
            if (empty($value)) {
                $sanitized[$field] = null;
                continue;
            }
            
            // Type validation
            switch ($rule['type'] ?? 'string') {
                case 'email':
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "{$field} must be a valid email";
                    } else {
                        $sanitized[$field] = filter_var($value, FILTER_SANITIZE_EMAIL);
                    }
                    break;
                    
                case 'int':
                    if (!filter_var($value, FILTER_VALIDATE_INT)) {
                        $errors[$field] = "{$field} must be a valid integer";
                    } else {
                        $sanitized[$field] = (int)$value;
                    }
                    break;
                    
                case 'float':
                    if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                        $errors[$field] = "{$field} must be a valid number";
                    } else {
                        $sanitized[$field] = (float)$value;
                    }
                    break;
                    
                case 'date':
                    if (!self::validateDate($value)) {
                        $errors[$field] = "{$field} must be a valid date (YYYY-MM-DD)";
                    } else {
                        $sanitized[$field] = $value;
                    }
                    break;
                    
                case 'phone':
                    if (!self::validatePhone($value)) {
                        $errors[$field] = "{$field} must be a valid phone number";
                    } else {
                        $sanitized[$field] = self::sanitizePhone($value);
                    }
                    break;
                    
                default: // string
                    $sanitized[$field] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
                    break;
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($sanitized[$field] ?? '') < $rule['min_length']) {
                $errors[$field] = "{$field} must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($sanitized[$field] ?? '') > $rule['max_length']) {
                $errors[$field] = "{$field} must not exceed {$rule['max_length']} characters";
            }
            
            // Enum validation
            if (isset($rule['enum']) && !in_array($value, $rule['enum'])) {
                $errors[$field] = "{$field} must be one of: " . implode(', ', $rule['enum']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $sanitized
        ];
    }
    
    /**
     * Validate file upload
     */
    public static function validateFileUpload($file, $allowedTypes = ['jpg', 'jpeg', 'png', 'pdf'], $maxSize = 5242880) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'File upload failed'];
        }
        
        if ($file['size'] > $maxSize) {
            return ['valid' => false, 'error' => 'File size exceeds limit (5MB)'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedTypes)) {
            return ['valid' => false, 'error' => 'File type not allowed. Allowed: ' . implode(', ', $allowedTypes)];
        }
        
        // Additional security checks
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'application/pdf' => ['pdf']
        ];
        
        $validMime = false;
        foreach ($allowedMimes as $mime => $extensions) {
            if ($mimeType === $mime && in_array($extension, $extensions)) {
                $validMime = true;
                break;
            }
        }
        
        if (!$validMime) {
            return ['valid' => false, 'error' => 'File type mismatch or potentially malicious file'];
        }
        
        return ['valid' => true, 'mime_type' => $mimeType, 'extension' => $extension];
    }
    
    /**
     * Generate secure filename
     */
    public static function generateSecureFilename($originalName, $prefix = '', $includeTimestamp = true) {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $timestamp = $includeTimestamp ? '_' . time() : '';
        $random = '_' . bin2hex(random_bytes(8));
        
        return $prefix . $timestamp . $random . '.' . $extension;
    }
    
    /**
     * Check rate limiting
     */
    public static function checkRateLimit($identifier, $maxRequests = 60, $timeWindow = 3600) {
        $rateLimitFile = "tmp/rate_limit_{$identifier}.json";
        
        if (!file_exists($rateLimitFile)) {
            $data = ['count' => 1, 'start_time' => time()];
            file_put_contents($rateLimitFile, json_encode($data));
            return true;
        }
        
        $data = json_decode(file_get_contents($rateLimitFile), true);
        $currentTime = time();
        
        // Reset if time window has passed
        if ($currentTime - $data['start_time'] > $timeWindow) {
            $data = ['count' => 1, 'start_time' => $currentTime];
            file_put_contents($rateLimitFile, json_encode($data));
            return true;
        }
        
        // Check if limit exceeded
        if ($data['count'] >= $maxRequests) {
            return false;
        }
        
        // Increment counter
        $data['count']++;
        file_put_contents($rateLimitFile, json_encode($data));
        return true;
    }
    
    /**
     * Validate date format
     */
    private static function validateDate($date, $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }
    
    /**
     * Validate phone number
     */
    private static function validatePhone($phone) {
        // Simple phone validation - can be enhanced based on requirements
        $pattern = '/^[\+]?[0-9\s\-\(\)]{10,15}$/';
        return preg_match($pattern, $phone);
    }
    
    /**
     * Sanitize phone number
     */
    private static function sanitizePhone($phone) {
        return preg_replace('/[^0-9\+]/', '', $phone);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
?>