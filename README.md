# Command Net S3

A [forumify](https://forumify.net) plugin giving S3 (Operations) staff — Mission
Developers, Server Admins, and Zeus/GMs — the tools they're currently missing. Sibling to
`commandnet-plugin`, which already covers ops calendaring, rosters, promotions, LOAs, and
awards; this plugin builds on it rather than duplicating it.

See [`Plugin Spec.md`](./Plugin%20Spec.md) for the full scope, roadmap, and permissions
matrix, and [`Forumify S3 Plugin — Feature Tracker.md`](./Forumify%20S3%20Plugin%20—%20Feature%20Tracker.md)
for the brainstormed feature list the spec was drawn from.

Built for a specific MILSIM community's Forumify install; not a general-purpose skeleton.

## Requirements

- PHP 8.4 or newer
- A Forumify 1.3.x install
- [`commandnet-plugin`](https://github.com/Spearhead-Gaming/commandnet-plugin) — required.
  Briefings hang off its `Operation`, and the loadout check reads its `Equipment`,
  `Position` and `Unit` data.
- MySQL
- Optional: [`commandnet-discord-plugin`](https://github.com/Spearhead-Gaming/commandnet-discord-plugin)
  for Discord announcements. Without it everything else works and the announcers simply
  aren't loaded.

## Install

```bash
composer require majesticdev/commandnet-s3-plugin
```

Then, from the Forumify install:

```bash
bin/console forumify:plugins:refresh
bin/console forumify:plugins:activate majesticdev/commandnet-s3-plugin
bin/console doctrine:migrations:migrate
```

## Status

Phases 1 and 2 of the spec are built. Phase 3 has not been started.

| Phase | Module | State |
| --- | --- | --- |
| 1 | Briefing tool | Built, except the map image upload |
| 1 | SOP / doctrine library | Built |
| 1 | Audit log | Built, S3 activity only (see below) |
| 2 | Zeus asset library | Built, admin only |
| 2 | Loadout tracker | Built |
| 2 | Discord announcements | Built, off by default |
| 3 | Live mission notes / AAR quick-capture | Not started |
| 3 | Mission upload & version repository | Not started, blocked on where mission files live |
| 3 | Mission testing / feedback log | Not started |
| 3 | Server status / performance dashboard | Not started |
| 3 | Mod / addon version tracker | Not started |

Nothing here has automated tests, and it has only been checked statically (container,
Twig and syntax lint, schema drift) plus a few targeted runs of the parsing logic. Open
the admin pages with some test data before relying on it.

## Modules

### Briefings

A structured briefing per Command Net operation: mission name, task/purpose, ordered
objectives (one per line) and a status of Draft, Ready or Delivered. It extends the free-form
OPORD body Command Net's `Operation` already has.

- Admin: **Command Net S3 → Briefings**.
- Players: `/operations/{id}/briefing`. A player sees it only when the status is Ready or
  Delivered **and** they have an Attending or Maybe RSVP on the operation, since Command
  Net has no slot model. Staff with `briefing.manage` see any status. Everyone else gets a
  404, so Drafts aren't leaked.
- Command Net's operation page shows a "View briefing" button when this plugin is
  installed and the user may view briefings.

### SOP / doctrine library

Documents with versions and a changelog per version. A document's newest version is its
current one, and members acknowledge a specific version, so publishing a new one asks
everyone to read it again.

- Members: `/sops` lists documents with their acknowledgment status, and `/sops/{id}`
  shows the current version, the changelog history and an acknowledge button.
- Admin: **SOP Library** and **SOP Versions**. The users icon on a document shows which
  enlisted members (anyone not Discharged or Retired) haven't acknowledged the current
  version, and who has.
- Editing a version's text does not reset its acknowledgements; publish a new version for
  that.

### Zeus asset library

A catalog of terrain, faction, vehicle and misc asset packs with a mod link and notes.
Admin only: **Zeus Assets**. The table's search box also matches category, so typing
"terrain" filters by tag.

### Loadout tracker

Command Net already holds the standing rules (the weapons each Position may use, the
vehicles each Unit fields, and Equipment class names), so this adds only the missing parts:

- **Kit Approvals**: equipment approved for one specific operation.
- **Loadout Check**: pick an operation, paste a kit list of Arma class names, and each is
  marked Approved, Not authorized, or Not in equipment list, with the reason. Nothing is
  saved. Matching is by class name, so equipment needs its class name filled in in
  Command Net.

### Audit log

Briefings, SOP documents and versions, Zeus assets and kit approvals implement Forumify's
`AuditableEntityInterface`, so Forumify itself records who created, changed or removed
them and which fields changed. **Audit Log** is a read-only view of just those entries, so
S3 leadership can review S3 activity without access to the site-wide log.

It does not record a reason, and kicks, bans and other game-server actions never touch the
forum database, so they aren't logged. That needs the RCON work planned for Phase 3.
Acknowledgements are not audited.

### Discord announcements

Built on the Discord plugin's `BotService::postAnnouncement()`, which posts through its bot
to each server's announcements channel. The spec's webhook design is superseded by that: the
bot already exists, and role and rank changes already reach Discord through its role
mappings. This plugin adds two announcements, each behind a toggle that is **off by
default** under **Discord Announcements**:

- A briefing being marked Ready.
- A new SOP version being published.

A failed bot call is logged and never blocks saving, and `@` is neutralized in staff-entered
text so a title can't ping a whole server.

## Permissions

Checked as `command-net-s3.<area>.<action>` (the prefix is slugged from the plugin's
display name, "Command Net S3"), declared in `CommandNetS3Plugin::getPermissions()`.

| Permission | Grants |
| --- | --- |
| `admin.briefing.view` / `.manage` | See / create, edit and delete briefings; staff can view Drafts on the player page |
| `admin.sop.view` / `.manage` | See / manage SOP documents and versions; view acknowledgement status |
| `admin.zeus_asset.view` / `.manage` | See / manage the Zeus asset library |
| `admin.loadout.view` / `.manage` | Use the Loadout Check and see / manage kit approvals |
| `admin.audit_log.view` | The S3 audit log view |
| `admin.discord.manage` | Turn Discord announcements on and off |
| `briefing.view` | The player-facing briefing page |
| `sop.view` / `sop.acknowledge` | Read the SOP library / acknowledge a version |

## Tables

`s3_briefing`, `s3_sop`, `s3_sop_version`, `s3_sop_acknowledgement`, `s3_zeus_asset`,
`s3_mission_kit_approval`. The audit log uses Forumify's own `audit_log` table, and Discord
settings are stored under the `command_net_s3.discord` setting.

## Known gaps

- **Not exercised in a browser.** No page here has been loaded with real data, the loadout
  check's Approved path hasn't been tried against real equipment, and no Discord message has
  been sent.
- **Briefing map image upload** from the spec isn't built.
- **"Slotted" means an Attending or Maybe RSVP.** Change `BriefingController::isSlotted()`
  if you gain a real slotting concept.
- **No navigation links** to the SOP library (`/sops`) in the site menu yet.
- **The Zeus library has no player- or GM-facing page**, so Zeus/GM staff need admin-panel
  access plus the view permission.
- **The audit log view lists its entities explicitly** in `S3AuditLogTable::AUDITED_ENTITIES`;
  add new auditable entities there.
- **Promotions aren't announced on Discord.** That belongs to personnel management, outside
  this plugin's scope in the spec.
- **No automated tests.**
- **Mission file storage is undecided**, which blocks the Phase 3 mission repository.

## Development

```bash
make quality       # phpcs + phpstan (needs composer install)
make quality-fix   # phpcbf
```

Migrations are generated with `doctrine:migrations:diff --namespace=CommandNetS3PluginMigrations`.
If several Claude or dev sessions share one Forumify install, generate and run migrations from
one place: `diff` and `migrate` pick up every plugin's uncommitted entities.
