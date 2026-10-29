const fs = require('fs');
const path = require('path');

const projectDir = path.join(__dirname, '..');

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

// past_orders.php - uses \n line endings
replaceInFile(
  'past_orders.php',
  '<link rel="icon" type="image/png" href="images/sfs_icon.png">\n  <link rel="manifest" href="manifest.json">\n  <meta name="theme-color" content="#d4af37">',
  '<link rel="icon" type="image/x-icon" href="images/favicon.ico">\n  <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\n  <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\n  <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\n  <link rel="manifest" href="manifest.json">\n  <meta name="theme-color" content="#d4af37">'
);

// inventory.html - insert after title, before Google Fonts
replaceInFile(
  'inventory.html',
  '    <title>Safe Flock Solutions - Inventory Management</title>\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">',
  '    <title>Safe Flock Solutions - Inventory Management</title>\n    <link rel="icon" type="image/x-icon" href="images/favicon.ico">\n    <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\n    <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\n    <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\n    <link rel="manifest" href="manifest.json">\n    <meta name="theme-color" content="#d4af37">\n    <meta name="apple-mobile-web-app-capable" content="yes">\n    <meta name="mobile-web-app-capable" content="yes">\n    <meta name="apple-mobile-web-app-status-bar-style" content="default">\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">'
);

// inventory_live.html - insert after title, before Google Fonts
replaceInFile(
  'inventory_live.html',
  '    <title>Safe Flock Solutions - Inventory Management</title>\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">',
  '    <title>Safe Flock Solutions - Inventory Management</title>\n    <link rel="icon" type="image/x-icon" href="images/favicon.ico">\n    <link rel="icon" type="image/png" sizes="192x192" href="images/sfs_icon-192x192.png">\n    <link rel="icon" type="image/png" sizes="512x512" href="images/sfs_icon-512x512.png">\n    <link rel="apple-touch-icon" sizes="180x180" href="images/sfs_icon-180x180.png">\n    <link rel="manifest" href="manifest.json">\n    <meta name="theme-color" content="#d4af37">\n    <meta name="apple-mobile-web-app-capable" content="yes">\n    <meta name="mobile-web-app-capable" content="yes">\n    <meta name="apple-mobile-web-app-status-bar-style" content="default">\n    <link href="https://fonts.googleapis.com/css2?family=Comic+Neue:wght@400;700&family=Gochi+Hand&display=swap" rel="stylesheet">'
);
