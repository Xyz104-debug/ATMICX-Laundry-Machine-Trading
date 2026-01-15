<?php
// Direct test of the API without going through JavaScript
session_start();

// Check if session exists
echo "<h2>Session Debug</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session data:\n";
print_r($_SESSION);
echo "</pre>";

// Try to call the API directly
echo "<h2>Direct API Test</h2>";

// Set client session if not set
if (!isset($_SESSION['client_id'])) {
    $_SESSION['client_id'] = 1;
    $_SESSION['role'] = 'client';
    echo "<p>⚠️ Set test client_id = 1</p>";
}

// Make the request
$url = 'http://localhost/ATMICX-Laundry-Machine-Trading/client_quotations_api.php?action=get_quotations';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h3>Response Headers:</h3>";
echo "<pre>" . htmlspecialchars($header) . "</pre>";

echo "<h3>HTTP Status Code: " . $http_code . "</h3>";

echo "<h3>Response Body:</h3>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Try to decode JSON
$json = json_decode($body, true);
if ($json) {
    echo "<h3>Decoded JSON:</h3>";
    echo "<pre>";
    print_r($json);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Failed to decode JSON response</p>";
}

// Check logs
echo "<h2>Recent Log Entries</h2>";
$logFile = __DIR__ . '/logs/system/api_errors.log';
if (file_exists($logFile)) {
    $logs = file($logFile);
    $recentLogs = array_slice($logs, -10);
    echo "<pre>";
    foreach ($recentLogs as $log) {
        echo htmlspecialchars($log);
    }
    echo "</pre>";
} else {
    echo "<p>No log file found at: " . $logFile . "</p>";
}
?>
