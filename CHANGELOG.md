# Changelog

## Unreleased (1.x)

### Changed
- The policy declares `"concurrency": 1`: Wikimedia's robot policy asks an unauthenticated client
  of the Action API for one request at a time (3 overall when authenticated; read on
  wikitech.wikimedia.org/wiki/Robot_policy, 2026-11-10). Declared, not enforced: a site's client
  that sends requests in parallel reads it from the policy, and may only lower it. Needs
  omnisense/contracts at 5bac28b or later (`concurrency`; still `^1.0@dev`, untagged).
