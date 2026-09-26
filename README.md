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
- A writable `var/` directory on the install: uploaded mission files are kept in
  `var/s3-missions/`
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

All three phases of the spec are built.

| Phase | Module | State |
| --- | --- | --- |
| 1 | Briefing tool | Built, except the map image upload |
| 1 | SOP / doctrine library | Built |
| 1 | Audit log | Built, S3 activity only (see below) |
| 2 | Zeus asset library | Built, admin only |
| 2 | Loadout tracker | Built |
| 2 | Discord announcements | Built, off by default |
| 3 | Live mission notes / AAR quick-capture | Built, the AAR draft is copy and paste |
| 3 | Mission upload & version repository | Built, files in private local storage |
| 3 | Mission testing / feedback log | Built |
| 3 | Server status / performance dashboard | Built, name / map / mission / players / ping only |
| 3 | Mod / addon version tracker | Built, versions entered by hand |
| Extra | S3 dashboard and site menu item | Built |
| Extra | Operation information page | Built, extra sections typed as one entry per line |
| Extra | Mission mod lists and generated launcher preset | Built, typed or from an uploaded launcher export |

Nothing here has automated tests. It has been checked statically (container, Twig and
syntax lint, schema drift) plus targeted runs of the riskiest logic: the server query
against a local fake server, the mission file storage (name generation, refused extensions,
path traversal), version comparison, the class-name parser, the operation page's text
parser and template (rendered against hostile input under strict variable checking), and the
mod list parser and preset generator, including against a real 70-mod Arma 3 Launcher export.

In a browser, two flows have been run by hand. The mission flow: creating a mission,
uploading three versions (200 KB, 1.5 MB and 5 MB, with the stored sizes matching exactly)
and downloading one. And the mod list flow, with mock data: creating a mission with the real
launcher export attached, then checking the mission page, the generated download and the
operation information page; editing the list by typing it (a DLC and a local mod included);
the two error paths (a bad typed line, a file that isn't a preset); an operation with no
mission falling back to its server's mods; and creating an Operation Page in the admin.
**The other pages have not been checked with real data**, so do that with some test data
before relying on them.

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

Briefings, SOP documents and versions, Zeus assets, kit approvals, game servers, server
mods, missions, mission versions, mission feedback and operation pages implement Forumify's
`AuditableEntityInterface`, so Forumify itself records who created, changed or removed
them and which fields changed. That is also the mod update history: every change to a
mod's installed version is logged with who and when. **Audit Log** is a read-only view of just those entries, so
S3 leadership can review S3 activity without access to the site-wide log.

It does not record a reason, and kicks, bans and other game-server actions never touch the
forum database, so they aren't logged. That would need an RCON integration, which isn't
built. Acknowledgements and live notes are not audited.

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

### Missions and playtest feedback

Missions with uploaded, versioned files, so the exact build used in an operation stays
retrievable.

- Admin: **Missions**. A mission can be tied to an operation. Each version is a `.pbo`,
  `.vt` or `.zip` (up to 256 MB, and also capped by your server's PHP upload limits) with a
  label and notes, and stays downloadable.
- Files are stored under `var/s3-missions/` with generated names, outside the web root, and
  served only through a permission-checked download. Nothing a user types becomes a path.
  `MissionStorage` is the only class that touches the disk, so moving to S3 or another
  Flysystem storage is a swap of that one class.
- Each version has an unguessable **feedback link** that a Mission Dev copies and shares.
  Any member with `mission.feedback` can report a bug, balance issue or general feedback
  through it. Missions are deliberately not listed for members, so upcoming ones can't be
  browsed. The owner or a lead can mark feedback resolved or reopen it.
- Ownership: any Mission Dev with `mission.manage` can create a mission and becomes its
  owner. Only the owner, or someone with `mission.manage_all` (leads), can upload versions
  or resolve feedback. Editing or deleting the mission record itself is leads only.
- **Mod list**: every mission has one list of mods and DLC, set two ways. Type it, one line
  per mod as `Name | Workshop link or ID` (start a DLC line with `DLC:`; a line with only a
  name is a local mod that players can't download), or upload the preset exported from the
  Arma 3 Launcher (Mods, Preset, Export), whose mods and DLC are read out of the HTML. Saving
  replaces the list, on the mission form or on **Edit mod list** (open to the owner and
  leads), where the text box starts from the stored list. A bad line, or a file with no
  mods in it, is a form error and leaves the stored list alone.
- **Generated download**: players get a launcher preset built fresh from the list, from the
  mission page and the operation page. The uploaded file is only read for names and Steam
  ids, with external entities and network access off; it is never stored or served back, so
  an uploaded page can't be redistributed. Limits: 2 MB and 500 mods.

### Live notes

**Live Notes** lets a GM pick an operation and jot timestamped notes during it. Notes can
only be added, so the record stays honest. Afterwards the page builds an after-action
report draft from them, to copy into Command Net's AAR form. It is not a real prefill of
that form, which would mean changing `commandnet-plugin`.

### Server status and mod tracker

- **Game Servers**: a host and Steam query port per server.
- **Server Status**: asks each server live, over the Steam query protocol (A2S_INFO), whether
  it is answering, its name, map, mission, player count and ping, plus how many of its
  tracked mods are out of date. It cannot show uptime or the loaded mod list, and it isn't
  RCON. Servers are queried one after another with a short timeout, so many offline servers
  make the page slow.
- **Server Mods**: the version installed on each server against the version in the client
  modpack, flagged Out of date when the server is behind. Versions are entered by hand and
  compared with `version_compare`, so 1.2.0 is correctly behind 1.10.0.

### Admin menu

The **Command Net S3** admin menu is grouped the way the spec groups the modules, with a
Dashboard link at the top:

- **Zeus / GM**: Briefings, Zeus Assets, Operation Pages, Live Notes.
- **Mission Development**: SOP Library, SOP Versions, Missions, Loadout Check, Kit Approvals.
- **Server Administration**: Server Status, Game Servers, Server Mods, Discord Announcements,
  Audit Log.

A category only appears when the user can see something in it, because Forumify's own menu
filter would otherwise leave an emptied category behind as a blank flyout.

### S3 dashboard and the site menu

`/s3` is a home page for S3 staff, gated by `dashboard.view`. Each section appears, and runs
its queries, only when the viewer has the permission for what it shows: upcoming operations
with their briefing state, your SOP documents awaiting acknowledgment, open playtest
feedback, out-of-date mods, recent S3 audit activity, and a list of tools.

For Forumify's site **Menu Builder** there is an **S3** item type. Tick which pages it covers
(Dashboard, Current Operation, SOP Library): one page renders as a plain link, several as a
dropdown, and pages the viewer can't access are left out. Use the item's own permissions in
the Menu Builder to limit it to S3 staff roles. Nothing is added to the site menu by default.

### Operation information page

A live page for an operation, ported from the community's static operations-page design.

- `/operations/current` shows the operation in progress, otherwise the next scheduled one
  (or says there isn't one). `/operations/{id}/info` shows any operation. Needs
  `operation_info.view`.
- **Read live on every view**, so it never goes stale: title, status, dates, location, unit,
  RSVP counts, the OPORD body, the briefing's mission and objectives, the countdown, and the
  chosen server's online state, player count and map.
- **The mod list** is the one on the mission tied to the operation (the newest, if several),
  and its "Download Mod-Pack" button gives the generated launcher preset. With no mission
  list, it falls back to the mods tracked on the chosen server, shown with their versions.
  DLC and local mods are marked, and the Version column appears only when a row has one. A
  preset link typed on the Operation Page overrides the generated download.
- **Pages are created for you.** Saving an operation of type Operation creates its page at
  once, including every operation a Deployment's **Generate Operations** makes, so there is
  nothing to add by hand. The page starts empty and unpublished; edit it like any other.
  Patrols, fun days, trainings and meetings don't get one (add it under Operation Pages if you
  want one).
- **Order numbers count per Deployment**: `26-10-03` is the third operation of the Deployment
  starting in October 2026. With no Deployment the number counts within the year (`26-04`).
  Numbers go out in creation order, and generation creates operations by date, so a generated
  Deployment is numbered in date order. The number is only a starting value: edit it freely.
  Numbers typed in another shape (`OP-2026-014`) are ignored when working out the next one.
- **Set the shared details once.** The server, Steam collection link, comms plan, ROE,
  pre-op checklist and quick links rarely change between operations, so they inherit in three
  tiers: the operation page's own value, else its Deployment's (**Zeus / GM → Deployment
  Details**, one record per Deployment), else the S3-wide default (**Zeus / GM → Page
  Defaults**). Edit one place and every page below it follows. A blank box counts as "not
  filled in", so to make one operation different, type the different value on its page. An
  unpublished page still hides all of it from non-staff. Timeline, task organization, mission
  data, summary and order number stay per operation.
- **Operations that existed before this** can be given pages in one go:
  `bin/console command-net-s3:operation-pages:backfill --dry-run` shows what would be
  created, then run it without `--dry-run`. It only does upcoming operations unless you add
  `--all`, and numbers oldest first.
- **Stored on an Operation Page**, edited under **Zeus / GM → Operation Pages**: order number,
  one-line summary, server, preset and Steam collection links, and text boxes for the
  timeline, task organization, mission data, comms plan, ROE, pre-op checklist and quick
  links. Each is one entry per line with columns split by `|`, for example
  `1900 | Platoon Briefing | Full OPORD brief`, and the form shows an example for each.
- Until an Operation Page is **Published**, only staff (`operation_page.manage`) see its
  sections; everyone else still gets the operation's own details. A draft briefing is hidden
  from non-staff too.
- All text is escaped, and a link must start with `http(s)://` or `/`, so a typed
  `javascript:` link is dropped.

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
| `admin.server.view` / `.manage` | See / manage game servers and mods; view Server Status |
| `admin.mission.view` | See missions, versions and feedback; download files |
| `admin.mission.manage` | Create missions; upload versions and resolve feedback on missions you own |
| `admin.mission.manage_all` | Leads: edit or delete any mission, upload to or resolve on any |
| `admin.live_notes.view` / `.manage` | See / add live mission notes |
| `admin.operation_page.view` / `.manage` | See / edit Operation Pages; `manage` also previews unpublished ones |
| `briefing.view` | The player-facing briefing page |
| `sop.view` / `sop.acknowledge` | Read the SOP library / acknowledge a version |
| `mission.feedback` | Submit playtest feedback through a shared feedback link |
| `dashboard.view` | Open the S3 dashboard |
| `operation_info.view` | Open the operation information page |

## Tables

`s3_briefing`, `s3_sop`, `s3_sop_version`, `s3_sop_acknowledgement`, `s3_zeus_asset`,
`s3_mission_kit_approval`, `s3_game_server`, `s3_server_mod`, `s3_mission`,
`s3_mission_version`, `s3_mission_feedback`, `s3_mission_note`, `s3_operation_page`,
`s3_mission_mod`. The
audit log uses Forumify's
own `audit_log` table, and Discord settings are stored under the `command_net_s3.discord`
setting. Uploaded mission files are on disk in `var/s3-missions/`, not in the database.

## Known gaps

- **The generated preset is unverified in the real launcher.** It follows the format of a
  real export and reads back to the same list, but nobody has confirmed the Arma 3 Launcher
  imports it. Try importing a downloaded preset before relying on it.
- **The mod list belongs to the mission, not to one version**, and changes to it are not
  audited (the list is replaced as a whole). Mods have no per-mod version there; versions
  only appear on the server-mod fallback.
- **Times on the operation page follow the site's timezone**, but the timeline and other
  typed sections are literal text. Type the timeline in the timezone the page shows.
- **Mostly not exercised in a browser.** Only the mission flow and the mod list flow above
  have been run by hand. Not checked with real data: submitting and resolving playtest
  feedback, the browser-side refusal of a disallowed upload (the storage layer's refusals
  are tested), the non-owner permission checks, Live Notes, Server Status, briefings, the
  SOP library, the dashboard, the categorized admin menu, the Menu Builder "S3" item type,
  the operation page as a normal member without staff rights, and the rest. The loadout
  check's Approved path hasn't been tried against real equipment, and no Discord message has
  been sent.
- **Briefing map image upload** from the spec isn't built.
- **"Slotted" means an Attending or Maybe RSVP.** Change `BriefingController::isSlotted()`
  if you gain a real slotting concept.
- **Nothing is in the site menu until an admin adds it.** Use the "S3" item type in
  Forumify's Menu Builder for the dashboard, the current operation and the SOP library.
- **The Zeus library has no player- or GM-facing page**, so Zeus/GM staff need admin-panel
  access plus the view permission.
- **The audit log view lists its entities explicitly** in `S3AuditLogTable::AUDITED_ENTITIES`;
  add new auditable entities there.
- **Promotions aren't announced on Discord.** That belongs to personnel management, outside
  this plugin's scope in the spec.
- **No automated tests.**
- **Deleting a mission leaves its files on disk.** The records go, the files in
  `var/s3-missions/` stay, which also means an accidental delete doesn't lose a build.
- **Mission uploads are limited by PHP** (`upload_max_filesize` and `post_max_size`), which
  may be lower than the 256 MB the form allows.
- **Back up `var/s3-missions/`** along with the database, since mission files aren't in it.
- **The server query is basic:** no uptime, no loaded mod list, not RCON, and servers are
  queried sequentially.
- **Mod versions are entered by hand;** nothing reads them from the server.
- **The AAR draft is copy and paste**, not a prefill of Command Net's AAR form.
- **Live notes have no "assigned GM"** per operation, because Command Net doesn't model one.
  Anyone with `live_notes.manage` can add notes to any operation.
- **On the operation page the OPORD is one rich-text block**, not five collapsible
  paragraphs, because Command Net stores it as a single body. Mods have no Required /
  Optional / Client tag, so the design's filter chips are gone (the search box remains).
- **The operation page queries the game server on every view**, which adds a moment when
  it is down. Cache the result briefly if the page gets busy.
- **The operation page design imports its fonts from Google Fonts**, so viewers' browsers
  make that request. Remove the `@import` in `_page.html.twig` to avoid it.

## Development

```bash
make quality       # phpcs + phpstan (needs composer install)
make quality-fix   # phpcbf
```

The dev server config in `.claude/launch.json` starts PHP with 4 workers and 300 MB upload
limits. Plain `php -S` is single-threaded, and in dev mode a page takes seconds, so its own
health checks can starve it; PHP's default 2 MB upload limit also rejects real missions.

Migrations are generated with `doctrine:migrations:diff --namespace=CommandNetS3PluginMigrations`.
If several Claude or dev sessions share one Forumify install, generate and run migrations from
one place: `diff` and `migrate` pick up every plugin's uncommitted entities.
