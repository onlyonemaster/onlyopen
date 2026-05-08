/**
 * Release Script - Bump version with Semantic Versioning
 * Usage: node scripts/release.js [patch|minor|major] [--note="description"]
 */

const versionUtils = require('./version-utils');

const args = process.argv.slice(2);
const bumpType = args[0] || 'patch';
const noteArg = args.find(a => a.startsWith('--note='));
const note = noteArg ? noteArg.replace('--note=', '') : '';

if (!['major', 'minor', 'patch'].includes(bumpType)) {
  console.error(`❌ Invalid bump type: ${bumpType}`);
  console.error('   Usage: node scripts/release.js [patch|minor|major] [--note="description"]');
  process.exit(1);
}

function main() {
  const current = versionUtils.getCurrentVersion();
  const next = versionUtils.bumpVersion(current, bumpType);

  console.log('');
  console.log('╔══════════════════════════════════════╗');
  console.log('║   Onlyone OneChat - Release       ║');
  console.log('╠══════════════════════════════════════╣');
  console.log(`║   Previous : v${current.raw.padEnd(19)}║`);
  console.log(`║   New      : v${next.raw.padEnd(19)}║`);
  console.log(`║   Type     : ${bumpType.padEnd(21)}║`);
  console.log(`║   Date     : ${new Date().toISOString().split('T')[0].padEnd(17)}║`);
  console.log('╚══════════════════════════════════════╝');
  console.log('');

  // 1. Update package.json
  versionUtils.writeVersion(next);

  // 2. Append version log
  versionUtils.appendVersionLog(current.raw, next.raw, bumpType, note);

  // 3. Generate changelog entry
  const changelogEntry = generateChangelogEntry(current.raw, next.raw, bumpType, note);
  appendChangelog(changelogEntry);

  // 4. Display version string for UI
  console.log(`🎨 UI Version String: Onlyone OneChat v${next.raw}`);
  console.log(`✅ Release complete!`);
  console.log('');
  console.log('💡 Next steps:');
  console.log('   node scripts/inject-version.js  → Update HTML with new version');
  console.log('   git add . && git commit -m "chore(release): v${next.raw}"');
  console.log('   git tag v${next.raw}');
  console.log('');
}

function generateChangelogEntry(from, to, type, note) {
  const date = new Date().toISOString().split('T')[0];
  const typeLabels = { major: '🔴 MAJOR', minor: '🟡 MINOR', patch: '🟢 PATCH' };
  let entry = `## [${to}] - ${date}\n\n`;
  entry += `**${typeLabels[type] || type}** - v${from} → v${to}\n\n`;
  if (note) entry += `- ${note}\n`;
  entry += '\n';
  return entry;
}

function appendChangelog(entry) {
  const fs = require('fs');
  const path = require('path');
  const changelogPath = path.join(__dirname, '..', 'CHANGELOG.md');

  let content = '';
  if (fs.existsSync(changelogPath)) {
    content = fs.readFileSync(changelogPath, 'utf8');
  } else {
    content = '# Changelog\n\nAll notable changes to Onlyone OneChat will be documented in this file.\n\n';
  }

  // Insert after the header
  const lines = content.split('\n');
  const headerEnd = lines.findIndex((l, i) => i > 0 && l.startsWith('## '));
  if (headerEnd > 0) {
    lines.splice(headerEnd, 0, entry);
  } else {
    lines.push(entry);
  }
  fs.writeFileSync(changelogPath, lines.join('\n'));
  console.log(`📄 CHANGELOG.md updated`);
}

main();