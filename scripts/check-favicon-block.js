const fs = require('fs');
const c = fs.readFileSync('C:/xampp/htdocs/sfs/poultry-ecommerce/index.html', 'utf8');
const m = c.match(/<link rel="icon"[^>]*>[\s\S]*?<link rel="apple-touch-icon"[^>]*>/);
if (m) {
  console.log(JSON.stringify(m[0]));
} else {
  console.log('no match');
}
