<?php
/**
 * CyberVault - Secure Server-Side File Manager API (Upload, Download, Delete)
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';

// Route Guard: Restrict access to logged-in sessions only
if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit;
}

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// Create physical uploads directory if it does not exist
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// --------------------------------------------------------------------------
// 1. SECURE FILE DOWNLOAD & CATALOG LISTING CONTROLLER (GET REQUESTS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['action']) && $_GET['action'] === 'download') {
        $fileId = intval($_GET['id'] ?? 0);

        if ($fileId <= 0) {
            die("Invalid file parameter.");
        }

        try {
            // Fetch file details, ensuring it belongs to the logged-in user!
            $stmt = $pdo->prepare("SELECT * FROM files WHERE id = :fid AND user_id = :uid LIMIT 1");
            $stmt->execute(['fid' => $fileId, 'uid' => $userId]);
            $fileRecord = $stmt->fetch();

            if (!$fileRecord) {
                header('HTTP/1.1 403 Forbidden');
                die("Error: File not found or access denied.");
            }

            $physicalPath = UPLOAD_DIR . $fileRecord['encrypted_name'];

            if (!file_exists($physicalPath)) {
                header('HTTP/1.1 404 Not Found');
                die("Error: Encrypted envelope file missing on server disk.");
            }

            // Increment decrypted files stat (or log transaction)
            $histStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'decrypt', :target, 'success')");
            $histStmt->execute([
                'uid' => $userId,
                'target' => $fileRecord['original_name']
            ]);

            // Securely stream physical vault file to browser bypassing .htaccess blocks
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($fileRecord['original_name'] . '.vault') . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($physicalPath));
            
            // Clear system buffer and output file
            ob_clean();
            flush();
            readfile($physicalPath);
            exit;

        } catch (PDOException $e) {
            die("Download error: " . $e->getMessage());
        }
    } else {
        // Return files list catalog as JSON for AJAX hydration
        header('Content-Type: application/json');
        try {
            $stmt = $pdo->prepare("SELECT id, original_name, encrypted_name, file_size, algorithm, created_at FROM files WHERE user_id = :uid ORDER BY created_at DESC");
            $stmt->execute(['uid' => $userId]);
            $files = $stmt->fetchAll();
            echo json_encode([
                'success' => true,
                'files' => $files
            ]);
            exit;
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch secured catalog: ' . $e->getMessage()]);
            exit;
        }
    }
}

// Set JSON API content header for POST operations
header('Content-Type: application/json');

// --------------------------------------------------------------------------
// 2. SECURE FILE DELETE CONTROLLER (POST ACTION = DELETE)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $fileId = intval($_POST['id'] ?? 0);

    if ($fileId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid file parameter.']);
        exit;
    }

    try {
        // Fetch record to check ownership and get physical file name
        $stmt = $pdo->prepare("SELECT * FROM files WHERE id = :fid AND user_id = :uid LIMIT 1");
        $stmt->execute(['fid' => $fileId, 'uid' => $userId]);
        $fileRecord = $stmt->fetch();

        if (!$fileRecord) {
            echo json_encode(['success' => false, 'message' => 'Record not found or access denied.']);
            exit;
        }

        // Delete physical encrypted envelope file
        $physicalPath = UPLOAD_DIR . $fileRecord['encrypted_name'];
        if (file_exists($physicalPath)) {
            unlink($physicalPath);
        }

        // Delete SQL metadata record
        $delStmt = $pdo->prepare("DELETE FROM files WHERE id = :fid");
        $delStmt->execute(['fid' => $fileId]);

        // Log action in history
        $logStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'system', :target, 'success')");
        $logStmt->execute([
            'uid' => $userId,
            'target' => "Deleted " . $fileRecord['original_name']
        ]);

        echo json_encode(['success' => true, 'message' => 'Secured file permanently deleted from system.']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error during deletion: ' . $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------------------------------
// 3. SECURE FILE UPLOAD CONTROLLER (POST MULTIPART FORM)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate upload errors
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        echo json_encode(['success' => false, 'message' => 'File transfer failed. Error code: ' . $errorCode]);
        exit;
    }

    $originalName = trim($_POST['original_name'] ?? '');
    $algorithm = trim($_POST['algorithm'] ?? 'AES-GCM-256');
    $fileSize = intval($_POST['file_size'] ?? $_FILES['file']['size']);

    if (empty($originalName)) {
        echo json_encode(['success' => false, 'message' => 'Original filename metadata missing.']);
        exit;
    }

    $uploadedFile = $_FILES['file'];

    // Validate size limit
    if ($uploadedFile['size'] > MAX_FILE_SIZE) {
        echo json_encode(['success' => false, 'message' => 'File size exceeds maximum allowed system limit (50MB).']);
        exit;
    }

    // Double check extensions for security
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        echo json_encode(['success' => false, 'message' => 'File type extension is forbidden for security.']);
        exit;
    }

    // Generate unique random hash filename for local server disk storage
    // Prevents directory traversal and duplicate filename overwriting
    $hashName = md5(uniqid($userId . '_', true)) . '.vault';
    $targetPath = UPLOAD_DIR . $hashName;

    // Move uploaded encrypted binary file to physical storage
    if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
        try {
            // Write metadata record inside MySQL database catalog
            $stmt = $pdo->prepare("INSERT INTO files (user_id, original_name, encrypted_name, file_size, algorithm) VALUES (:uid, :orig, :enc, :size, :algo)");
            $stmt->execute([
                'uid' => $userId,
                'orig' => $originalName,
                'enc' => $hashName,
                'size' => $fileSize,
                'algo' => $algorithm
            ]);

            // Add success action log in history
            $histStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'encrypt', :target, 'success')");
            $histStmt->execute([
                'uid' => $userId,
                'target' => $originalName
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'File successfully encrypted and stored on WAMP server!',
                'file_record' => [
                    'id' => $pdo->lastInsertId(),
                    'original_name' => $originalName,
                    'envelope' => $originalName . '.vault',
                    'size' => $fileSize,
                    'algo' => $algorithm
                ]
            ]);
            exit;

        } catch (PDOException $e) {
            // Remove physical file if DB logging fails
            if (file_exists($targetPath)) {
                unlink($targetPath);
            }
            echo json_encode(['success' => false, 'message' => 'Server database entry failed: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save encrypted file on server disk. Check folder permissions.']);
        exit;
    }
}

// If no conditions match
echo json_encode(['success' => false, 'message' => 'Unsupported API request.']);
exit;
?>
