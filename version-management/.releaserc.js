/**
 * Semantic Release Configuration
 * Onlyone OneChat - Automated Version Management
 *
 * This config is for production use with semantic-release.
 * It automatically:
 *   1. Analyzes commit messages (Conventional Commits)
 *   2. Determines next version (SemVer)
 *   3. Generates release notes
 *   4. Updates CHANGELOG.md
 *   5. Creates Git tag
 *   6. Publishes GitHub Release
 */

module.exports = {
  // Only release from main branch
  branches: ['main'],
  
  plugins: [
    // Step 1: Analyze commits to determine version bump
    [
      '@semantic-release/commit-analyzer',
      {
        preset: 'conventionalcommits',
        releaseRules: [
          { type: 'feat', release: 'minor' },
          { type: 'fix', release: 'patch' },
          { type: 'perf', release: 'patch' },
          { type: 'refactor', release: 'patch' },
          { type: 'docs', release: false },
          { type: 'style', release: false },
          { type: 'test', release: false },
          { type: 'chore', release: false },
          { type: 'ci', release: false },
          { type: 'build', release: false },
          // Breaking changes in any commit → major
          { breaking: true, release: 'major' },
        ],
      },
    ],

    // Step 2: Generate release notes from commits
    [
      '@semantic-release/release-notes-generator',
      {
        preset: 'conventionalcommits',
        presetConfig: {
          types: [
            { type: 'feat', section: '✨ New Features' },
            { type: 'fix', section: '🐛 Bug Fixes' },
            { type: 'perf', section: '⚡ Performance Improvements' },
            { type: 'refactor', section: '♻️ Refactoring' },
            { type: 'docs', section: '📚 Documentation' },
            { type: 'style', section: '💄 Styling' },
            { type: 'test', section: '🧪 Tests' },
            { type: 'chore', section: '🔧 Maintenance' },
            { type: 'ci', section: '🔄 CI/CD' },
            { type: 'build', section: '🏗️ Build System' },
          ],
        },
      },
    ],

    // Step 3: Update CHANGELOG.md
    [
      '@semantic-release/changelog',
      {
        changelogFile: 'CHANGELOG.md',
        changelogTitle: '# Onlyone OneChat Changelog\n\nAll notable changes to this project will be documented here.',
      },
    ],

    // Step 4: Update version in package.json
    [
      '@semantic-release/npm',
      {
        npmPublish: false, // We're not publishing to npm
      },
    ],

    // Step 5: Inject version into HTML before committing
    [
      '@semantic-release/exec',
      {
        prepareCmd: 'node scripts/inject-version.js',
        successCmd: 'echo "🎉 Released ${nextRelease.version} - Onlyone OneChat v${nextRelease.version}"',
      },
    ],

    // Step 6: Commit updated files back (package.json, CHANGELOG.md, HTML)
    [
      '@semantic-release/git',
      {
        assets: [
          'package.json',
          'CHANGELOG.md',
          'index.html',
          'dist/**/*.html',
        ],
        message: 'chore(release): ${nextRelease.version}\n\n[skip ci]\n\n🎉 Onlyone OneChat v${nextRelease.version}',
      },
    ],

    // Step 7: Create GitHub Release with auto-generated notes
    [
      '@semantic-release/github',
      {
        successComment: '🎉 This release is available as **Onlyone OneChat v${nextRelease.version}**',
        releasedLabels: ['released', 'Onlyone OneChat v${nextRelease.version}'],
      },
    ],
  ],
};