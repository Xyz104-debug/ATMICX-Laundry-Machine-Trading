<?php
require_once 'role_session_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

$role = $_GET['role'] ?? '';

if (!in_array($role, ['manager', 'secretary'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

try {
    // Check if this role has an active session
    $sessionData = RoleSessionManager::hasActiveSession($role);
    
    if ($sessionData && isset($sessionData['user_id'])) {
        echo json_encode([
            'success' => true,
            'active' => true,
            'role' => $role,
            'user_id' => $sessionData['user_id'],
            'username' => $sessionData['username'] ?? 'Unknown'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'active' => false,
            'role' => $role
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'active' => false,
        'message' => $e->getMessage()
    ]);
}
?>