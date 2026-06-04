<?php
/**
 * CyberVault - User Authentication Request Handler (JSON API Interface)
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/session.php';

// Set standard API header
header('Content-Type: application/json');

// Only allow POST actions
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Retrieve dynamic parameters
$action = $_POST['action'] ?? '';

if ($action === 'login') {
    $identifier = trim($_POST['username'] ?? ''); // Accepts username OR email
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter username and password.']);
        exit;
    }

    try {
        // Find user by username or email (using distinct parameters to prevent HY093 under native MySQL prepared statements)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username_ident OR email = :email_ident LIMIT 1");
        $stmt->execute([
            'username_ident' => $identifier,
            'email_ident' => $identifier
        ]);
        $user = $stmt->fetch();

        // Verify user exists and standard bcrypt password fits
        if ($user && password_verify($password, $user['password'])) {
            // Initiate session values
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];

            echo json_encode([
                'success' => true, 
                'message' => 'Access authorized! Redirecting...',
                'redirect' => 'dashboard.php'
            ]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid username/email or password key.']);
            exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'System authentication error: ' . $e->getMessage()]);
        exit;
    }
}

elseif ($action === 'register') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Server-side validation
    if (empty($username) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'All registration fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
        exit;
    }

    if (strlen($username) < 3 || strlen($username) > 30) {
        echo json_encode(['success' => false, 'message' => 'Username must be between 3 and 30 characters.']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Security password must be at least 6 characters long.']);
        exit;
    }

    try {
        // Check uniqueness of username and email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1");
        $stmt->execute(['username' => $username, 'email' => $email]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or Email is already registered.']);
            exit;
        }

        // Hash security password key using standard Bcrypt
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Dynamic Schema Adaptation: Detect if table has custom api_key column (highly robust with multiple fallbacks)
        $hasApiKey = false;
        $matchedApiKeyColumnName = 'api_key'; // default fallback case

        try {
            $columnsStmt = $pdo->query("DESCRIBE users");
            $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
            foreach ($columns as $column) {
                $trimmedCol = trim($column);
                if (strcasecmp($trimmedCol, 'api_key') === 0 || strcasecmp($trimmedCol, 'apikey') === 0) {
                    $hasApiKey = true;
                    $matchedApiKeyColumnName = $trimmedCol;
                    break;
                }
            }
        } catch (Exception $e) {
            // Ignore failure and try fallback
        }

        // Fallback 1: SHOW COLUMNS FROM users
        if (!$hasApiKey) {
            try {
                $columnsStmt = $pdo->query("SHOW COLUMNS FROM users");
                $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN, 0);
                foreach ($columns as $column) {
                    $trimmedCol = trim($column);
                    if (strcasecmp($trimmedCol, 'api_key') === 0 || strcasecmp($trimmedCol, 'apikey') === 0) {
                        $hasApiKey = true;
                        $matchedApiKeyColumnName = $trimmedCol;
                        break;
                    }
                }
            } catch (Exception $e) {
                // Ignore failure and try fallback 2
            }
        }

        // Fallback 2: Direct SELECT query probing (extremely reliable)
        if (!$hasApiKey) {
            try {
                $pdo->query("SELECT api_key FROM users LIMIT 1");
                $hasApiKey = true;
                $matchedApiKeyColumnName = 'api_key';
            } catch (Exception $e) {
                try {
                    $pdo->query("SELECT apikey FROM users LIMIT 1");
                    $hasApiKey = true;
                    $matchedApiKeyColumnName = 'apikey';
                } catch (Exception $ex) {
                    // Column definitely does not exist
                }
            }
        }

        if ($hasApiKey) {
            // Generate a secure unique API key
            $apiKey = bin2hex(random_bytes(16));

            // Save new user profile including API Key (using exact casing matched from database)
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, `$matchedApiKeyColumnName`) VALUES (:username, :email, :password, :api_key)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'api_key' => $apiKey
            ]);
        } else {
            // Save new user profile normally
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (:username, :email, :password)");
            $stmt->execute([
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword
            ]);
        }

        $newUserId = $pdo->lastInsertId();

        // Instantly authenticate the user session
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;

        // Log initial registration activity
        $histStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'system', 'Account Registration', 'success')");
        $histStmt->execute(['uid' => $newUserId]);

        echo json_encode([
            'success' => true,
            'message' => 'Registration successful! Preparing dashboard...',
            'redirect' => 'dashboard.php'
        ]);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()]);
        exit;
    }
}

else {
    echo json_encode(['success' => false, 'message' => 'Unsupported action requested.']);
    exit;
}
?>
