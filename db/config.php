<?php
// DB configuration for poultry-ecommerce
// Production credentials are loaded from outside webroot (config/sfs_config.php)
// Environment variables can override any value.

$DB_HOST = getenv('DB_HOST') ?: '';
$DB_PORT = getenv('DB_PORT') ?: '';
$DB_NAME = getenv('DB_NAME') ?: '';
$DB_USER = getenv('DB_USER') ?: '';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_CHARSET = getenv('DB_CHARSET') ?: 'utf8mb4';

$isLocal = php_sapi_name() === 'cli' || (isset($_SERVER['SERVER_NAME']) && in_array($_SERVER['SERVER_NAME'], ['localhost', '127.0.0.1', '::1'], true));

// The shared config file carries the SMS gateway credentials and the
// production database credentials. It is loaded in BOTH local and production
// mode so the SMS gateway works on localhost too. Local database values are
// applied afterwards, so XAMPP keeps using its own database.
$configPaths = [
    getenv('SFS_CONFIG_PATH'),
    __DIR__ . '/../config/sfs/sfs_config.php',
    __DIR__ . '/../../config/sfs/sfs_config.php',
    __DIR__ . '/../../../config/sfs_config.php',
    __DIR__ . '/../../../config/sfs/sfs_config.php',
    __DIR__ . '/../../../../config/sfs/sfs_config.php',
  ];
  $loaded = false;
  foreach ($configPaths as $path) {
    if ($path && file_exists($path)) {
      require_once $path;
      $DB_HOST = $DB_HOST ?: ($GLOBALS['DB_HOST'] ?? '');
      $DB_PORT = $DB_PORT ?: ($GLOBALS['DB_PORT'] ?? '');
      $DB_NAME = $DB_NAME ?: ($GLOBALS['DB_NAME'] ?? '');
      $DB_USER = $DB_USER ?: ($GLOBALS['DB_USER'] ?? '');
      $DB_PASS = $DB_PASS ?: ($GLOBALS['DB_PASS'] ?? '');
      $loaded = true;
      break;
    }
  }
  if (!$loaded) {
    error_log('[config] Shared config not found at any expected path. Using env vars and built-in defaults. Checked: ' . implode(' | ', array_filter($configPaths)));
}

if ($isLocal) {
  $DB_HOST = 'localhost';
  $DB_PORT = '3306';
  $DB_NAME = 'poultry_ecommerce';
  $DB_USER = 'root';
  $DB_PASS = '';
}

if (empty($DB_HOST) || empty($DB_NAME) || empty($DB_USER)) {
  error_log('[config] Database configuration incomplete: host=' . ($DB_HOST ?? 'empty') . ' db=' . ($DB_NAME ?? 'empty') . ' user=' . ($DB_USER ?? 'empty') . ' port=' . ($DB_PORT ?? 'empty') . ' pass_set=' . ($DB_PASS !== '' ? 'yes' : 'no'));
}

// Textbee OTP SMS configuration.
// Resolution order: environment -> shared config file -> built-in fallback.
// The fallback keeps SMS working on deployments where the shared config file
// is not uploaded (it is git-ignored on purpose).
$textbeeKey = getenv('TEXTBEE_API_KEY') ?: ($GLOBALS['TEXTBEE_API_KEY'] ?? '');
$textbeeDevice = getenv('TEXTBEE_DEVICE_ID') ?: ($GLOBALS['TEXTBEE_DEVICE_ID'] ?? '');

if ($textbeeKey === '') {
  $textbeeKey = 'txb_0r283BYjaJ7uAiAIfqFFWC9TwrHUpNFA';
}
if ($textbeeDevice === '') {
  $textbeeDevice = '6a3452dd77015dcde134bb11';
}

if ($textbeeKey === '' || $textbeeDevice === '') {
  error_log('[config] TextBee API key or Device ID not configured. OTP SMS will fail.');
}

// Debug logging for config loading (self-contained so it works even
// when this file is loaded before auth/csrf.php).
if (getenv('SFS_DEBUG_LOG') || file_exists(__DIR__ . '/../debug_on')) {
  try {
    $line = date('c') . ' [config_loaded] ' . json_encode([
      'isLocal' => $isLocal,
      'host' => $DB_HOST,
      'port' => $DB_PORT,
      'db' => $DB_NAME,
      'user' => $DB_USER,
      'pass_set' => $DB_PASS !== '',
      'textbee_key_set' => $textbeeKey !== '',
      'textbee_device_set' => $textbeeDevice !== '',
      'config_loaded' => $loaded,
      'config_paths_checked' => array_values(array_filter($configPaths ?: [])),
    ], JSON_UNESCAPED_SLASHES) . PHP_EOL;
    @file_put_contents(sys_get_temp_dir() . '/sfs_debug.log', $line, FILE_APPEND);
  } catch (Throwable $e) {}
}

$baseUrlFromEnv = getenv('SFS_BASE_URL');
if ($baseUrlFromEnv !== false && $baseUrlFromEnv !== '') {
  define('SFS_BASE_URL', rtrim((string)$baseUrlFromEnv, '/'));
} elseif (isset($_SERVER['HTTP_HOST']) && $_SERVER['HTTP_HOST'] !== '') {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
  $scriptDir = str_replace('\\', '/', $scriptDir);
  if (preg_match('#/(auth|api)(/|$)#', $scriptDir)) {
    $scriptDir = dirname($scriptDir);
  }
  $baseDir = rtrim($scriptDir, '/');
  define('SFS_BASE_URL', $scheme . '://' . $_SERVER['HTTP_HOST'] . ($baseDir !== '' ? $baseDir : ''));
} else {
  define('SFS_BASE_URL', 'https://SAFEFLOCKSOLUTIONS.freedev.app');
}

// Textbee OTP SMS configuration (resolved earlier: env -> shared config -> fallback)
define('TEXTBEE_API_KEY', $textbeeKey);
define('TEXTBEE_DEVICE_ID', $textbeeDevice);
define('TEXTBEE_BASE_URL', rtrim(getenv('TEXTBEE_BASE_URL') ?: 'https://api.textbee.dev/api/v1', '/'));

// Sync secret for localhost -> live server product sync
// Keep this identical on both localhost and live server.
$syncSecret = getenv('SFS_SYNC_SECRET') ?: ($GLOBALS['SFS_SYNC_SECRET'] ?? '');
define('SFS_SYNC_SECRET', $syncSecret ?: 'PoultrySync2026!XyZ#9kLm');

// OTP behavior defaults
define('OTP_COUNTRY_CODE', '+91'); // send as +91XXXXXXXXXX
define('OTP_EXPIRES_MINUTES', 5);
define('OTP_RATELIMIT_MINUTES', 3); // max 3 OTP requests per window
define('OTP_MAX_ATTEMPTS', 5);





