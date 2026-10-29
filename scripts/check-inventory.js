const fs = require('fs');
for (const file of ['inventory.html', 'inventory_live.html']) {
  const c = fs.readFileSync(`C:/xampp/htdocs/sfs/poultry-ecommerce/${file}`, 'utf8');
  const m = c.match(/<link rel="icon"|rel="manifest"|rel="apple-touch-icon"/g);
  console.log(`${file}:`, m ? m.join(', ') : 'no matches');
}
