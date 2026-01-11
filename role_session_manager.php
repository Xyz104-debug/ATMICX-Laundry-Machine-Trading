<?php
/**
 * Session Manager for Role-Based Sessions
 * Allows secretary and manager to maintain separate sessions simultaneously
 */

class RoleSessionManager {
    private static $session_names = [
        'secretary' => 'ATMICX_SEC_SESSION',
        'manager' => 'ATMICX_MGR_SESSION',
        'client' => 'ATMICX_CLIENT_SESSION'
    ];
    
    private static $current_role = null;
    
    /**
     * Start session for specific role
     */
    public static function start($role) {
        if (!in_array($role, ['secretary', 'manager', 'client'])) {
            throw new Exception("Invalid role: $role");
        }
        
        // Close any existing session
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        
        // Set session name for this role
        session_name(self::$session_names[$role]);
        
        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        self::$current_role = $role;
    }
    
    /**
     * Get current session data
     */
    public static function get($key = null) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return null;
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        return $_SESSION[$key] ?? null;
    }
    
    /**
     * Set session data
     */
    public static function set($key, $value) {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION[$key] = $value;
        }
    }
    
    /**
     * Check if user is authenticated for current role
     */
    public static function isAuthenticated() {
        return self::get('user_id') !== null && self::get('role') !== null;
    }
    
    /**
     * Get user ID
     */
    public static function getUserId() {
        return self::get('user_id');
    }
    
    /**
     * Get user role
     */
    public static function getRole() {
        return self::get('role');
    }
    
    /**
     * Get username
     */
    public static function getUsername() {
        return self::get('username');
    }
    
    /**
     * Logout current session
     */
    public static function logout() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }
    }
    
    /**
     * Login user with role
     */
    public static function login($user_id, $username, $role) {
        self::set('user_id', $user_id);
        self::set('username', $username);
        self::set('role', $role);
        self::set('login_time', time());
    }
    
    /**
     * Check if specific role session exists (without switching to it)
     */
    public static function hasActiveSession($role) {
        if (!in_array($role, ['secretary', 'manager', 'client'])) {
            return false;
        }
        
        // Store current session
        $current_session_name = session_name();
        $current_session_data = $_SESSION ?? [];
        $was_active = session_status() === PHP_SESSION_ACTIVE;
        
        if ($was_active) {
            session_write_close();
        }
        
        // Check target role session
        session_name(self::$session_names[$role]);
        session_start();
        $has_session = isset($_SESSION['user_id']) && isset($_SESSION['role']);
        $session_data = $_SESSION ?? [];
        session_write_close();
        
        // Restore original session
        if ($was_active && $current_session_name) {
            session_name($current_session_name);
            session_start();
            $_SESSION = $current_session_data;
        }
        
        return $has_session ? $session_data : false;
    }
    
    /**
     * Get current role context
     */
    public static function getCurrentRole() {
        return self::$current_role;
    }
}
?>