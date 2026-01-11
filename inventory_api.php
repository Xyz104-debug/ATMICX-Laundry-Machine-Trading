<?php
error_reporting(E_ALL & ~E_NOTICE);
require_once 'role_session_manager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Tighten in production
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Check for authentication using RoleSessionManager
// Start manager session (page should already have started it)
RoleSessionManager::start('manager');

$user_id = RoleSessionManager::getUserId();
$user_role = RoleSessionManager::getRole();

// Basic auth: only logged-in staff may access.
// - Managers: full read/write
// - Secretaries: read-only (GET only)
if (!$user_id || !in_array($user_role, ['manager', 'secretary'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please log in as secretary or manager']);
    exit;
}

if (!extension_loaded('pdo')) {
    echo json_encode(['success' => false, 'message' => 'PDO extension not loaded']);
    exit;
}

$host = 'localhost';
$dbname = 'atmicxdb';
$username_db = 'root';
$password_db = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

function inv_error($message, $code = 400)
{
    http_response_code($code);
    echo json_encode(['success' => false, 'message' => $message]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // List stock, optionally filtered by branch
    $branch = isset($_GET['branch']) ? trim($_GET['branch']) : '';

    if ($branch !== '') {
        $stmt = $pdo->prepare("SELECT Item_ID, Item_Name, Quantity, Branch FROM inventory WHERE Branch = :branch ORDER BY Item_Name ASC");
        $stmt->execute([':branch' => $branch]);
    } else {
        $stmt = $pdo->query("SELECT Item_ID, Item_Name, Quantity, Branch FROM inventory ORDER BY Branch, Item_Name ASC");
    }

    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'items'   => $items
    ]);
    exit;
}

if ($method === 'POST') {
    // Secretaries are not allowed to modify inventory
    if ($user_role !== 'manager') {
        inv_error('Forbidden - Only managers can modify inventory', 403);
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        inv_error('Invalid JSON payload');
    }

    $action = $input['action'] ?? '';

    switch ($action) {
        case 'receive':
            $itemName = trim($input['item_name'] ?? '');
            $qty      = (int)($input['quantity'] ?? 0);
            $branch   = trim($input['branch'] ?? 'Manila HQ');

            if ($itemName === '' || $qty <= 0) {
                inv_error('Item name and positive quantity are required');
            }

            // Upsert inventory row for this item+branch
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("SELECT Item_ID, Quantity FROM inventory WHERE Item_Name = :name AND Branch = :branch LIMIT 1");
                $stmt->execute([
                    ':name'   => $itemName,
                    ':branch' => $branch
                ]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existing) {
                    $newQty = (int)$existing['Quantity'] + $qty;
                    $update = $pdo->prepare("UPDATE inventory SET Quantity = :qty WHERE Item_ID = :id");
                    $update->execute([
                        ':qty' => $newQty,
                        ':id'  => $existing['Item_ID']
                    ]);
                    $itemId = (int)$existing['Item_ID'];
                } else {
                    $userId = $_SESSION['user_id'] ?? null;
                    $insert = $pdo->prepare("INSERT INTO inventory (Item_Name, Quantity, Branch, User_ID) VALUES (:name, :qty, :branch, :user_id)");
                    $insert->execute([
                        ':name'    => $itemName,
                        ':qty'     => $qty,
                        ':branch'  => $branch,
                        ':user_id' => $userId
                    ]);
                    $itemId = (int)$pdo->lastInsertId();
                    $newQty = $qty;
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Stock received',
                    'item'    => [
                        'Item_ID'   => $itemId,
                        'Item_Name' => $itemName,
                        'Quantity'  => $newQty,
                        'Branch'    => $branch
                    ]
                ]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                inv_error('Failed to receive stock: ' . $e->getMessage(), 500);
            }

        case 'transfer':
            $itemName   = trim($input['item_name'] ?? '');
            $qty        = (int)($input['quantity'] ?? 0);
            $fromBranch = trim($input['from_branch'] ?? 'Manila HQ');
            $toBranch   = trim($input['to_branch'] ?? '');

            if ($itemName === '' || $qty <= 0 || $toBranch === '') {
                inv_error('Item, positive quantity and destination branch are required');
            }
            if ($fromBranch === $toBranch) {
                inv_error('Source and destination branches must be different');
            }

            $pdo->beginTransaction();
            try {
                // Get source row
                $stmt = $pdo->prepare("SELECT Item_ID, Quantity FROM inventory WHERE Item_Name = :name AND Branch = :branch LIMIT 1");
                $stmt->execute([
                    ':name'   => $itemName,
                    ':branch' => $fromBranch
                ]);
                $source = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$source || (int)$source['Quantity'] < $qty) {
                    throw new Exception('Not enough stock in source branch');
                }

                // Decrement source
                $newSourceQty = (int)$source['Quantity'] - $qty;
                $updateSrc = $pdo->prepare("UPDATE inventory SET Quantity = :qty WHERE Item_ID = :id");
                $updateSrc->execute([
                    ':qty' => $newSourceQty,
                    ':id'  => $source['Item_ID']
                ]);

                // Upsert destination
                $stmtDest = $pdo->prepare("SELECT Item_ID, Quantity FROM inventory WHERE Item_Name = :name AND Branch = :branch LIMIT 1");
                $stmtDest->execute([
                    ':name'   => $itemName,
                    ':branch' => $toBranch
                ]);
                $dest = $stmtDest->fetch(PDO::FETCH_ASSOC);

                if ($dest) {
                    $newDestQty = (int)$dest['Quantity'] + $qty;
                    $updateDest = $pdo->prepare("UPDATE inventory SET Quantity = :qty WHERE Item_ID = :id");
                    $updateDest->execute([
                        ':qty' => $newDestQty,
                        ':id'  => $dest['Item_ID']
                    ]);
                } else {
                    $insertDest = $pdo->prepare("INSERT INTO inventory (Item_Name, Quantity, Branch) VALUES (:name, :qty, :branch)");
                    $insertDest->execute([
                        ':name'   => $itemName,
                        ':qty'    => $qty,
                        ':branch' => $toBranch
                    ]);
                    $newDestQty = $qty;
                }

                $pdo->commit();

                echo json_encode([
                    'success' => true,
                    'message' => 'Transfer completed',
                    'from'    => [
                        'Branch'   => $fromBranch,
                        'ItemName' => $itemName,
                        'Quantity' => $newSourceQty
                    ],
                    'to'      => [
                        'Branch'   => $toBranch,
                        'ItemName' => $itemName,
                        'Quantity' => $newDestQty
                    ]
                ]);
                exit;
            } catch (Exception $e) {
                $pdo->rollBack();
                inv_error('Transfer failed: ' . $e->getMessage(), 400);
            }

        default:
            inv_error('Unknown action', 400);
    }
}

inv_error('Method not allowed', 405);


