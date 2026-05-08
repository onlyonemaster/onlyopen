/**
 * Commitlint Configuration
 * Enforces Conventional Commits format for Onlyone OneChat
 * 
 * Valid formats:
 *   feat(chat): add real-time messaging
 *   fix(auth): resolve session timeout
 *   feat(api)!: migrate to REST v2
 *   chore: update dependencies
 */

module.exports = {
  extends: ['@commitlint/config-conventional'],
  
  rules: {
    // Type must always be present
    'type-enum': [2, 'always', [
      'feat',      // New feature (→ MINOR)
      'fix',       // Bug fix (→ PATCH)
      'docs',      // Documentation only
      'style',     // Code style (formatting, etc.)
      'refactor',  // Code refactoring
      'perf',      // Performance improvement
      'test',      // Adding/updating tests
      'chore',     // Build/package/config
      'ci',        // CI/CD changes
      'build',     // Build system changes
      'revert',    // Revert previous commit
    ]],
    
    // Scope is optional but must be lowercase if present
    'scope-case': [2, 'always', 'lower-case'],
    
    // Subject must be lowercase
    'subject-case': [2, 'never', ['sentence-case', 'start-case', 'pascal-case', 'upper-case']],
    
    // Subject must not end with period
    'subject-full-stop': [2, 'never', '.'],
    
    // Max header length: 100 chars
    'header-max-length': [2, 'always', 100],
  },

  // Help messages for common mistakes
  helpUrl: 'https://www.conventionalcommits.org/en/v1.0.0/',

  // Ignore certain commits (e.g., merge commits)
  ignores: [
    (commit) => commit.startsWith('Merge '),
    (commit) => commit.startsWith('Revert "'),
  ],
};