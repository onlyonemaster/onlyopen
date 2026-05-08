/**
 * Inject Version Script
 * Reads version from package.json and injects into HTML files
 * Also patches "AIvote" → "Onlyone" in menu labels
 */

const fs = require('fs');
const path = require('path');

const PKG_PATH = path.join(__dirname, '..', 'package.json');
const pkg = JSON.parse(fs.readFileSync(PKG_PATH, 'utf8'));
const VERSION = pkg.version;
const APP_NAME = 'Onlyone OneChat';
const VERSION_STRING = `${APP_NAME} v${VERSION}`;
const BUILD_DATE = new Date().toISOString().split('T')[0];

console.log('');
console.log('┌─────────────────────────────────────────┐');
console.log('│   Onlyone OneChat - Version Injector    │');
console.log('├─────────────────────────────────────────┤');
console.log(`│   ${VERSION_STRING.padEnd(36)}│`);
console.log(`│   Build: ${BUILD_DATE.padEnd(30)}│`);
console.log('└─────────────────────────────────────────┘');
console.log('');

/**
 * Patch a file: replace version placeholder + AIvote → Onlyone
 */
function patchFile(filePath) {
  if (!fs.existsSync(filePath)) {
    console.log(`⚠️  Skip (not found): ${filePath}`);
    return false;
  }

  let content = fs.readFileSync(filePath, 'utf8');
  let modified = false;

  // 1. Replace version span placeholder
  const versionPattern = /<span[^>]*id=["']app-version["'][^>]*>.*?<\/span>/gi;
  if (versionPattern.test(content)) {
    content = content.replace(versionPattern, `<span id="app-version">${VERSION_STRING}</span>`);
    console.log(`   ✅ Version injected → ${VERSION_STRING}`);
    modified = true;
  }

  // 2. Replace version meta tag
  const metaPattern = /<meta\s+name=["']app-version["']\s+content=["'].*?["']\s*\/?>/gi;
  if (metaPattern.test(content)) {
    content = content.replace(metaPattern, `<meta name="app-version" content="${VERSION}">`);
    console.log(`   ✅ Meta version → ${VERSION}`);
    modified = true;
  }

  // 3. Replace "AIvote OneChat" → "Onlyone OneChat"
  const aivotePattern = /AIvote\s+OneChat/gi;
  if (aivotePattern.test(content)) {
    content = content.replace(aivotePattern, 'Onlyone OneChat');
    console.log(`   ✅ AIvote OneChat → Onlyone OneChat`);
    modified = true;
  }

  // 4. Replace standalone "AIvote" in version strings
  const aivoteVersionPattern = /AIvote\s+(OneChat\s+v[\d.]+)/gi;
  if (aivoteVersionPattern.test(content)) {
    content = content.replace(aivoteVersionPattern, `Onlyone $1`);
    console.log(`   ✅ AIvote OneChat vX.X.X → Onlyone OneChat vX.X.X`);
    modified = true;
  }

  // 5. Replace in any text content containing AIvote
  const aivoteAnyPattern = /AIvote/gi;
  const aivoteCount = (content.match(aivoteAnyPattern) || []).length;
  if (aivoteCount > 0) {
    content = content.replace(aivoteAnyPattern, 'Onlyone');
    console.log(`   ✅ AIvote → Onlyone (${aivoteCount} occurrences)`);
    modified = true;
  }

  if (modified) {
    fs.writeFileSync(filePath, content);
    return true;
  } else {
    console.log(`   ℹ️  No changes needed`);
    return false;
  }
}

// ── Main ──
const targetFiles = process.argv.slice(2);
const defaultTargets = [
  path.join(__dirname, '..', 'index.html'),
  path.join(__dirname, '..', 'dist', 'index.html'),
];

const files = targetFiles.length > 0 ? targetFiles : defaultTargets;
let patchedCount = 0;

files.forEach(file => {
  const absPath = path.resolve(file);
  console.log(`📄 Processing: ${absPath}`);
  if (patchFile(absPath)) patchedCount++;
  console.log('');
});

console.log(`🎯 Done! ${patchedCount} file(s) patched.`);
console.log(`   Version: ${VERSION_STRING}`);
console.log('');