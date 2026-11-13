# Changelog

## [Unreleased]

## [1.0.0] - 2026-11-13

The first stable release.

### Release

- Requires `omnisense/contracts: ^1.0`, `omnisense/omnishow: ^1.0`: stable versions, so `composer
  require omnishow/wikidata` installs in a project at Composer's default minimum-stability.
  `extra.branch-alias` names 1.x (`1.x-dev`); the CI (`.github/workflows/ci.yml`, glitchr-studio/ci's
  php-package.yml) is callable, and `release.yml` runs it on a tag before glitchr-studio/ci's
  php-release.yml.

### Changed
- The policy declares `"concurrency": 1`: Wikimedia's robot policy asks an unauthenticated client
  of the Action API for one request at a time (3 overall when authenticated; read on
  wikitech.wikimedia.org/wiki/Robot_policy, 2026-11-10). Declared, not enforced: a site's client
  that sends requests in parallel reads it from the policy, and may only lower it. Needs
  omnisense/contracts at 5bac28b or later (`concurrency`; still `^1.0@dev`, untagged).
