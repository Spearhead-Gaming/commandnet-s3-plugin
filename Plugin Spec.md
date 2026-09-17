# Forumify S3 Plugin — Spec

2026-09-17 · @Someone

## Overview

This plugin gives S3 (Operations) staff — Mission Developers, Server Admins, and Zeus/GMs — the tools they're currently missing. Command Net Plugin already covers ops calendaring, rosters, promotions, LOAs, and awards; this spec does not duplicate that. It covers the confirmed gaps identified in the Feature Tracker tab: briefing/asset tools for Zeus/GMs, mission-authoring tools for Mission Devs, and server oversight tools for Admins.

## Scope

**In scope** — the three modules below: Zeus/GM Support, Mission Development, Server Administration.

**Out of scope** — personnel management, recruitment, and general admin QoL tools (staff dashboard, disciplinary log). These belong to other staff branches or are already handled by Command Net Plugin; they're tracked in the tracker tab for visibility only, not built here.

## Zeus/GM Support

### Briefing tool

Attach an OPORD to an op's calendar entry: mission name, task/purpose, objectives (bulleted, ordered), map image upload, and a status field (Draft / Ready / Delivered). Visible to slotted players once marked Ready.

### Zeus asset / module library

A searchable catalog of terrain, faction, and common asset packs the community uses, each tagged (terrain, faction, vehicle, misc) with mod link and short notes. GMs browse/filter when planning a mission.

### Live mission notes / AAR quick-capture

A lightweight timestamped note field a GM can update during a live op (key events, casualties, objective completion) that pre-fills the After Action Report template afterward instead of writing it from scratch.

## Mission Development

### SOP/doctrine library

Versioned documents (SOPs, doctrine, standard loadout rules) with a changelog per version. Members must click "acknowledge" on the current version; staff can see who hasn't yet.

### Virtual loadout / kit authorization tracker

Defines which kits/loadouts each unit or role is authorized to run (by mission or standing rule), so Mission Devs can check a mission's kit list against what's approved before publishing.

### Mission upload & version repository

Upload a mission file (.pbo/.vt) tied to an op calendar entry, with version history, so the exact build used in a given op is always retrievable.

### Mission testing / feedback log

A structured form for playtesters to log bugs, balance issues, and feedback per mission version, visible to the Mission Dev who owns it.

## Server Administration

### Audit log for staff actions

An append-only log of admin-level actions taken on the server or in the forum panel (kicks, bans, role changes, config edits) with actor, target, timestamp, and reason field.

### Discord sync

One-way sync from Forumify to Discord: role/rank changes and op announcements post automatically to designated channels via webhook.

### Server status / performance dashboard

Live or near-live view of server uptime, player count, and mod/mission currently loaded, pulled from the game server's query port or RCON, shown to Server Admins in one place.

### Mod/addon version tracker

Tracks the mod list and version each server is running, flags when a mod is out of date against the client-side modpack, and logs update history.

## Roles & Permissions

| Module | Who can view | Who can edit/act |
| --- | --- | --- |
| Briefing tool | Slotted players (once Ready) | Zeus/GM staff |
| Asset library | All Zeus/GM staff | Zeus/GM staff |
| AAR quick-capture | Zeus/GM staff, S3 leadership | Assigned GM for that op |
| SOP/doctrine library | All members | Mission Dev leads |
| Loadout tracker | Mission Devs, Zeus/GMs | Mission Dev leads |
| Mission repository | Mission Devs | Mission Dev who owns the mission |
| Testing/feedback log | Mission Devs | Any member (submit), Mission Dev (resolve) |
| Audit log | Server Admins, S3 leadership | System-generated, read-only |
| Discord sync | N/A (automated) | Server Admins (config only) |
| Status dashboard | Server Admins, S3 leadership | System-generated, read-only |
| Mod/addon tracker | Server Admins | Server Admins |

## Roadmap

**Phase 1 (MVP)** — the tools with the most immediate operational pain if missing:

- Briefing tool
- SOP/doctrine library
- Audit log

**Phase 2** — quality-of-life additions once the MVP is stable:

- Zeus asset/module library
- Loadout tracker
- Discord sync

**Phase 3** — larger builds needing more integration work (RCON/query-port access, file storage for missions):

- Live mission notes / AAR quick-capture
- Mission upload & version repository
- Mission testing/feedback log
- Server status/performance dashboard
- Mod/addon version tracker

## Open Questions

- [x] Does the game server expose RCON/query-port access for the status dashboard and audit log integration?
- [ ] Where should mission files be stored (Forumify's own storage vs. an external file host)?
- [x] Should Discord sync be one-way only, or does S3 also want two-way (e.g. a Discord command that updates Forumify)?
- [x] Who owns building this — in-house dev, community contractor, or a Forumify marketplace developer?
