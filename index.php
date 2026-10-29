<?php
// Canonical redirect: /index.php -> the app folder root ("/").
// It is derived from REQUEST_URI so the app keeps its own folder in the URL
// (e.g. /sfs/poultry-ecommerce/index.php -> /sfs/poultry-ecommerce/) and never
// bounces the visitor to the server root, where the app files do not exist.
$sfsRequestPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$sfsScriptPath = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
if (preg_match('#/index\.php$#i', $sfsRequestPath) && basename($sfsScriptPath) === 'index.php') {
  $sfsDir = rtrim(str_replace('\\', '/', dirname($sfsRequestPath)), '/');
  $sfsTarget = ($sfsDir === '' || $sfsDir === '.') ? '/' : $sfsDir . '/';
  header('Location: ' . $sfsTarget, true, 301);
  exit;
}

include __DIR__ . '/home.html';
