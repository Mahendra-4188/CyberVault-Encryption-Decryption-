<?php
/**
 * CyberVault - Dynamic History Logger and API Endpoint
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

// Set JSON header
header('Content-Type: application/json');

// Route Guard: Restrict access to logged-in sessions only
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// --------------------------------------------------------------------------
// 1. FETCH LOG HISTORY RECORDS (GET REQUESTS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Retrieve transaction logs for current logged-in user
        $stmt = $pdo->prepare("SELECT id, operation_type, target_name, status, created_at FROM history WHERE user_id = :uid ORDER BY created_at DESC");
        $stmt->execute(['uid' => $userId]);
        $historyLogs = $stmt->fetchAll();

        echo json_encode([
            'success' => true,
            'history' => $historyLogs
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch logs: ' . $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------------------------------
// 2. TRANSACTION LOGGING & PURGING CONTROLLER (POST REQUESTS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'log') {
        $opType = trim($_POST['operation_type'] ?? '');
        $targetName = trim($_POST['target_name'] ?? '');
        $status = trim($_POST['status'] ?? 'success');

        if (empty($opType) || empty($targetName)) {
            echo json_encode(['success' => false, 'message' => 'Logging arguments are incomplete.']);
            exit;
        }

        try {
            // Write action log to history
            $stmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, :op, :target, :status)");
            $stmt->execute([
                'uid' => $userId,
                'op' => $opType,
                'target' => $targetName,
                'status' => $status
            ]);

            echo json_encode(['success' => true, 'message' => 'Operation successfully logged in database.']);
            exit;

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to write history log: ' . $e->getMessage()]);
            exit;
        }
    }

    elseif ($action === 'clear') {
        try {
            // Clear history rows connected to current user
            $stmt = $pdo->prepare("DELETE FROM history WHERE user_id = :uid");
            $stmt->execute(['uid' => $userId]);

            // Add standard fresh system log indicating history wipe
            $logStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'system', 'Cleared Activity Logs', 'success')");
            $logStmt->execute(['uid' => $userId]);

            echo json_encode(['success' => true, 'message' => 'All history logs permanently cleared.']);
            exit;

        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to purge history logs: ' . $e->getMessage()]);
            exit;
        }
    }

    else {
        echo json_encode(['success' => false, 'message' => 'Unsupported API post operation.']);
        exit;
    }
}
?>
