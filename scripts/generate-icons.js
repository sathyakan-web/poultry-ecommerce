const fs = require('fs');
const path = require('path');
const sharp = require('sharp');
const { default: pngToIco } = require('png-to-ico');

const src = path.join(__dirname, '..', 'images', 'sfs_icon.png');
const outDir = path.join(__dirname, '..', 'images');

const sizes = [72, 96, 128, 144, 152, 192, 384, 512];

async function main() {
  const meta = await sharp(src).metadata();
  console.log(`Source: ${meta.width}x${meta.height}`);

  for (const s of sizes) {
    const outFile = path.join(outDir, `sfs_icon-${s}x${s}.png`);
    await sharp(src)
      .resize(s, s, { fit: 'contain', kernel: 'lanczos3' })
      .png({ compressionLevel: 9, adaptiveFiltering: true })
      .toFile(outFile);
    console.log(`Generated ${path.basename(outFile)}`);
  }

  // Generate temporary PNGs for favicon.ico
  const icoSizes = [16, 32, 48];
  const tmpFiles = [];
  for (const s of icoSizes) {
    const tmp = path.join(outDir, `tmp-favicon-${s}.png`);
    await sharp(src)
      .resize(s, s, { fit: 'contain', kernel: 'lanczos3' })
      .png()
      .toFile(tmp);
    tmpFiles.push(tmp);
  }

  const icoBuffer = await pngToIco(tmpFiles);
  const icoFile = path.join(outDir, 'favicon.ico');
  fs.writeFileSync(icoFile, icoBuffer);
  console.log(`Generated favicon.ico (${icoBuffer.length} bytes)`);

  // Cleanup temp files
  for (const f of tmpFiles) {
    fs.unlinkSync(f);
  }
  console.log('Cleaned up temp files');
}

main().catch(err => {
  console.error('Error:', err);
  process.exit(1);
});
