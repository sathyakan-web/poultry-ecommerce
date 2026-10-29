<?php
require_once __DIR__ . '/../db/config.php';

function db(): PDO {
  global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS, $DB_CHARSET;
  static $pdo = null;
  if ($pdo) return $pdo;

  $dsn = "mysql:host={$DB_HOST};port={$DB_PORT};dbname={$DB_NAME};charset={$DB_CHARSET}";
  try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    if (function_exists('sfs_debug_log')) {
      sfs_debug_log('db_connect_ok', ['host' => $DB_HOST, 'port' => $DB_PORT, 'db' => $DB_NAME, 'user' => $DB_USER]);
    }
    return $pdo;
  } catch (Throwable $e) {
    if (function_exists('sfs_debug_log')) {
      sfs_debug_log('db_connect_fail', ['host' => $DB_HOST, 'port' => $DB_PORT, 'db' => $DB_NAME, 'user' => $DB_USER, 'err' => $e->getMessage()]);
    }
    throw $e;
  }
}


