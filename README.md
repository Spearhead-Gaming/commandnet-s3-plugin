# Command Net S3

A [forumify](https://forumify.net) plugin giving S3 (Operations) staff — Mission
Developers, Server Admins, and Zeus/GMs — the tools they're currently missing. Sibling to
`commandnet-plugin`, which already covers ops calendaring, rosters, promotions, LOAs, and
awards; this plugin does not duplicate that.

See [`Plugin Spec.md`](./Plugin%20Spec.md) for the full scope, roadmap, and permissions
matrix, and [`Forumify S3 Plugin — Feature Tracker.md`](./Forumify%20S3%20Plugin%20—%20Feature%20Tracker.md)
for the brainstormed feature list this spec was drawn from.

Built for a specific MILSIM community's Forumify install; not a general-purpose skeleton.

## Requirements

- PHP 8.4 or newer
- A Forumify 1.3.x install
- MySQL (for migrations, once added)

## Install

```bash
composer require majesticdev/commandnet-s3-plugin
```

Then, from the Forumify install:

```bash
bin/console forumify:plugins:refresh
bin/console doctrine:migrations:migrate
```

## Status

Scaffolding only — plugin bootstrap, permissions stubs, and project tooling. No entities,
controllers, or migrations yet. Phase 1 (MVP) per the spec: briefing tool, SOP/doctrine
library, audit log.

## Permissions

Checked as `command-net-s3.<area>.<action>` (the prefix is slugged from the plugin's
display name, "Command Net S3"). Declared in `CommandNetS3Plugin::getPermissions()`;
granting them today has no effect since the features they gate don't exist yet.
