# The Guestbook Course — from plain HTML to a live CRUD app

A complete beginner course **and** a working app in one project. Thirteen
lessons take you from a bare HTML page to a deployed guestbook with create,
read, update and delete (CRUD) features.

- **No frameworks. No build step. No Composer. No npm.**
- HTML5 · CSS3 · vanilla JavaScript · HTMX (from a CDN) · PHP 8.x (PDO) · SQLite.

## Folder layout

```
guestbook-site/
  public/                  ← becomes public_html on cPanel
    index.php              ← lesson index (home page)
    lessons/               ← lessons 1–13
    app/                   ← the final CRUD guestbook
      index.php
      handlers.php         ← HTMX endpoints (create/list/count/edit/update/delete/entry)
    assets/
      css/style.css        ← the one stylesheet
      js/main.js           ← the one script file
  src/
    config.php             ← PDO connection, table creation, shared helpers
  data/
    .htaccess              ← "Require all denied" (protects the database)
    .gitkeep               ← keeps the empty folder in Git
  .gitignore               ← ignores data/*.sqlite etc.
  .vscode/extensions.json  ← recommends useful VS Code extensions
  README.md
```

### Why src/ and data/ sit outside public/

Everything inside `public/` can be requested by any visitor. Secrets and data
must not live there. On cPanel:

```
home/your-username/
  src/config.php
  data/guestbook.sqlite
  public_html/    ← contents of public/
```

The relative includes keep working unchanged:
- `public/index.php` and `public/lessons/*.php` use `__DIR__ . '/../src/config.php'`
  (or `'../../src/config.php'` from `lessons/`).
- `public/app/*.php` use `__DIR__ . '/../../src/config.php'`.
- `src/config.php` builds the database path as `__DIR__ . '/../data/guestbook.sqlite'`.

Because `__DIR__` is the file's real location on disk, these paths resolve
correctly whether you run locally (`php -S localhost:8000 -t public` from the
project root) or on cPanel (files uploaded as shown above).

## The database

`data/guestbook.sqlite` is created automatically on first run, together with
the `messages` table (`id`, `name`, `message`, `created_at`). The file is:

- **outside** `public/` so no browser can download it,
- protected by `data/.htaccess` (`Require all denied`),
- excluded by `.gitignore` (`data/*.sqlite`), so it never reaches GitHub.

## Requirements

- PHP **8.x** with the `pdo_sqlite` extension (bundled default on most hosts).
- Nothing else. No shell access needed for the deployment path.

## Run locally

From the project root (the folder that contains `public/`, `src/`, `data/`):

```bash
php -S localhost:8000 -t public
```

Then open <http://localhost:8000/> for the lessons and
<http://localhost:8000/app/index.php> for the app.

Static-only editing works with the **Live Server** VS Code extension, but PHP
pages need the command above (see lesson 12).

## Deploy

- **GitHub (repo):** lesson 7
- **GitHub Pages (static lessons only):** lesson 8
- **Vercel Hobby (static lessons only):** lesson 9
- **Vercel + free Neon Postgres (optional appendix):** lesson 10
- **cPanel (the full PHP + SQLite CRUD app):** lesson 11 ← the live app

## The app's API (for the curious)

All requests go to `public/app/handlers.php` with an `action` parameter:

| Action   | Method | Answer                                            |
|----------|--------|---------------------------------------------------|
| `create` | POST   | `201` + the new entry fragment; `422` on bad input |
| `list`   | GET    | `200` + all entries (or a friendly empty state)    |
| `count`  | GET    | `200` + the "N messages" badge                     |
| `edit`   | GET    | `200` + the inline edit form                       |
| `entry`  | GET    | `200` + one entry (used by Cancel)                 |
| `update` | POST   | `200` + the updated entry; `422` on bad input      |
| `delete` | DELETE | `200` + empty body (row removed)                   |

Wrong method → `405`. Unknown id or action → `404`. Server problem → `500`
with a friendly message, never a raw PHP error.
