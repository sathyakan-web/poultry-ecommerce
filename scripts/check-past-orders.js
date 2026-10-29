const fs = require('fs');
const c = fs.readFileSync('C:/xampp/htdocs/sfs/poultry-ecommerce/past_orders.php', 'utf8');
const m = c.match(/<link rel="icon"[^>]*>[\s\S]*?<meta name="theme-color"/);
if (m) {
  console.log(JSON.stringify(m[0]));
} else {
  console.log('no match');
}
