/**
 * Version History Viewer
 * Display full version history from VERSION_LOG.json
 */

const fs = require('fs');
const path = require('path');

const logPath = path.join(__dirname, '..', 'VERSION_LOG.json');

if (!fs.existsSync(logPath)) {
  console.log('📭 No version history found. Make your first release!');
  console.log('   Run: node scripts/release.js patch --note="Initial release"');
  process.exit(0);
}

const log = JSON.parse(fs.readFileSync(logPath, 'utf8'));

console.log('');
console.log('╔══════════════════════════════════════════════════════════╗');
console.log('║   Onlyone OneChat - Version History                    ║');
console.log('╠══════════════════════════════════════════════════════════╣');
console.log('║   #   │ From       │ To         │ Type     │ Date       ║');
console.log('╠══════════════════════════════════════════════════════════╣');

log.forEach((entry, i) => {
  const idx = String(i + 1).padStart(3);
  const from = entry.from.padEnd(10);
  const to = entry.to.padEnd(10);
  const type = entry.type.padEnd(8);
  const date = entry.date.split('T')[0].padEnd(10);
  console.log(`║  ${idx}  │ ${from} │ ${to} │ ${type} │ ${date} ║`);
  if (entry.note) {
    console.log(`║       └─ ${entry.note.substring(0, 42).padEnd(42)} ║`);
  }
});

console.log('╚══════════════════════════════════════════════════════════╝');
console.log('');
console.log(`   Total releases: ${log.length}`);
console.log('');