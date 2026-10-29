<?php
$src = __DIR__ . '/provider_dashboard.php';
$dst = __DIR__ . '/provider_dashboard_live.php';
$content = file_get_contents($src);

$content = preg_replace('#<button class="doodle-btn".*?onclick="syncToLive\(\)".*?</button>#s', '', $content);
$content = preg_replace('#async function syncToLive\(\) \{.*?\n\s+\}\n\s+\n#s', '', $content);

file_put_contents($dst, $content);
echo "Created: $dst\n";
