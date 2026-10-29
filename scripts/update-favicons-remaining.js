const fs = require('fs');
const path = require('path');

const projectDir = path.join(__dirname, '..');

// Helper to read and replace in a file
function replaceInFile(file, oldStr, newStr) {
  const filePath = path.join(projectDir, file);
  if (!fs.existsSync(filePath)) {
    console.log(`Skipping missing: ${file}`);
    return false;
  }
  let content = fs.readFileSync(filePath, 'utf8');
  if (content.includes(oldStr)) {
    content = content.replace(oldStr, newStr);
    fs.writeFileSync(filePath, content, 'utf8');
    console.log(`Updated: ${file}`);
    return true;
  }
  console.log(`No match: ${file}`);
  return false;
}

// past_orders.php - reduced set
replaceInFile(
  'past_orders.php',
  '<link rel="icon" type="image/png" href="images/sfs_icon.png">\r\n  <link rel="manifest" href="manifest.json">\r\n  <meta name="theme-color" content="#d4af37">',
  '<link rel="icon" type="image/x-icon" href="images/favicon.ico">\r\n  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\r\n  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\r\n  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\r\n  <link rel="manifest" href="manifest.json">\r\n  <meta name="theme-color" content="#d4af37">'
);

// qr_clean.php - only has the icon tag
replaceInFile(
  'qr_clean.php',
  '<link rel="icon" type="image/png" href="images/sfs_icon.png">',
  '<link rel="icon" type="image/x-icon" href="images/favicon.ico">\r\n  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\r\n  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\r\n  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\r\n  <link rel="manifest" href="manifest.json">\r\n  <meta name="theme-color" content="#d4af37">\r\n  <meta name="apple-mobile-web-app-capable" content="yes">\r\n  <meta name="mobile-web-app-capable" content="yes">\r\n  <meta name="apple-mobile-web-app-status-bar-style" content="default">'
);

// home.html - insert favicon block before stylesheets
replaceInFile(
  'home.html',
  '  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700;900&family=Gochi+Hand&display=swap" rel="stylesheet">',
  '  <link rel="icon" type="image/x-icon" href="images/favicon.ico">\r\n  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\r\n  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\r\n  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\r\n  <link rel="manifest" href="manifest.json">\r\n  <meta name="theme-color" content="#d4af37">\r\n  <meta name="apple-mobile-web-app-capable" content="yes">\r\n  <meta name="mobile-web-app-capable" content="yes">\r\n  <meta name="apple-mobile-web-app-status-bar-style" content="default">\r\n  <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700;900&family=Gochi+Hand&display=swap" rel="stylesheet">'
);

// inventory.html - insert favicon block after title
replaceInFile(
  'inventory.html',
  '    <title>Safe Flock Solutions - Inventory Management</title>\r\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">',
  '    <title>Safe Flock Solutions - Inventory Management</title>\r\n    <link rel="icon" type="image/x-icon" href="images/favicon.ico">\r\n    <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\r\n    <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\r\n    <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\r\n    <link rel="manifest" href="manifest.json">\r\n    <meta name="theme-color" content="#d4af37">\r\n    <meta name="apple-mobile-web-app-capable" content="yes">\r\n    <meta name="mobile-web-app-capable" content="yes">\r\n    <meta name="apple-mobile-web-app-status-bar-style" content="default">\r\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">'
);

// inventory_live.html - insert favicon block after title
replaceInFile(
  'inventory_live.html',
  '    <title>Safe Flock Solutions - Inventory Management</title>\r\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">',
  '    <title>Safe Flock Solutions - Inventory Management</title>\r\n    <link rel="icon" type="image/x-icon" href="images/favicon.ico">\r\n    <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\r\n    <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\r\n    <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\r\n    <link rel="manifest" href="manifest.json">\r\n    <meta name="theme-color" content="#d4af37">\r\n    <meta name="apple-mobile-web-app-capable" content="yes">\r\n    <meta name="mobile-web-app-capable" content="yes">\r\n    <meta name="apple-mobile-web-app-status-bar-style" content="default">\r\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">'
);
