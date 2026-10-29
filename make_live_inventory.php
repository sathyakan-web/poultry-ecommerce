<?php
$src = __DIR__ . '/inventory.html';
$dst = __DIR__ . '/inventory_live.html';
$content = file_get_contents($src);

$content = preg_replace('#<button class="action-btn".*?id="syncToLiveBtn".*?</button>#s', '', $content);
$content = preg_replace('#async function syncToLive\(\) \{.*?\n\s+\}\n#s', '', $content);

file_put_contents($dst, $content);
echo "Created: $dst\n";
