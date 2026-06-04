/**
 * Version Utility Functions
 * Onlyone OneChat - Semantic Versioning Helper
 */

const fs = require('fs');
const path = require('path');

const PKG_PATH = path.join(__dirname, '..', 'package.json');

/** 현재 버전 읽기 */
function getCurrentVersion() {
  const pkg = JSON.parse(fs.readFileSync(PKG_PATH, 'utf8'));
  return {
    major: 0, minor: 0, patch: 0, ...parseSemVer(pkg.version),
    raw: pkg.version
  };
}

/** SemVer 파싱 */
function parseSemVer(versionStr) {
  const match = versionStr.match(/^(\d+)\.(\d+)\.(\d+)(?:-(.+))?(?:\+(.+))?$/);
  if (!match) throw new Error(`Invalid SemVer: ${versionStr}`);
  return {
    major: parseInt(match[1]),
    minor: parseInt(match[2]),
    patch: parseInt(match[3]),
    preRelease: match[4] || null,
    buildMeta: match[5] || null,
  };
}

/** 버전 증가 */
function bumpVersion(current, type) {
  const next = { ...current };
  switch (type) {
    case 'major':
      next.major += 1;
      next.minor = 0;
      next.patch = 0;
      break;
    case 'minor':
      next.minor += 1;
      next.patch = 0;
      break;
    case 'patch':
      next.patch += 1;
      break;
    default:
      throw new Error(`Unknown bump type: ${type}. Use major/minor/patch`);
  }
  next.raw = `${next.major}.${next.minor}.${next.patch}`;
  return next;
}

/** package.json 쓰기 */
function writeVersion(newVersion) {
  const pkg = JSON.parse(fs.readFileSync(PKG_PATH, 'utf8'));
  pkg.version = newVersion.raw;
  fs.writeFileSync(PKG_PATH, JSON.stringify(pkg, null, 2) + '\n');
  console.log(`📦 package.json: ${newVersion.raw}`);
}

/** 버전 히스토리 기록 */
function appendVersionLog(previous, next, type, note) {
  const logPath = path.join(__dirname, '..', 'VERSION_LOG.json');
  let log = [];
  if (fs.existsSync(logPath)) {
    log = JSON.parse(fs.readFileSync(logPath, 'utf8'));
  }
  log.push({
    from: previous,
    to: next,
    type: type,
    date: new Date().toISOString(),
    note: note || ''
  });
  fs.writeFileSync(logPath, JSON.stringify(log, null, 2));
  console.log(`📝 VERSION_LOG.json updated`);
}

module.exports = {
  getCurrentVersion,
  parseSemVer,
  bumpVersion,
  writeVersion,
  appendVersionLog,
};