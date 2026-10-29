const fs = require('fs');
const path = require('path');

const projectDir = path.join(__dirname, '..');

const filesToUpdate = [
  'index.html',
  'products.html',
  'cart.php',
  'orders.php',
  'order_details.php',
  'order_dispatch.php',
  'past_orders.php',
  'returns.php',
  'settings.php',
  'login.php',
  'qr.php',
  'qr_clean.php',
  'verified_product.php',
  'print_qr_labels.php',
  'logistics_route.php',
  'logistics_hub.php',
  'calculator.php',
  'provider_dashboard.php',
  'provider_dashboard_live.php',
  'home.html',
  'inventory.html',
  'inventory_live.html',
];

// Build a regex that matches the old block with any line endings
const oldPattern = [
  '<link rel="icon" type="image/png" href="images/sfs_icon.png">',
  '<link rel="manifest" href="manifest.json">',
  '<meta name="theme-color" content="#d4af37">',
  '<meta name="apple-mobile-web-app-capable" content="yes">',
  '<meta name="mobile-web-app-capable" content="yes">',
  '<meta name="apple-mobile-web-app-status-bar-style" content="default">',
  '<link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon.png">'
].join('\\s*');

const newBlock = [
  '<link rel="icon" type="image/x-icon" href="images/favicon.ico">',
  '<link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">',
  '<link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">',
  '<link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">',
  '<link rel="manifest" href="manifest.json">',
  '<meta name="theme-color" content="#d4af37">',
  '<meta name="apple-mobile-web-app-capable" content="yes">',
  '<meta name="mobile-web-app-capable" content="yes">',
  '<meta name="apple-mobile-web-app-status-bar-style" content="default">'
].join('\r\n  ');

const regex = new RegExp(oldPattern, 'g');

let updatedCount = 0;

for (const file of filesToUpdate) {
  const filePath = path.join(projectDir, file);
  if (!fs.existsSync(filePath)) {
    console.log(`Skipping missing: ${file}`);
    continue;
  }
  let content = fs.readFileSync(filePath, 'utf8');
  if (regex.test(content)) {
    content = content.replace(regex, newBlock);
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`Updated: ${file}`);
    updatedCount++;
  } else {
    console.log(`No match: ${file}`);
  }
}

console.log(`\nUpdated ${updatedCount} files`);
