/**
 * Show Current Version
 */

const versionUtils = require('./version-utils');

const current = versionUtils.getCurrentVersion();
const pkg = require('../package.json');

console.log('');
console.log('╔══════════════════════════════════╗');
console.log('║   Onlyone OneChat               ║');
console.log('╠══════════════════════════════════╣');
console.log(`║   Version : v${current.raw.padEnd(17)}║`);
console.log(`║   Name    : ${pkg.name.padEnd(19)}║`);
console.log('╚══════════════════════════════════╝');
console.log('');
console.log(`   UI Label: Onlyone OneChat v${current.raw}`);
console.log('');