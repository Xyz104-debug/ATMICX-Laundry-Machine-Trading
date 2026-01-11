<?php
session_start();

if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'set_manager':
            $_SESSION['user_id'] = 1;
            $_SESSION['username'] = 'Manager User';
            $_SESSION['role'] = 'manager';
            echo "<p style='color: green;'>✅ Session set to Manager</p>";
            break;
            
        case 'set_secretary':
            $_SESSION['user_id'] = 2;
            $_SESSION['username'] = 'Secretary User';
            $_SESSION['role'] = 'secretary';
            echo "<p style='color: green;'>✅ Session set to Secretary</p>";
            break;
            
        case 'clear':
            session_destroy();
            session_start();
            echo "<p style='color: orange;'>🔄 Session cleared</p>";
            break;
    }
}

echo "<h2>Session Management Tools</h2>";
echo "<div style='margin: 20px 0;'>";
echo "<a href='?action=set_manager' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Set Manager Session</a>";
echo "<a href='?action=set_secretary' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>Set Secretary Session</a>";
echo "<a href='?action=clear' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Clear Session</a>";
echo "</div>";

echo "<h3>Current Session:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Test Access:</h3>";
echo "<p><a href='atmicxMANAGER.php' target='_blank'>→ Manager Interface</a></p>";
echo "<p><a href='armicxSECRETARY.php' target='_blank'>→ Secretary Interface</a></p>";
?>