<?php
/**
 * CyberVault - Dynamic Database-Backed Dashboard Workspace
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/session.php';

// Route Guard: Access allowed only for authenticated user sessions
requireLogin();

$currentUser = getCurrentUser();
$userId = $currentUser['id'];

// --------------------------------------------------------------------------
// 1. AJAX STATS DATA ENDPOINT (GET)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_stats') {
    header('Content-Type: application/json');
    try {
        // Fetch real-time count metrics
        $fileStmt = $pdo->prepare("SELECT COUNT(*) FROM files WHERE user_id = :uid");
        $fileStmt->execute(['uid' => $userId]);
        $totalFiles = $fileStmt->fetchColumn();

        $encStmt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE user_id = :uid AND operation_type = 'encrypt' AND status = 'success'");
        $encStmt->execute(['uid' => $userId]);
        $encryptedCount = $encStmt->fetchColumn();

        $decStmt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE user_id = :uid AND operation_type = 'decrypt' AND status = 'success'");
        $decStmt->execute(['uid' => $userId]);
        $decryptedCount = $decStmt->fetchColumn();

        $opsStmt = $pdo->prepare("SELECT COUNT(*) FROM history WHERE user_id = :uid");
        $opsStmt->execute(['uid' => $userId]);
        $totalOps = $opsStmt->fetchColumn();

        echo json_encode([
            'success' => true,
            'total_files' => $totalFiles,
            'encrypted_count' => $encryptedCount,
            'decrypted_count' => $decryptedCount,
            'total_ops' => $totalOps
        ]);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------------------------------
// 2. AJAX PROFILE UPDATE HANDLER (POST)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    header('Content-Type: application/json');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $defaultKey = trim($_POST['default_key'] ?? ''); // Prefill key stored in session

    if (empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Username and email cannot be blank.']);
        exit;
    }

    try {
        // Validate duplicates
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE (username = :user OR email = :email) AND id != :uid LIMIT 1");
        $checkStmt->execute(['user' => $username, 'email' => $email, 'uid' => $userId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or Email is already taken by another user.']);
            exit;
        }

        // Update database
        $stmt = $pdo->prepare("UPDATE users SET username = :user, email = :email WHERE id = :uid");
        $stmt->execute(['user' => $username, 'email' => $email, 'uid' => $userId]);

        // Save default key inside secure session
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['default_key'] = $defaultKey;

        // Log action in history
        $logStmt = $pdo->prepare("INSERT INTO history (user_id, operation_type, target_name, status) VALUES (:uid, 'system', 'Updated profile credentials', 'success')");
        $logStmt->execute(['uid' => $userId]);

        echo json_encode(['success' => true, 'message' => 'Profile configurations successfully updated!']);
        exit;

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]);
        exit;
    }
}

// --------------------------------------------------------------------------
// 3. SERVER-SIDE STATIC PRE-RENDERING FOR DASHBOARD HYDRATION
// --------------------------------------------------------------------------
try {
    // A. Stats
    $totalFiles = $pdo->query("SELECT COUNT(*) FROM files WHERE user_id = $userId")->fetchColumn();
    $encryptedCount = $pdo->query("SELECT COUNT(*) FROM history WHERE user_id = $userId AND operation_type = 'encrypt' AND status = 'success'")->fetchColumn();
    $decryptedCount = $pdo->query("SELECT COUNT(*) FROM history WHERE user_id = $userId AND operation_type = 'decrypt' AND status = 'success'")->fetchColumn();
    $totalOps = $pdo->query("SELECT COUNT(*) FROM history WHERE user_id = $userId")->fetchColumn();

    // B. Recent activities widgets
    $recentStmt = $pdo->prepare("SELECT * FROM history WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5");
    $recentStmt->execute(['uid' => $userId]);
    $recentHistory = $recentStmt->fetchAll();

    // C. File database catalog rows
    $filesStmt = $pdo->prepare("SELECT * FROM files WHERE user_id = :uid ORDER BY created_at DESC");
    $filesStmt->execute(['uid' => $userId]);
    $myFiles = $filesStmt->fetchAll();

    // D. All History tabular rows
    $allHistStmt = $pdo->prepare("SELECT * FROM history WHERE user_id = :uid ORDER BY created_at DESC");
    $allHistStmt->execute(['uid' => $userId]);
    $allHistory = $allHistStmt->fetchAll();

} catch (PDOException $e) {
    die("Database pre-rendering query failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberVault - Dashboard</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="light-mode">
    <div class="app-container" id="dashboard-view-container">
        
        <!-- Left Sidebar Navigation -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <div class="logo-icon">
                        <i data-lucide="shield-check"></i>
                    </div>
                    <span class="logo-text">Cyber<span>Vault</span></span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-links">
                    <li>
                        <button class="nav-link active" data-view="dashboard">
                            <i data-lucide="layout-dashboard"></i>
                            <span>Dashboard</span>
                        </button>
                    </li>
                    <li>
                        <button class="nav-link" data-view="encrypt">
                            <i data-lucide="lock"></i>
                            <span>Encrypt</span>
                        </button>
                    </li>
                    <li>
                        <button class="nav-link" data-view="decrypt">
                            <i data-lucide="unlock"></i>
                            <span>Decrypt</span>
                        </button>
                    </li>
                    <li>
                        <button class="nav-link" data-view="history">
                            <i data-lucide="history"></i>
                            <span>History</span>
                        </button>
                    </li>
                    <li>
                        <button class="nav-link" data-view="profile">
                            <i data-lucide="user"></i>
                            <span>Profile</span>
                        </button>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="logout.php" class="nav-link logout-btn" style="text-decoration:none;">
                    <i data-lucide="log-out"></i>
                    <span>Logout</span>
                </a>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="main-layout">
            <!-- Top Navbar -->
            <header class="navbar">
                <div class="nav-right">
                    <!-- Theme Mode Toggle Button -->
                    <button class="theme-toggle" id="theme-toggle" title="Toggle Light/Dark Mode">
                        <i data-lucide="moon" class="moon-icon"></i>
                        <i data-lucide="sun" class="sun-icon"></i>
                    </button>
                    
                    <!-- User Profile Dropdown badge -->
                    <div class="profile-badge" onclick="app.switchDashboardView('profile')" style="cursor: pointer;" title="View Security Profile">
                        <div class="avatar"><?php echo strtoupper(substr($currentUser['username'], 0, 1)); ?></div>
                        <span class="profile-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>
                    </div>
                </div>
            </header>

            <!-- Content Workspace Panels -->
            <main class="content-container">

                <!-- 1. DASHBOARD OVERVIEW VIEW -->
                <section class="content-view active" id="view-dashboard">
                    <div class="view-header">
                        <h1>Welcome back, <span class="profile-name"><?php echo htmlspecialchars($currentUser['username']); ?></span>!</h1>
                        <p>Secure files locally inside browser with database operations hosting on WAMP.</p>
                    </div>

                    <!-- Statistics Cards Grid Widgets -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon icon-blue">
                                <i data-lucide="folder"></i>
                            </div>
                            <div class="stat-details">
                                <h3 id="stat-total-files"><?php echo $totalFiles; ?></h3>
                                <span>Total Files</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-blue">
                                <i data-lucide="lock"></i>
                            </div>
                            <div class="stat-details">
                                <h3 id="stat-encrypted-files"><?php echo $encryptedCount; ?></h3>
                                <span>Encrypted Files</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-emerald">
                                <i data-lucide="unlock"></i>
                            </div>
                            <div class="stat-details">
                                <h3 id="stat-decrypted-files"><?php echo $decryptedCount; ?></h3>
                                <span>Decrypted Files</span>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon icon-emerald">
                                <i data-lucide="activity"></i>
                            </div>
                            <div class="stat-details">
                                <h3 id="stat-total-ops"><?php echo $totalOps; ?></h3>
                                <span>Total Operations</span>
                            </div>
                        </div>
                    </div>

                    <!-- Dynamic Secured Files catalog list table -->
                    <div class="dashboard-card">
                        <div class="table-header">
                            <h2>My Secured Catalog</h2>
                            <div class="table-search">
                                <i data-lucide="search"></i>
                                <input type="text" placeholder="Search files..." id="files-search">
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Envelope Name</th>
                                        <th>Original Size</th>
                                        <th>Date Encrypted</th>
                                        <th>Algorithm</th>
                                        <th class="actions-col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="my-files-tbody">
                                    <!-- Populated dynamically via AJAX App -->
                                    <tr class="table-empty">
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i data-lucide="folder-open"></i>
                                                <p>Securing files database is syncing...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- 2. ENCRYPT WORKSPACE VIEW -->
                <section class="content-view" id="view-encrypt">
                    <div class="view-header">
                        <h1>Encrypt Workspace</h1>
                        <p>Encrypt plain text blocks or physical document files locally using zero-knowledge client AES-GCM-256.</p>
                    </div>

                    <div class="dual-pane-layout">
                        <!-- Left Forms Pane -->
                        <div class="form-pane">
                            <div class="segmented-control">
                                <button class="segment-btn active" data-target="encrypt-file">
                                    <i data-lucide="file"></i> File Encryption
                                </button>
                                <button class="segment-btn" data-target="encrypt-text">
                                    <i data-lucide="type"></i> Text Encryption
                                </button>
                            </div>

                            <!-- FILE FORM -->
                            <div class="form-section active" id="encrypt-file-section">
                                <div class="form-group">
                                    <label>Select File</label>
                                    <div class="dropzone" id="enc-file-dropzone">
                                        <input type="file" id="enc-file-input" style="display:none;">
                                        <div class="dropzone-icon-circle">
                                            <i data-lucide="upload-cloud"></i>
                                        </div>
                                        <h3>Drag & Drop physical file here</h3>
                                        <p>or <span class="browse-link" style="color:var(--blue-500); font-weight:600;">click to browse</span></p>
                                        <p style="margin-top:12px; font-size:10px; color:var(--text-muted);">Supports images, audio, video, PDFs, zip archives and doc text files.</p>
                                    </div>
                                    <div class="file-preview-card" id="enc-file-preview" style="display:none;">
                                        <div class="file-icon-box">
                                            <i data-lucide="file" id="enc-preview-icon"></i>
                                        </div>
                                        <div class="file-info-box">
                                            <span class="file-name-text" id="enc-preview-name">file.pdf</span>
                                            <span class="file-size-text" id="enc-preview-size">0 KB</span>
                                        </div>
                                        <button class="remove-file-btn" id="enc-remove-file" title="Clear file">
                                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                        </button>
                                    </div>
                                    <!-- Upload Animating Progress Bar -->
                                    <div class="progress-container" id="enc-progress-container">
                                        <div class="progress-bar-wrapper">
                                            <div class="progress-bar-fill" id="enc-progress-fill"></div>
                                        </div>
                                        <div class="progress-details">
                                            <span id="enc-progress-text">Uploading...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TEXT FORM -->
                            <div class="form-section" id="encrypt-text-section">
                                <div class="form-group">
                                    <label for="enc-text-input">Plain Text Message</label>
                                    <textarea id="enc-text-input" placeholder="Type secret text message data to encrypt..." rows="5"></textarea>
                                </div>
                            </div>

                            <!-- Passphrase inputs -->
                            <div class="form-group" style="border-top: 1px solid var(--border-color); padding-top:20px;">
                                <label for="enc-password">Secret Password Passphrase</label>
                                <div class="password-input-container">
                                    <input type="password" id="enc-password" placeholder="Enter encryption key...">
                                    <button class="toggle-password-btn" type="button">
                                        <i data-lucide="eye" style="width:16px; height:16px;"></i>
                                    </button>
                                </div>
                                <span style="font-size:10px; color:var(--text-muted);">Write down this passphrase. CyberVault zero-knowledge means keys cannot be reset or recovered!</span>
                            </div>

                            <button class="btn btn-blue btn-full btn-large" id="btn-run-encrypt" style="margin-top:10px;">
                                <i data-lucide="lock"></i> Securely Encrypt Now
                            </button>
                        </div>

                        <!-- Right Results display Terminal -->
                        <div class="results-pane">
                            <div class="result-placeholder" id="enc-placeholder">
                                <div class="lock-animation-circle">
                                    <i data-lucide="lock"></i>
                                </div>
                                <h3>Encryption Workspace Terminal</h3>
                                <p>Supply details and click "Securely Encrypt Now" to run localized zero-knowledge AES on-device.</p>
                            </div>

                            <div class="result-display" id="enc-result-display">
                                <div class="result-header success">
                                    <i data-lucide="shield-check" style="width:24px; height:24px; color:var(--emerald-500);"></i>
                                    <div>
                                        <h4>Encryption Complete</h4>
                                        <span id="enc-result-metadata">AES-GCM-256</span>
                                    </div>
                                </div>

                                <!-- Text outcome -->
                                <div id="enc-text-result-body" style="display:none; flex-direction:column; gap:16px; width:100%;">
                                    <div class="form-group">
                                        <label>Encrypted Ciphertext Block (Base64)</label>
                                        <div class="ciphertext-box">
                                            <code id="enc-ciphertext-output">U2FsdGVkX...</code>
                                        </div>
                                    </div>
                                    <button class="btn btn-secondary" id="btn-copy-ciphertext">
                                        <i data-lucide="copy"></i> Copy Ciphertext
                                    </button>
                                </div>

                                <!-- File outcome -->
                                <div id="enc-file-result-body" style="display:none; width:100%;">
                                    <div class="secure-download-box">
                                        <div class="vault-file-art">
                                            <i data-lucide="file-key"></i>
                                        </div>
                                        <h4 id="enc-vault-filename">file.vault</h4>
                                        <p>Secure crypt envelope created and uploaded to WAMP uploads database folder.</p>
                                        <button class="btn btn-emerald" id="btn-download-vault">
                                            <i data-lucide="download"></i> Download Envelope (.vault)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 3. DECRYPT WORKSPACE VIEW -->
                <section class="content-view" id="view-decrypt">
                    <div class="view-header">
                        <h1>Decrypt Workspace</h1>
                        <p>Unlock GCM crypt records by loading vault files or pasting Base64 blocks locally.</p>
                    </div>

                    <div class="dual-pane-layout">
                        <!-- Left Forms Pane -->
                        <div class="form-pane">
                            <div class="segmented-control">
                                <button class="segment-btn active" data-target="decrypt-file">
                                    <i data-lucide="file-check"></i> File Decryption
                                </button>
                                <button class="segment-btn" data-target="decrypt-text">
                                    <i data-lucide="type"></i> Text Decryption
                                </button>
                            </div>

                            <!-- FILE FORM -->
                            <div class="form-section active" id="decrypt-file-section">
                                <div class="form-group">
                                    <label>Select Encrypted File</label>
                                    <div class="dropzone" id="dec-file-dropzone">
                                        <input type="file" id="dec-file-input" style="display:none;" accept=".vault">
                                        <div class="dropzone-icon-circle" style="color:var(--emerald-500); background-color:var(--emerald-50);">
                                            <i data-lucide="file-digit"></i>
                                        </div>
                                        <h3>Drag & Drop .vault file here</h3>
                                        <p>or <span class="browse-link" style="color:var(--emerald-500); font-weight:600;">click to browse</span></p>
                                    </div>
                                    <div class="file-preview-card" id="dec-file-preview" style="display:none;">
                                        <div class="file-icon-box" style="color:var(--emerald-500); background-color:var(--emerald-50);">
                                            <i data-lucide="file-key"></i>
                                        </div>
                                        <div class="file-info-box">
                                            <span class="file-name-text" id="dec-preview-name">file.vault</span>
                                            <span class="file-size-text" id="dec-preview-size">0 KB</span>
                                        </div>
                                        <button class="remove-file-btn" id="dec-remove-file" title="Clear file">
                                            <i data-lucide="trash-2" style="width:16px; height:16px;"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- TEXT FORM -->
                            <div class="form-section" id="decrypt-text-section">
                                <div class="form-group">
                                    <label for="dec-text-input">Ciphertext Block (Base64)</label>
                                    <textarea id="dec-text-input" placeholder="Paste Base64 ciphertext block to decrypt..." rows="5"></textarea>
                                </div>
                            </div>

                            <!-- Passphrase input -->
                            <div class="form-group" style="border-top: 1px solid var(--border-color); padding-top:20px;">
                                <label for="dec-password">Passphrase password Key</label>
                                <div class="password-input-container">
                                    <input type="password" id="dec-password" placeholder="Enter decryption key...">
                                    <button class="toggle-password-btn" type="button">
                                        <i data-lucide="eye" style="width:16px; height:16px;"></i>
                                    </button>
                                </div>
                            </div>

                            <button class="btn btn-emerald btn-full btn-large" id="btn-run-decrypt" style="margin-top:10px;">
                                <i data-lucide="key-round"></i> Authenticate & Decrypt
                            </button>
                        </div>

                        <!-- Right Results display Terminal -->
                        <div class="results-pane">
                            <div class="result-placeholder" id="dec-placeholder">
                                <div class="lock-animation-circle" style="color:var(--emerald-500); background-color:var(--emerald-50);">
                                    <i data-lucide="unlock"></i>
                                </div>
                                <h3>Decryption Workspace Terminal</h3>
                                <p>Supply details and click "Authenticate & Decrypt" to verify data integrity and reconstruct contents.</p>
                            </div>

                            <div class="result-display" id="dec-result-display">
                                <div class="result-header success" id="dec-result-header">
                                    <i data-lucide="shield-check" style="width:24px; height:24px;"></i>
                                    <div>
                                        <h4 id="dec-result-title">Decryption Successful</h4>
                                        <span id="dec-result-metadata">AES-GCM-256</span>
                                    </div>
                                </div>

                                <!-- Text outcome -->
                                <div id="dec-text-result-body" style="display:none; flex-direction:column; gap:16px; width:100%;">
                                    <div class="form-group">
                                        <label>Decrypted Output Plaintext</label>
                                        <div class="plaintext-result-box" id="dec-plaintext-output">
                                            decrypted message text will display here...
                                        </div>
                                    </div>
                                    <button class="btn btn-secondary" id="btn-copy-plaintext">
                                        <i data-lucide="copy"></i> Copy Output
                                    </button>
                                </div>

                                <!-- File outcome -->
                                <div id="dec-file-result-body" style="display:none; width:100%;">
                                    <div class="secure-download-box" style="background-color:var(--emerald-50); border-color:rgba(16,185,129,0.2);">
                                        <div class="vault-file-art" style="background-color:var(--bg-card); color:var(--emerald-500);">
                                            <i data-lucide="file-check"></i>
                                        </div>
                                        <h4 id="dec-restored-filename">file.pdf</h4>
                                        <p>Integrity tags validated successfully. Secured envelop decompiled.</p>
                                        <button class="btn btn-emerald" id="btn-download-restored">
                                            <i data-lucide="download"></i> Download Unlocked File
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- 4. LOG HISTORY VIEW -->
                <section class="content-view" id="view-history">
                    <div class="flex-header">
                        <div class="view-header">
                            <h1>Cryptographic Operation Logs</h1>
                            <p>Complete historical list of secure local transactions synced inside MySQL database tables.</p>
                        </div>
                        <button class="btn btn-danger-outline" id="btn-clear-history">
                            <i data-lucide="trash-2"></i> Clear History Logs
                        </button>
                    </div>

                    <div class="dashboard-card">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Operation Type</th>
                                        <th>Target Resource / File</th>
                                        <th>Action Timestamp</th>
                                        <th>Verification Status</th>
                                    </tr>
                                </thead>
                                <tbody id="history-tbody">
                                    <!-- Syncing via AJAX app.js -->
                                    <tr class="table-empty">
                                        <td colspan="4">
                                            <div class="empty-state">
                                                <i data-lucide="clock"></i>
                                                <p>History logs are loading...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- 5. ACCOUNT PROFILE CONFIGS VIEW -->
                <section class="content-view" id="view-profile">
                    <div class="view-header">
                        <h1>Security Profile Settings</h1>
                        <p>Configure user credentials and prefilled passphrase keys saved inside WAMP secure databases.</p>
                    </div>

                    <div class="profile-card">
                        <h2>Personal Configuration</h2>
                        <form id="profile-form">
                            <div class="form-group">
                                <label for="prof-user">Username</label>
                                <input type="text" id="prof-user" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prof-email">Email Address</label>
                                <input type="email" id="prof-email" name="email" value="<?php echo htmlspecialchars($currentUser['email']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="prof-default-key">Default Passphrase Key (Optional)</label>
                                <div class="password-input-container">
                                    <input type="password" id="prof-default-key" name="default_key" value="<?php echo htmlspecialchars($_SESSION['default_key'] ?? ''); ?>" placeholder="Enter prefill key for fast testing...">
                                    <button class="toggle-password-btn" type="button">
                                        <i data-lucide="eye" style="width:16px; height:16px;"></i>
                                    </button>
                                </div>
                                <span style="font-size:10px; color:var(--text-muted);">If provided, this key is prefilled inside encrypt and decrypt cards to accelerate demonstrations.</span>
                            </div>
                            <!-- Prefill key source holder hidden input -->
                            <input type="hidden" id="default-crypt-key-value" value="<?php echo htmlspecialchars($_SESSION['default_key'] ?? ''); ?>">
                            
                            <button type="submit" class="btn btn-blue" style="margin-top:10px; width:fit-content;">
                                <i data-lucide="save"></i> Save Profile Details
                            </button>
                        </form>
                    </div>
                </section>

            </main>
        </div>
    </div>

    <!-- Floating Toast container -->
    <div class="toast-container" id="toast-container"></div>

    <!-- Script Imports -->
    <script src="assets/js/crypto.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
