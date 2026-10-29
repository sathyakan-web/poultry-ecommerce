<?php
// Diagnostic script - run on live server to check DB state.
// Safe to delete after debugging. It does NOT print passwords.
require_once __DIR__ . '/db/config.php';
require_once __DIR__ . '/api/db.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SFS DB DIAGNOSTIC ===\n";
echo "SERVER_NAME=" . ($_SERVER['SERVER_NAME'] ?? 'cli') . "\n";
echo "SFS_BASE_URL=" . SFS_BASE_URL . "\n";
echo "DB_HOST=" . $DB_HOST . "\n";
echo "DB_PORT=" . $DB_PORT . "\n";
echo "DB_NAME=" . $DB_NAME . "\n";
echo "DB_USER=" . $DB_USER . "\n";
echo "TEXTBEE_API_KEY=" . (TEXTBEE_API_KEY ? 'SET' : 'EMPTY') . "\n";
echo "TEXTBEE_DEVICE_ID=" . (TEXTBEE_DEVICE_ID ? 'SET' : 'EMPTY') . "\n";
echo "SFS_SYNC_SECRET_LEN=" . strlen(SFS_SYNC_SECRET) . "\n";
echo "PHP_VERSION=" . PHP_VERSION . "\n";
echo "password_hash_algos=" . implode(',', password_algos()) . "\n";
echo "\n";

try {
    $pdo = db();
    echo "DB_CONNECT=OK\n";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "TABLES=" . implode(", ", $tables) . "\n";

    $required = ['users', 'otp_requests', 'webauthn_credentials', 'audit_logs', 'inventory', 'products'];
    foreach ($required as $t) {
        if (!in_array($t, $tables)) echo "MISSING_TABLE=$t\n";
    }

    $usersCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "USERS_COUNT=$usersCount\n";

    $cols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
    echo "USERS_COLS=" . implode(", ", $cols) . "\n";

    $phones = $pdo->query("SELECT phone, role, biometric_enabled, LENGTH(password_hash) AS hash_len, password_hash FROM users LIMIT 20")->fetchAll();
    foreach ($phones as $u) {
        echo "USER phone=" . $u['phone'] . " role=" . $u['role'] . " bio=" . $u['biometric_enabled'] . " hash_len=" . $u['hash_len'] . " hash_prefix=" . substr($u['password_hash'], 0, 12) . "\n";
    }

    $otpCount = $pdo->query("SELECT COUNT(*) FROM otp_requests")->fetchColumn();
    echo "OTP_REQUESTS_COUNT=$otpCount\n";

    $waCount = $pdo->query("SELECT COUNT(*) FROM webauthn_credentials")->fetchColumn();
    echo "WEBAUTHN_CREDENTIALS_COUNT=$waCount\n";

    $auditCount = $pdo->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
    echo "AUDIT_LOGS_COUNT=$auditCount\n";

    // Test password_verify with a known hash to ensure password_hash works
    $testHash = password_hash('1234', PASSWORD_DEFAULT);
    $testOk = password_verify('1234', $testHash);
    echo "PASSWORD_HASH_TEST=" . ($testOk ? 'OK' : 'FAIL') . "\n";
} catch (Throwable $e) {
    echo "DB_CONNECT=FAIL err=" . $e->getMessage() . "\n";
}

echo "=== END ===\n";