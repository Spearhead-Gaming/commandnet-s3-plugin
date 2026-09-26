# commandnet-s3-plugin

A [Forumify](https://forumify.net) plugin (`majesticdev/commandnet-s3-plugin`) for
**Spearhead Gaming**'s S3 (Operations) staff — Mission Developers, Server Admins, and
Zeus/GMs — sibling to and dependent on `commandnet-plugin`, which already covers ops
calendaring, rosters, promotions, LOAs, and awards. This plugin adds what that one doesn't:
briefings, SOP/doctrine library, Zeus asset catalog, loadout tracker, live mission notes,
mission upload/versioning with mod lists, mission feedback, server status dashboard, and the
public operation-information page. See [README.md](README.md) for the module table,
[`Plugin Spec.md`](./Plugin%20Spec.md) for the original spec, and
[`Forumify S3 Plugin — Feature Tracker.md`](./Forumify%20S3%20Plugin%20—%20Feature%20Tracker.md)
for the brainstormed feature list it came from.

"S3" is the military staff-section term for Operations (S1 personnel, S2 intelligence, S3
operations, S4 logistics) — nothing to do with AWS S3.

Built for this one community's Forumify install, not a general-purpose skeleton.

## The ecosystem

Sibling repos under `G:\Github Repos`: **commandnet-plugin** (required dependency — this
plugin's `Briefing`/`OperationPage`/`Mission` hang off its `Operation` entity, and the loadout
check reads its `Equipment`/`Position`/`Unit`), **commandnet-discord-plugin** (optional —
Discord announcements; `loadExtension` only imports Discord config when that plugin is
present), **commandnet-discord-bot**, **command-net-theme**, **forumify-id-card-plugin**.

## Local dev

This repo is developed via a Composer **path repository**, not a packaged install — the real
Forumify app is `~/dev/forumify` in WSL, and its `composer.json` points a `path` repo at this
directory's WSL path (`/mnt/g/Github Repos/commandnet-s3-plugin`). Editing here edits the
live plugin. Uploaded mission files land in that app's `var/s3-missions/`, not in this repo.

**`.claude/launch.json`** in this repo drives `preview_start` for the Browser pane. It has
moved between two setups across sessions — check its current content before assuming which:
1. A `wsl.exe`-spawned native `php -S` process.
2. An **attach-only** config (`url`/`port`, no `runtimeExecutable`) pointing at a separate
   Docker Compose dev stack at `~/dev/forumify-dev-docker/docker-compose.yml` (WSL-native
   path). That stack is MySQL + PHP containers bind-mounting `~/dev/forumify` and
   `/mnt/g/Github Repos` at identical absolute paths so the composer path repos resolve
   unchanged inside the container. It runs `APP_DEBUG=0` — after any PHP/config edit here,
   run `docker exec <forumify-container> php bin/console cache:clear` before it takes effect
   in the browser; Twig edits show up live without that.

There is also a separate, unrelated **prod-mode** Docker stack (containers named
`default-forumify-1`/`default-database-1`, image `forumify-fixed:latest`) that Docker
Desktop's Gordon agent created once — vanilla Forumify, no plugins, no relation to this repo's
dev work. Don't confuse the two; check `docker ps` and each container's `DATABASE_URL`/image
before assuming which one a port belongs to.

After a path-repo/composer change from the app root:
```bash
bin/console forumify:plugins:refresh
bin/console forumify:plugins:activate majesticdev/commandnet-s3-plugin
bin/console doctrine:migrations:migrate
```

**The dev app's `vendor/majesticdev/commandnet-s3-plugin` is a symlink to the *main* checkout**
(`/mnt/g/Github Repos/commandnet-s3-plugin`), not to a Claude worktree. To test a worktree's
code in the dev stack, repoint that symlink at the worktree (`ln -sfn`, from
`/usr/src/app/vendor/majesticdev` inside the container) and put it back afterwards. In Git Bash
set `MSYS_NO_PATHCONV=1` or `docker exec` mangles `/usr/src/app`-style paths. Twig edits to an
already-rendered template also needed a `cache:clear` here, despite the note above.

## Commands

```bash
make quality       # phpcs (strict) + phpstan
make quality-fix    # phpcbf autofix
```
No automated tests exist yet (see README's Status section for what *has* been checked, by
hand, statically or with targeted scripts). If you add real test coverage, that closes a
known gap — worth calling out explicitly in the PR.

## Gotchas learned the hard way

- **Page loads over the Docker dev stack were 10x+ slower with `APP_DEBUG=1`.** The path
  repos symlink plugin source through a Windows-drive bind mount inside WSL2 (slow `stat()`),
  and Symfony's dev-mode container-cache freshness check stats every tracked resource file on
  every request. `APP_DEBUG=0` fixed it (4-5s → ~0.3s) at the cost of needing a manual
  `cache:clear` after PHP/config edits — worth remembering before assuming "it's just slow."
- **Doctrine migrations only pick up a plugin's own migration namespace once that plugin is
  *active***. Running `doctrine:migrations:migrate` before `forumify:plugins:activate` will
  silently skip this plugin's tables (`s3_*`) even though the migrate command reports success
  — re-run migrate again after activating.
- **`docker-php-ext-install -j$(nproc) <ext1> <ext2> ...` can fail** ("`cp: cannot stat
  'modules/*'`") when installing several PHP extensions in one call with parallel jobs — drop
  `-j` (install serially) if you ever touch the dev Dockerfile. `opcache` specifically fails
  to build as a source ext on some base images; it's often already bundled, so a plain
  `docker-php-ext-install` list without `opcache` (and without `-j`) is the safe default.
- **`File` upload constraints keyed by extension, not just MIME type**: the Arma 3 Launcher's
  exported `.html` preset gets sniffed as `text/xml` by PHP, not `text/html` — see
  `MissionModsType`'s `File` constraint for the extension→MIME-list pattern this needed.
