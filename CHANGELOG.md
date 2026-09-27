# Changelog

## Unreleased

## v1.1.0

- Operation pages are now created automatically whenever an operation is saved — including
  every operation a Deployment's "Generate Operations" creates — numbered per Deployment
  (e.g. `26-10-03`) or per year if there's no Deployment. Use
  `command-net-s3:operation-pages:backfill` to create pages for existing operations.
- The server, Steam collection link, comms plan, ROE, checklist, and quick links now
  inherit in three tiers: the page's own value, then the Deployment's, then the S3-wide
  Page Defaults setting — a blank box means "not filled in." This reconciles with the
  deployment-modpack feature from v1.0.2, so modpack resolution now flows through the
  same shared-details system.
- Requires `commandnet-plugin ^1.1`.
- (Tag not yet published as a GitHub Release as of this writing.)

## v1.0.2

- Deployment modpack with versions and changelog; first version on mission create (#2)

## v1.0.1

- Improve server status query error reporting: record the last query failure reason
  (connection, timeout, or malformed reply) and show it on the admin status page instead
  of collapsing every failure into a generic "No answer" state.

## v1.0.0

- Initial release.
