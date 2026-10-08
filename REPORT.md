# Guestbook Course — Full Deliverable Report
All 24 project files, the review report, and the run/deploy guides.

## 1. File tree

```
guestbook-site/.gitignore
guestbook-site/.vscode/extensions.json
guestbook-site/README.md
guestbook-site/data/.gitkeep
guestbook-site/data/.htaccess
guestbook-site/public/app/handlers.php
guestbook-site/public/app/index.php
guestbook-site/public/assets/css/style.css
guestbook-site/public/assets/js/main.js
guestbook-site/public/index.php
guestbook-site/public/lessons/01-glimpse.html
guestbook-site/public/lessons/02-html-css.html
guestbook-site/public/lessons/03-html-css-js.html
guestbook-site/public/lessons/04-html-css-js-php.php
guestbook-site/public/lessons/05-html-css-js-php-sqlite.php
guestbook-site/public/lessons/06-final-htmx.php
guestbook-site/public/lessons/07-github-upload.html
guestbook-site/public/lessons/08-github-pages.html
guestbook-site/public/lessons/09-vercel.html
guestbook-site/public/lessons/10-vercel-free-database.html
guestbook-site/public/lessons/11-cpanel-deploy.html
guestbook-site/public/lessons/12-vscode-editing.html
guestbook-site/public/lessons/13-ai-test-and-review.html
guestbook-site/src/config.php
```

## 2. File contents

### `guestbook-site/.gitignore` (299 bytes)

```gitignore
# Never commit the SQLite database (it holds real visitor data).
data/*.sqlite
data/*.sqlite-wal
data/*.sqlite-shm

# Composer files (this project does not use Composer, but ignore it just in case).
vendor/

# macOS junk file.
.DS_Store

# Personal VS Code workspace settings.
.vscode/settings.json
```

### `guestbook-site/.vscode/extensions.json` (131 bytes)

```json
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "ritwickdey.LiveServer",
    "ms-vscode.live-server"
  ]
}
```

### `guestbook-site/README.md` (4146 bytes)

```markdown
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
```

### `guestbook-site/data/.gitkeep` (0 bytes)

*(empty file)*

### `guestbook-site/data/.htaccess` (19 bytes)

```
Require all denied
```

### `guestbook-site/public/app/handlers.php` (12979 bytes)

```php
<?php
/**
 * handlers.php — the HTMX endpoints of the guestbook app.
 *
 * This file answers small AJAX-style requests made by HTMX
 * (see public/app/index.php). It NEVER returns a whole page:
 * every response is a small HTML fragment plus a proper HTTP
 * status code.
 *
 * Actions (chosen by ?action=...):
 *   create  POST    add a message, answer 201 + the new entry fragment
 *   list    GET     answer 200 + every entry as fragments
 *   count   GET     answer 200 + the "N messages" badge fragment
 *   edit    GET     answer 200 + the inline edit form fragment
 *   update  POST    save changes, answer 200 + the updated entry fragment
 *   delete  DELETE  remove a row, answer 200 + empty body
 *   entry   GET     answer 200 + one entry fragment (used by "Cancel")
 *
 * Status codes: 200 success, 201 created, 404 unknown id/action,
 * 405 wrong HTTP method, 422 validation error, 500 server error.
 * A visitor never sees a raw PHP error.
 */
require_once __DIR__ . '/../../src/config.php';
guestbook_create_table();

header('Content-Type: text/html; charset=UTF-8');

/* ============================================================
   Small shared helpers
   ============================================================ */

/**
 * Sends a fragment with a status code and stops.
 */
function respond(string $html, int $status = 200, array $headers = []): void
{
    foreach ($headers as $header) {
        header($header);
    }
    http_response_code($status);
    echo $html;
    exit;
}

/**
 * 405: this action exists but not for this HTTP method.
 * The "Allow" header tells the client which method is correct.
 */
function method_not_allowed(string ...$allowed): void
{
    respond(
        '<p class="form-error" role="alert">Wrong request method for this action.</p>',
        405,
        ['Allow: ' . implode(', ', $allowed)]
    );
}

/**
 * 404: the action (or the id) does not exist.
 */
function not_found(string $message = 'Not found.'): void
{
    respond('<p class="form-error" role="alert">' . guestbook_escape($message) . '</p>', 404);
}

/**
 * Reads an id from the URL and returns it as an integer,
 * or fails with 404 when it is missing or not a plain number.
 */
function read_id(): int
{
    $raw = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if ($raw === '' || !ctype_digit($raw)) {
        not_found('That message id does not exist.');
    }
    return (int) $raw;
}

/**
 * Finds one row by id. 404 when it is not there.
 */
function fetch_entry(PDO $db, int $id): array
{
    $stmt = $db->prepare('SELECT id, name, message, created_at FROM messages WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if ($row === false) {
        not_found('That message does not exist (it may have been deleted).');
    }
    return $row;
}

/**
 * Validates input using the shared rules from src/config.php:
 * name required, max 60 chars; message required, max 500 chars.
 * Returns [errors, name, message] with everything trimmed.
 */
function clean_and_validate(array $source): array
{
    $name    = guestbook_field($source, 'name');
    $message = guestbook_field($source, 'message');
    $errors  = guestbook_validate($name, $message);
    return [$errors, $name, $message];
}

/* ============================================================
   Fragment builders (each returns HTML as a string)
   ============================================================ */

/**
 * One guestbook entry, ready to drop into the list.
 * EVERY value is escaped with guestbook_escape() (XSS protection).
 */
function render_entry(array $row): string
{
    $id = (int) $row['id'];
    $html = '<article class="entry" id="entry-' . $id . '">';
    $html .= '<div class="entry-header">';
    $html .= '<p class="entry-name">' . guestbook_escape($row['name']) . '</p>';
    $html .= '<span class="entry-date">' . guestbook_escape($row['created_at']) . '</span>';
    $html .= '</div>';
    $html .= '<p class="entry-message">' . guestbook_escape($row['message']) . '</p>';
    $html .= '<div class="entry-actions">';
    // Edit: GET the inline edit form, swap it in place of this entry.
    $html .= '<button type="button" class="button button-secondary"'
           . ' hx-get="handlers.php?action=edit&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="outerHTML">Edit</button>';
    // Delete: confirms first, then removes this entry from the page.
    $html .= '<button type="button" class="button button-danger"'
           . ' hx-delete="handlers.php?action=delete&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="delete"'
           . ' hx-confirm="Delete this message?">Delete</button>';
    $html .= '</div>';
    $html .= '</article>';
    return $html;
}

/**
 * The inline edit form. Shown in place of an entry (same id).
 * On validation errors the same form comes back WITH the errors
 * and the values the user typed, so nothing is lost.
 */
function render_edit_form(array $row, array $errors = []): string
{
    $id      = (int) $row['id'];
    $name    = guestbook_escape($row['name']);
    $message = guestbook_escape($row['message']);

    $nameError    = isset($errors['name']) ? (string) $errors['name'] : '';
    $messageError = isset($errors['message']) ? (string) $errors['message'] : '';

    // aria-describedby links each error message to its field for
    // screen readers (see the accessibility section of the course).
    $nameDesc    = $nameError    !== '' ? ' aria-describedby="edit-name-error-' . $id . '"'    : '';
    $messageDesc = $messageError !== '' ? ' aria-describedby="edit-message-error-' . $id . '"' : '';

    $html = '<form class="form" id="entry-' . $id . '"'
          . ' hx-post="handlers.php?action=update&amp;id=' . $id . '"'
          . ' hx-target="#entry-' . $id . '"'
          . ' hx-swap="outerHTML" novalidate>';
    $html .= '<div class="field' . ($nameError !== '' ? ' has-error' : '') . '">';
    $html .= '<label for="edit-name-' . $id . '">Name</label>';
    $html .= '<input id="edit-name-' . $id . '" name="name" type="text"'
           . ' maxlength="60" value="' . $name . '"' . $nameDesc . '>';
    if ($nameError !== '') {
        $html .= '<p class="field-error" id="edit-name-error-' . $id . '">' . guestbook_escape($nameError) . '</p>';
    }
    $html .= '</div>';
    $html .= '<div class="field' . ($messageError !== '' ? ' has-error' : '') . '">';
    $html .= '<label for="edit-message-' . $id . '">Message</label>';
    $html .= '<textarea id="edit-message-' . $id . '" name="message" maxlength="500"'
           . $messageDesc . '>' . $message . '</textarea>';
    if ($messageError !== '') {
        $html .= '<p class="field-error" id="edit-message-error-' . $id . '">' . guestbook_escape($messageError) . '</p>';
    }
    $html .= '</div>';
    $html .= '<div class="entry-actions">';
    $html .= '<button type="submit" class="button">Save changes</button>';
    // Cancel: GET the unchanged entry back and swap the form away.
    $html .= '<button type="button" class="button button-secondary"'
           . ' hx-get="handlers.php?action=entry&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="outerHTML">Cancel</button>';
    $html .= '</div>';
    $html .= '</form>';
    return $html;
}

/**
 * The live count badge. It listens for the "msgChanged" event that
 * main.js fires after every successful create/update/delete.
 */
function render_count(int $count): string
{
    $text = $count === 1 ? '1 message' : $count . ' messages';
    return '<p class="muted" id="message-count"'
         . ' hx-get="handlers.php?action=count"'
         . ' hx-trigger="msgChanged from:body"'
         . ' hx-swap="outerHTML">'
         . '<span class="count-badge">' . guestbook_escape($text) . '</span>'
         . '</p>';
}

/**
 * The whole list. An empty database shows a friendly empty state.
 */
function render_list(PDO $db): string
{
    $rows = $db->query('SELECT id, name, message, created_at FROM messages ORDER BY id DESC')
               ->fetchAll();

    if (!$rows) {
        return '<p class="empty-state">No messages yet. Be the first to sign the guestbook!</p>';
    }

    $html = '';
    foreach ($rows as $row) {
        $html .= render_entry($row);
    }
    return $html;
}

/* ============================================================
   One function per action
   ============================================================ */

/**
 * POST create: validate, save, answer 201 + the new entry fragment.
 * On validation errors: 422 + the error box fragment.
 */
function action_create(PDO $db, string $method): void
{
    if ($method !== 'POST') {
        method_not_allowed('POST');
    }

    [$errors, $name, $message] = clean_and_validate($_POST);
    if ($errors) {
        $html = '<div class="form-error" role="alert"><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . guestbook_escape($error) . '</li>';
        }
        $html .= '</ul></div>';
        respond($html, 422);
    }

    $stmt = $db->prepare('INSERT INTO messages (name, message) VALUES (:name, :message)');
    $stmt->execute([':name' => $name, ':message' => $message]);

    $entry = fetch_entry($db, (int) $db->lastInsertId());
    respond(render_entry($entry), 201);
}

/**
 * GET list: all entries, newest first.
 */
function action_list(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    respond(render_list($db));
}

/**
 * GET count: the "N messages" badge.
 */
function action_count(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $count = (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    respond(render_count($count));
}

/**
 * GET edit: the inline edit form for one entry.
 */
function action_edit(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $entry = fetch_entry($db, read_id());
    respond(render_edit_form($entry));
}

/**
 * GET entry: one unchanged entry (used by the Cancel button).
 */
function action_entry(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $entry = fetch_entry($db, read_id());
    respond(render_entry($entry));
}

/**
 * POST update: validate, save, answer 200 + the updated entry.
 * On validation errors: 422 + the edit form WITH the errors.
 */
function action_update(PDO $db, string $method): void
{
    if ($method !== 'POST') {
        method_not_allowed('POST');
    }
    $id    = read_id();
    $entry = fetch_entry($db, $id);

    [$errors, $name, $message] = clean_and_validate($_POST);
    if ($errors) {
        respond(render_edit_form($entry, $errors), 422);
    }

    $stmt = $db->prepare('UPDATE messages SET name = :name, message = :message WHERE id = :id');
    $stmt->execute([':name' => $name, ':message' => $message, ':id' => $id]);

    respond(render_entry(fetch_entry($db, $id)));
}

/**
 * DELETE delete: remove the row, answer 200 with an empty body
 * (HTMX's hx-swap="delete" then removes the entry from the page).
 */
function action_delete(PDO $db, string $method): void
{
    if ($method !== 'DELETE') {
        method_not_allowed('DELETE');
    }
    $id    = read_id();
    $entry = fetch_entry($db, $id);

    $stmt = $db->prepare('DELETE FROM messages WHERE id = :id');
    $stmt->execute([':id' => (int) $entry['id']]);

    respond('');
}

/* ============================================================
   Route to the right action
   ============================================================ */
try {
    $db     = guestbook_db();
    $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];

    $known = ['create', 'list', 'count', 'edit', 'entry', 'update', 'delete'];
    if (!in_array($action, $known, true)) {
        not_found('Unknown action.');
    }

    switch ($action) {
        case 'create': action_create($db, $method); break;
        case 'list':   action_list($db, $method);   break;
        case 'count':  action_count($db, $method);  break;
        case 'edit':   action_edit($db, $method);   break;
        case 'entry':  action_entry($db, $method);  break;
        case 'update': action_update($db, $method); break;
        case 'delete': action_delete($db, $method); break;
    }
} catch (Throwable $error) {
    // A safety net: whatever explodes, the visitor gets a calm,
    // generic message and the real error stays in the server log.
    // (The try/catch also guards the actions above.)
    if (!in_array($action ?? '', ['list', 'count', 'edit', 'entry'], true)) {
        // fall through to the generic answer below
    }
    respond(
        '<p class="form-error" role="alert">Sorry, something went wrong on the server. Please try again.</p>',
        500
    );
}
```

### `guestbook-site/public/app/index.php` (5484 bytes)

```php
<?php
/**
 * index.php — the guestbook app itself (the "final app").
 *
 * A small CRUD page:
 *   Create  the form below (HTMX posts it, no page reload)
 *   Read    the list, loaded on page open with hx-get
 *   Update  the Edit button on each entry
 *   Delete  the Delete button on each entry
 *
 * All the real work happens in handlers.php (same folder).
 */
require_once __DIR__ . '/../../src/config.php';
guestbook_create_table();

$db    = guestbook_db();
$count = (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>The Guestbook · a tiny CRUD app</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- HTMX from a public CDN (no npm, no build step). -->
  <script src="https://unpkg.com/htmx.org@1.9.12"></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">&#128216; The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="../lessons/01-glimpse.html">01 &middot; Glimpse</a></li>
          <li><a href="../lessons/02-html-css.html">02 &middot; HTML + CSS</a></li>
          <li><a href="../lessons/03-html-css-js.html">03 &middot; + JavaScript</a></li>
          <li><a href="../lessons/04-html-css-js-php.php">04 &middot; + PHP</a></li>
          <li><a href="../lessons/05-html-css-js-php-sqlite.php">05 &middot; + SQLite</a></li>
          <li><a href="../lessons/06-final-htmx.php">06 &middot; Final + HTMX</a></li>
          <li><a href="../lessons/07-github-upload.html">07 &middot; GitHub</a></li>
          <li><a href="../lessons/08-github-pages.html">08 &middot; GitHub Pages</a></li>
          <li><a href="../lessons/09-vercel.html">09 &middot; Vercel</a></li>
          <li><a href="../lessons/10-vercel-free-database.html">10 &middot; Free database</a></li>
          <li><a href="../lessons/11-cpanel-deploy.html">11 &middot; cPanel</a></li>
          <li><a href="../lessons/12-vscode-editing.html">12 &middot; VS Code</a></li>
          <li><a href="../lessons/13-ai-test-and-review.html">13 &middot; AI review</a></li>
          <li><a href="index.php" aria-current="page">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>The Guestbook</h1>
    <p>
      A complete CRUD app in a few files: post a message (create), read the
      list, edit any entry (update), remove one (delete). Every action is a
      small HTMX request &mdash; the page never fully reloads.
    </p>

    <section aria-labelledby="form-heading">
      <h2 id="form-heading">Leave a message</h2>

      <!-- Server validation errors land here (422 responses are swapped
           into this box by a small helper in main.js + the hx-swap-oob
           fragment from handlers.php). -->
      <div id="form-errors" role="alert" aria-live="assertive"></div>

      <form class="form" id="create-form"
            hx-post="handlers.php?action=create"
            hx-target="#message-list"
            hx-swap="afterbegin" novalidate>
        <div class="field">
          <label for="name">Your name <span class="muted">(required, max 60)</span></label>
          <input id="name" name="name" type="text" maxlength="60"
                 aria-describedby="name-hint">
          <p class="muted" id="name-hint">Up to 60 characters.</p>
        </div>
        <div class="field">
          <label for="message">Your message <span class="muted">(required, max 500)</span></label>
          <textarea id="message" name="message" maxlength="500"
                    aria-describedby="message-hint"></textarea>
          <p class="muted" id="message-hint">Up to 500 characters.</p>
        </div>
        <button class="button" type="submit">Post message</button>
      </form>
    </section>

    <section aria-labelledby="list-heading">
      <h2 id="list-heading">Messages</h2>

      <!-- The live counter. It refreshes itself whenever main.js
           announces "msgChanged" after a successful change. -->
      <p class="muted" id="message-count"
         hx-get="handlers.php?action=count"
         hx-trigger="msgChanged from:body"
         hx-swap="outerHTML">
        <span class="count-badge"><?php
          echo $count === 1 ? '1 message' : $count . ' messages';
        ?></span>
      </p>

      <!-- The list. Loaded with one hx-get when the page opens. -->
      <ul id="message-list" style="list-style:none;padding:0"
          hx-get="handlers.php?action=list"
          hx-trigger="load"
          hx-swap="innerHTML">
        <li class="empty-state">Loading messages&hellip;</li>
      </ul>
    </section>

    <p><a href="../index.php">&larr; Back to the course home page</a></p>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course &middot; the final app (lessons 4&ndash;6).</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/assets/css/style.css` (20143 bytes)

```css
/* ============================================================
   style.css ? the one stylesheet for the whole site.
   ------------------------------------------------------------
   Written mobile-first: the phone styles come first, then we
   add bigger-screen rules inside @media (min-width: ...) blocks.
   No CSS framework is used ? everything is plain CSS3.
   ============================================================ */

/* ------------------------------------------------------------
   1. Design tokens (CSS custom properties / "variables")
   ------------------------------------------------------------ */
:root {
  /* Light theme colors (the default). */
  --color-bg: #f7f8fa;
  --color-surface: #ffffff;
  --color-border: #d8dee6;
  --color-text: #1c2733;
  --color-text-muted: #4f5b66;
  --color-accent: #1a5fb4;
  --color-accent-hover: #164a8f;
  --color-accent-contrast: #ffffff;
  --color-danger: #b3261e;
  --color-danger-bg: #fdecea;
  --color-success-bg: #e6f4ea;
  --color-success-text: #1a7f37;
  --color-code-bg: #eef1f5;
  --color-code-border: #cfd8e3;
  --color-focus: #ff9900;

  /* Shadows and spacing scale. */
  --shadow-1: 0 1px 3px rgba(20, 30, 40, 0.08);
  --shadow-2: 0 4px 12px rgba(20, 30, 40, 0.10);
  --radius: 8px;
  --space-1: 0.25rem;
  --space-2: 0.5rem;
  --space-3: 1rem;
  --space-4: 1.5rem;
  --space-5: 2.5rem;

  /* Fluid type: grows with the screen, never too small or huge. */
  --fs-body: clamp(1rem, 0.95rem + 0.25vw, 1.125rem);
  --fs-h1: clamp(1.75rem, 1.4rem + 1.6vw, 2.5rem);
  --fs-h2: clamp(1.35rem, 1.2rem + 0.8vw, 1.8rem);
  --fs-h3: clamp(1.1rem, 1.05rem + 0.3vw, 1.3rem);
  --fs-small: clamp(0.82rem, 0.8rem + 0.15vw, 0.9rem);
}

/* Dark theme: the browser applies this automatically when the
   visitor's device asks for it (prefers-color-scheme: dark). */
@media (prefers-color-scheme: dark) {
  :root {
    --color-bg: #14181d;
    --color-surface: #1d232b;
    --color-border: #343d47;
    --color-text: #e8edf2;
    --color-text-muted: #a7b3bd;
    --color-accent: #6aa9f5;
    --color-accent-hover: #8cbeff;
    --color-accent-contrast: #10161c;
    --color-danger: #ff7b73;
    --color-danger-bg: #3a1d1b;
    --color-success-bg: #14301f;
    --color-success-text: #58d67f;
    --color-code-bg: #262d36;
    --color-code-border: #3a434e;
  }
}

/* ------------------------------------------------------------
   2. Reset and base elements
   ------------------------------------------------------------ */

/* A tiny, modern reset: consistent box sizes and margins. */
*,
*::before,
*::after {
  box-sizing: border-box;
}

html {
  /* Smooth jumps when clicking #anchors, unless reduced motion wins. */
  scroll-behavior: smooth;
}

body {
  margin: 0;
  font-family: system-ui, -apple-system, "Segoe UI", Roboto, Arial, sans-serif;
  font-size: var(--fs-body);
  line-height: 1.6;
  color: var(--color-text);
  background-color: var(--color-bg);
}

/* Stop long words and long code from stretching the page sideways.
   text wraps instead of creating a horizontal scrollbar. Note: no
   non-English characters appear anywhere in this file's comments. */
body,
p,
li,
h1, h2, h3, h4,
dd, dt, td, th, figcaption {
  overflow-wrap: break-word;
  word-break: break-word;
}

h1, h2, h3, h4 {
  line-height: 1.25;
  margin: 0 0 var(--space-3);
}

h1 { font-size: var(--fs-h1); }
h2 { font-size: var(--fs-h2); margin-top: var(--space-5); }
h3 { font-size: var(--fs-h3); margin-top: var(--space-4); }

p {
  margin: 0 0 var(--space-3);
}

a {
  color: var(--color-accent);
}

a:hover {
  color: var(--color-accent-hover);
}

img,
video,
iframe,
embed,
object {
  /* Never let media break out of (or overflow) its container. */
  max-width: 100%;
  height: auto;
}

/* Every interactive element gets a clear, visible focus outline. */
a:focus-visible,
button:focus-visible,
input:focus-visible,
textarea:focus-visible,
select:focus-visible,
summary:focus-visible,
[tabindex]:focus-visible {
  outline: 3px solid var(--color-focus);
  outline-offset: 2px;
}

/* Respect visitors who turn animations off in their OS settings. */
@media (prefers-reduced-motion: reduce) {
  html {
    scroll-behavior: auto;
  }
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
    scroll-behavior: auto !important;
  }
}

/* ------------------------------------------------------------
   3. Skip-to-content link (accessibility)
   ------------------------------------------------------------ */
/* Hidden until focused: keyboard users Tab onto it first and it
   jumps them past the header/navigation to the main content. */
.skip-link {
  position: absolute;
  left: var(--space-3);
  top: -100%; /* park it off-screen */
  z-index: 100;
  padding: var(--space-2) var(--space-3);
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  border-radius: 0 0 var(--radius) var(--radius);
}

.skip-link:focus {
  top: 0; /* slide it into view when focused */
}

/* ------------------------------------------------------------
   4. Header, language-free JS rule: selectors below style the
   site chrome (header, nav, footer) shared by every page.
   ------------------------------------------------------------ */
.site-header {
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
  box-shadow: var(--shadow-1);
}

.site-header .container {
  display: flex;
  flex-direction: column; /* mobile-first: brand on top, button + menu below */
  gap: var(--space-2);
  padding-top: var(--space-3);
  padding-bottom: var(--space-3);
}

.brand {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  font-weight: 700;
  font-size: var(--fs-h3);
  color: var(--color-text);
  text-decoration: none;
}

.brand:hover {
  color: var(--color-accent);
}

/* The round menu button (only visible on small screens, see media
   query near the end of this file). It is a real <button> in the
   HTML with aria-expanded, so screen readers announce it properly. */
.menu-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  min-width: 44px;       /* touch-target minimum (44x44 px) */
  min-height: 44px;
  padding: var(--space-2) var(--space-3);
  font-size: 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  color: var(--color-text);
  cursor: pointer;
  align-self: flex-start;
}

.menu-button:hover {
  background: var(--color-bg);
}

/* Button's own icon box, drawn with pure CSS (no image needed). */
.menu-button .menu-icon {
  display: inline-block;
  width: 1.1em;
  height: 1.1em;
  position: relative;
}

.menu-button .menu-icon::before,
.menu-button .menu-icon::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  height: 2px;
  background: currentColor;
  border-radius: 2px;
}

.menu-button .menu-icon::before { top: 30%; }
.menu-button .menu-icon::after  { bottom: 30%; }

/* The lesson navigation list. */
.site-nav {
  display: none; /* hidden on phones until the menu button opens it */
}

.site-nav.is-open {
  display: block;
}

.site-nav ul,
.progress-list ul {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: var(--space-2);
}

.site-nav a,
.progress-list a {
  display: inline-block;
  min-height: 44px;              /* touch target */
  padding: var(--space-2) var(--space-3);
  border-radius: var(--radius);
  text-decoration: none;
  color: var(--color-text);
}

.site-nav a:hover,
.progress-list a:hover {
  background: var(--color-bg);
  color: var(--color-accent);
}

.site-nav a[aria-current="page"] {
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  font-weight: 600;
}

/* ------------------------------------------------------------
   5. Page layout helpers
   ------------------------------------------------------------ */
.container {
  width: 100%;
  max-width: 72rem; /* 1152px */
  margin-inline: auto;
  padding-inline: clamp(0.75rem, 4vw, 1.5rem);
}

main {
  padding-block: var(--space-4);
}

main.container {
  padding-top: var(--space-4);
  padding-bottom: var(--space-5);
}

/* Two-column layout for lesson pages on wide screens:
   the lesson article main column + a "prev/next + progress" side. */
.lesson-layout {
  display: grid;
  gap: var(--space-4);
}

.card {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-1);
  padding: var(--space-4);
  margin-block: var(--space-3);
}

.site-footer {
  border-top: 1px solid var(--color-border);
  background: var(--color-surface);
  padding-block: var(--space-4);
  color: var(--color-text-muted);
  font-size: var(--fs-small);
}

.site-footer .container {
  display: grid;
  gap: var(--space-2);
}

/* ------------------------------------------------------------
   6. Code blocks
   ------------------------------------------------------------ */
/* Code always scrolls inside its own box and never stretches
   the page horizontally, even with the longest line. */
pre {
  background: var(--color-code-bg);
  border: 1px solid var(--color-code-border);
  border-radius: var(--radius);
  padding: var(--space-3);
  overflow-x: auto;      /* horizontal scroll, contained */
  overflow-y: hidden;
  max-width: 100%;
  font-size: var(--fs-small);
  line-height: 1.5;
}

pre code {
  display: block;
  white-space: pre;        /* keep formatting? */
  overflow-wrap: normal;
  word-break: normal;
}

/* Inline code (inside a sentence). */
code {
  font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
  background: var(--color-code-bg);
  border: 1px solid var(--color-code-border);
  border-radius: 4px;
  padding: 0.1em 0.35em;
  font-size: 0.92em;
}

pre code {
  background: transparent;
  border: 0;
  padding: 0;
}

/* The copy button sits above the code block (main.js adds one). */
.code-block {
  position: relative;
  margin-block: var(--space-3);
}

.code-block .copy-btn {
  position: absolute;
  top: var(--space-2);
  right: var(--space-2);
  min-width: 44px;
  min-height: 44px;
  padding: 0 var(--space-3);
  border: 1px solid var(--color-code-border);
  border-radius: 6px;
  background: var(--color-surface);
  color: var(--color-text);
  font-size: var(--fs-small);
  cursor: pointer;
}

.code-block .copy-btn:hover {
  background: var(--color-code-bg);
}

/* ------------------------------------------------------------
   7. Forms
   ------------------------------------------------------------ */
.form {
  display: grid;
  gap: var(--space-3);
  max-width: 34rem;
}

.field {
  display: grid;
  gap: var(--space-1);
}

.field label {
  font-weight: 600;
}

.field input,
.field textarea {
  width: 100%;
  padding: var(--space-2) var(--space-3);
  min-height: 44px;      /* touch target height */
  font: inherit;
  color: var(--color-text);
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
}

.field textarea {
  min-height: 6rem;
  resize: vertical;
}

.field input:focus,
.field textarea:focus {
  border-color: var(--color-accent);
}

/* Error alert block + per-field error text. */
.form-error {
  background: var(--color-danger-bg);
  color: var(--color-danger);
  border: 1px solid currentColor;
  border-radius: var(--radius);
  padding: var(--space-2) var(--space-3);
}

.field-error {
  color: var(--color-danger);
  font-size: var(--fs-small);
}

/* Show a red border on fields the server flagged. */
.field.has-error input,
.field.has-error textarea {
  border-color: var(--color-danger);
}

/* Success banner (lesson 4 demo). */
.form-success {
  background: var(--color-success-bg);
  color: var(--color-success-text);
  border: 1px solid currentColor;
  border-radius: var(--radius);
  padding: var(--space-2) var(--space-3);
}

/* Buttons must be big enough to tap easily. */
.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 44px;
  min-height: 44px;
  padding: var(--space-2) var(--space-4);
  font: inherit;
  font-weight: 600;
  border: 1px solid transparent;
  border-radius: var(--radius);
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  cursor: pointer;
  text-decoration: none;
}

.button:hover {
  background: var(--color-accent-hover);
}

.button-secondary {
  background: transparent;
  color: var(--color-accent);
  border-color: var(--color-border);
}

.button-secondary:hover {
  background: var(--color-bg);
}

.button-danger {
  background: transparent;
  color: var(--color-danger);
  border-color: var(--color-danger);
}

.button-danger:hover {
  background: var(--color-danger-bg);
}

/* ------------------------------------------------------------
   8. Lesson helper blocks ("What you'll learn", "Glimpse", etc.)
   ------------------------------------------------------------ */
.lesson-meta,
.lesson-nav {
  display: grid;
  gap: var(--space-3);
}

.learn-list {
  margin: 0;
  padding-left: 1.25rem;
}

.check-list {
  margin: 0;
  padding-left: 0;
  list-style: none;
  counter-reset: check;
  display: grid;
  gap: var(--space-2);
}

.check-list li {
  counter-increment: check;
  padding-left: 2.2rem;
  position: relative;
}

.check-list li::before {
  content: counter(check);
  position: absolute;
  left: 0;
  top: 0.1em;
  width: 1.6rem;
  height: 1.6rem;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  font-weight: 700;
  font-size: var(--fs-small);
}

/* CAUTION / TIP boxes. */
.caution {
  border-left: 4px solid var(--color-danger);
  background: var(--color-danger-bg);
  padding: var(--space-3);
  border-radius: 0 var(--radius) var(--radius) 0;
}

.tip {
  border-left: 4px solid var(--color-accent);
  background: var(--color-bg);
  padding: var(--space-3);
  border-radius: 0 var(--radius) var(--radius) 0;
}

table {
  width: 100%;
  border-collapse: collapse;
  margin-block: var(--space-3);
  font-size: var(--fs-small);
}

th,
td {
  text-align: left;
  vertical-align: top;
  border: 1px solid var(--color-border);
  padding: var(--space-2) var(--space-3);
}

th {
  background: var(--color-code-bg);
}

/* Allow the troubleshooting table to scroll inside its own box
   on tiny screens instead of stretching the page. */
.table-wrap {
  overflow-x: auto;
  margin-block: var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
}

.table-wrap table {
  margin: 0;
  border: 0;
  min-width: 40rem;
}

.table-wrap th,
.table-wrap td {
  border-bottom: 1px solid var(--color-border);
}

/* ------------------------------------------------------------
   9. Progress (index page + "Mark as done" checkbox)
   ------------------------------------------------------------ */
.progress-bar {
  height: 0.8rem;
  width: 100%;
  background: var(--color-code-bg);
  border: 1px solid var(--color-border);
  border-radius: 999px;
  overflow: hidden;
}

.progress-bar__fill {
  height: 100%;
  width: 0%;                 /* main.js sets the real width */
  background: linear-gradient(90deg, var(--color-accent), var(--color-accent-hover));
  border-radius: 999px;
  transition: width 0.4s ease;
}

.progress-list a {
  display: flex;
  align-items: center;
  min-width: 44px;
}

/* The "Mark as done" checkbox is styled but still a real input. */
.mark-done {
  display: flex;
  align-items: center;
  gap: var(--space-2);
  min-height: 44px;
  font-weight: 600;
  cursor: pointer;
}

.mark-done input[type="checkbox"] {
  width: 1.5rem;
  height: 1.5rem;
  min-width: 24px;
  min-height: 24px;
  accent-color: var(--color-accent);
  cursor: pointer;
}

/* ------------------------------------------------------------
   10. Guestbook list (app + lessons 5 and 6)
   ------------------------------------------------------------ */
.entry {
  background: var(--color-surface);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  box-shadow: var(--shadow-1);
  padding: var(--space-3);
  margin-bottom: var(--space-3);
}

.entry-header {
  display: flex;
  flex-wrap: wrap;          /* lets buttons wrap below the name */
  align-items: center;
  justify-content: space-between;
  gap: var(--space-2);
}

.entry-name {
  font-weight: 700;
  margin: 0;
}

.entry-date {
  color: var(--color-text-muted);
  font-size: var(--fs-small);
}

.entry-message {
  margin: var(--space-2) 0;
  white-space: pre-wrap; /* show line breaks the visitor typed */
}

.entry-actions {
  display: flex;
  gap: var(--space-2);
}

.entry-actions .button {
  min-width: 44px;
  min-height: 44px;
  font-size: var(--fs-small);
}

.empty-state {
  text-align: center;
  color: var(--color-text-muted);
  border: 2px dashed var(--color-border);
  border-radius: var(--radius);
  padding: var(--space-5) var(--space-3);
}

/* HTMX sends a spinner? We keep it simple: a small inline "busy"
   indicator shown via the htmx-injected classes. */
.htmx-indicator {
  opacity: 0;
  transition: opacity 0.2s ease;
}

.htmx-request .htmx-indicator,
.htmx-request.htmx-indicator {
  opacity: 1;
}

/* Counter badge for the live message count. */
.count-badge {
  display: inline-block;
  background: var(--color-accent);
  color: var(--color-accent-contrast);
  border-radius: 999px;
  padding: 0.15em 0.8em;
  font-size: var(--fs-small);
  font-weight: 700;
}

/* ------------------------------------------------------------
   11. Footer navigation (Previous / Next lesson)
   ------------------------------------------------------------ */
.pager {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-3);
  justify-content: space-between;
  margin-top: var(--space-5);
}

.pager a {
  min-height: 44px;
  display: inline-flex;
  align-items: center;
  padding: var(--space-2) var(--space-3);
  border: 1px solid var(--color-border);
  border-radius: var(--radius);
  background: var(--color-surface);
  text-decoration: none;
}

.pager a:hover {
  background: var(--color-bg);
  color: var(--color-accent);
}

/* ------------------------------------------------------------
   12. Bigger screens: tablet and up (space is available now)
   ------------------------------------------------------------ */
@media (min-width: 48em) {
  /* Header: brand left, everything else in one row. */
  .site-header .container {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
  }

  .menu-button {
    display: none; /* the real always-visible menu replaces it */
  }

  /* The nav is always visible and becomes a horizontal flex row. */
  .site-nav {
    display: block;
  }

  .site-nav ul {
    display: flex;
    flex-wrap: wrap;
    gap: var(--space-1) var(--space-2);
    justify-content: flex-end;
  }

  .site-nav a {
    padding: var(--space-1) var(--space-2);
    min-height: 44px;
    display: inline-flex;
    align-items: center;
  }
}

/* Lesson pages get the two-column layout: article + sidebar. */
@media (min-width: 60em) {
  .lesson-layout {
    grid-template-columns: minmax(0, 1fr) 16rem;
    align-items: start;
  }

  .lesson-side {
    position: sticky;
    top: var(--space-3);
  }

  /* The single glimpse/demo example blocks. */
  .lesson-aside {
    display: block;
  }
}

/* ------------------------------------------------------------
   13. Utility classes
   ------------------------------------------------------------ */
.badge {
  display: inline-block;
  font-size: var(--fs-small);
  padding: 0.1em 0.7em;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-code-bg);
  color: var(--color-text-muted);
}

.muted {
  color: var(--color-text-muted);
}

.text-center {
  text-align: center;
}

.visually-hidden {
  /* For screen-reader-only help text (e.g. required notes). */
  position: absolute;
  width: 1px;
  height: 1px;
  margin: -1px;
  padding: 0;
  overflow: hidden;
  clip: rect(0 0 0 0);
  white-space: nowrap;
  border: 0;
}
```

### `guestbook-site/public/assets/js/main.js` (7830 bytes)

```javascript
/*
 * main.js — the one JavaScript file for the whole site.
 *
 * It does three jobs:
 *   1. Remembers which lessons you marked as done (localStorage).
 *   2. Draws the progress bar on the index page from that list.
 *   3. Opens and closes the mobile navigation menu.
 *
 * It also adds a "Copy" button to every code block, because typing
 * commands by hand is slow and easy to get wrong.
 *
 * Everything is plain ES2015+ JavaScript. No libraries, no build step.
 */

/* ============================================================
   1. Lesson progress (localStorage)
   ------------------------------------------------------------
   localStorage is a tiny database built into every browser.
   It keeps small pieces of text, saved under a key, even after
   the browser is closed. We use the key "guestbook-lesson-progress"
   and store one lesson file name ("done") per marked lesson.
   ============================================================ */

const PROGRESS_KEY = 'guestbook-lesson-progress';

/**
 * Reads the saved progress object from localStorage.
 * Returns a plain object like { "lessons/01-glimpse.html": true }.
 * If nothing is saved (or the JSON is broken) it returns {}.
 */
function readProgress() {
  try {
    const raw = window.localStorage.getItem(PROGRESS_KEY);
    if (!raw) {
      return {};
    }
    const parsed = JSON.parse(raw);
    // Only keep it if it is really an object, never trust the store blindly.
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (err) {
    // Private-browsing modes can block storage. The site still works
    // without progress saving, so we just swallow the error.
    return {};
  }
}

/**
 * Saves the progress object back into localStorage.
 * Any storage error is ignored on purpose (see readProgress above).
 */
function writeProgress(progress) {
  try {
    window.localStorage.setItem(PROGRESS_KEY, JSON.stringify(progress));
  } catch (err) {
    /* storage unavailable: keep going without saving */
  }
}

/**
 * Marks a lesson as done or not done.
 * @param {string} lessonId - the slug used in data attributes, e.g. "01-glimpse"
 * @param {boolean} done
 */
function setLessonDone(lessonId, done) {
  const progress = readProgress();
  if (done) {
    progress[lessonId] = true;
  } else {
    delete progress[lessonId];
  }
  writeProgress(progress);
}

/**
 * Counts how many of the known lessons are marked done.
 * @param {string[]} lessonIds - every lesson the site knows about
 */
function countDone(lessonIds) {
  const progress = readProgress();
  return lessonIds.filter((id) => progress[id]).length;
}

/* ============================================================
   2. Wire up every "Mark as done" checkbox on the page
   ============================================================ */
function initProgressCheckboxes() {
  const checkboxes = document.querySelectorAll('[data-lesson-done]');

  checkboxes.forEach((box) => {
    const lessonId = box.getAttribute('data-lesson-done');

    // Show the saved state when the page loads.
    box.checked = Boolean(readProgress()[lessonId]);

    box.addEventListener('change', () => {
      setLessonDone(lessonId, box.checked);

      // Update the inline status text (if the page has one).
      const status = document.querySelector(`[data-lesson-status="${lessonId}"]`);
      if (status) {
        status.textContent = box.checked ? 'Done' : 'Not done yet';
      }

      // If this page owns a progress bar too (e.g. index page),
      // refresh it immediately.
      renderProgressBar();
    });
  });
}

/* ============================================================
   3. The progress bar on the index page
   ============================================================ */

/**
 * Looks for a progress bar and fills it according to saved progress.
 * The page tells us the lesson list via a data attribute:
 *   <div class="progress-bar" data-lessons="01-glimpse 02-html-css ...">
 */
function renderProgressBar() {
  const bar = document.querySelector('[data-progress-bar]');
  if (!bar) {
    return;
  }

  const lessonIds = (bar.getAttribute('data-lessons') || '')
    .split(/[\s,]+/)
    .filter(Boolean);

  const done = countDone(lessonIds);
  const total = lessonIds.length;
  const percent = total === 0 ? 0 : Math.round((done / total) * 100);

  const fill = bar.querySelector('.progress-bar__fill');
  if (fill) {
    fill.style.width = percent + '%';
  }

  // Update the "3 / 13 lessons done" caption if present.
  const caption = document.querySelector('[data-progress-caption]');
  if (caption) {
    caption.textContent = `${done} of ${total} lessons done`;
  }
}

/* ============================================================
   4. Copy buttons on code blocks
   ============================================================ */

/**
 * Wraps every <pre> in a container and adds a Copy button above it.
 * Clicking copies the code text to the clipboard (no clipboard.js
 * library needed — the browser has navigator.clipboard built in).
 */
function initCopyButtons() {
  document.querySelectorAll('pre').forEach((pre) => {
    // Never wrap the same block twice (.onView on reloaded pages).
    if (pre.parentElement && pre.parentElement.classList.contains('code-block')) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'code-block';

    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(pre);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'copy-btn';
    button.textContent = 'Copy';
    button.setAttribute('aria-label', 'Copy code to clipboard');

    button.addEventListener('click', async () => {
      const text = pre.innerText;
      try {
        await navigator.clipboard.writeText(text);
        button.textContent = 'Copied!';
      } catch (err) {
        // Older browsers may reject the clipboard API. Ignore quietly;
        // the learner can still select the code manually.
        button.textContent = 'Press Ctrl+C';
      }
      // Put the label back after a short pause.
      setTimeout(() => {
        button.textContent = 'Copy';
      }, 1500);
    });

    wrapper.appendChild(button);
  });
}

/* ============================================================
   5. The mobile menu button
   ============================================================ */

/**
 * Toggles the header navigation on small screens.
 * The button is a real <button aria-expanded="..."> so screen readers
 * announce whether the menu is open or closed.
 */
function initMenuButton() {
  const button = document.querySelector('[data-menu-button]');
  const nav = document.querySelector('[data-site-nav]');
  if (!button || !nav) {
    return; // page has no menu (nothing to do)
  }

  const setOpen = (open) => {
    nav.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  button.addEventListener('click', () => {
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    setOpen(!isOpen);
  });

  // Close the menu with the Escape key (keyboard accessibility).
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus(); // send keyboard focus back to the button
    }
  });
}

/* ============================================================
   6. Start everything once the page is ready
   ============================================================ */
function initMain() {
  initProgressCheckboxes();
  renderProgressBar();
  initCopyButtons();
  initMenuButton();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMain);
} else {
  initMain();
}
```

### `guestbook-site/public/index.php` (7554 bytes)

```php
<?php
/**
 * index.php — the home page.
 *
 * This is the lesson index. It is a .php file so the site keeps working
 * the same way on cPanel, but it only echoes static HTML: all the real
 * teaching content lives in the lessons/ folder.
 *
 * Lesson progress is stored in the visitor's browser (localStorage,
 * key "guestbook-lesson-progress") and the progress bar is drawn by
 * public/assets/js/main.js — no server-side tracking happens here.
 */

// Boot the shared engine (database helpers are not used on this page,
// but including config keeps every PHP page consistent).
require_once __DIR__ . '/../src/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Learn Web Deployment — the Guestbook Course</title>
  <meta name="description" content="A beginner course that takes you from a plain HTML page to a live CRUD app you deploy yourself.">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="index.php" aria-current="page">Home</a></li>
          <li><a href="lessons/01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="lessons/02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="lessons/03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="lessons/04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="lessons/05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="lessons/06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="lessons/07-github-upload.html">07 · GitHub</a></li>
          <li><a href="lessons/08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="lessons/09-vercel.html">09 · Vercel</a></li>
          <li><a href="lessons/10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="lessons/11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="lessons/12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="lessons/13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="app/index.php">💬 Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>From plain HTML to a live CRUD app</h1>
    <p>
      Welcome! This course teaches you, step by step, how a website is really
      built and how you put it online yourself. You start with one HTML file.
      Thirteen small lessons later you have built a guestbook (a wall of
      messages) with create, read, update and delete features, and you have
      deployed it to real hosting.
    </p>
    <p>
      No frameworks. No build tools. Just HTML, CSS, JavaScript, PHP and a
      SQLite database — the skills every web developer should understand first.
    </p>

    <section class="card" aria-labelledby="progress-heading">
      <h2 id="progress-heading">Your progress</h2>
      <div class="progress-bar" data-progress-bar
           data-lessons="01-glimpse 02-html-css 03-html-css-js 04-html-css-js-php
                         05-html-css-js-php-sqlite 06-final-htmx 07-github-upload
                         08-github-pages 09-vercel 10-vercel-free-database
                         11-cpanel-deploy 12-vscode-editing 13-ai-test-and-review">
        <div class="progress-bar__fill"></div>
      </div>
      <p class="muted" data-progress-caption>0 of 13 lessons done</p>
      <p class="muted">
        Progress is saved in your browser only (localStorage). It never leaves
        your computer, and it fills up as you tick <em>Mark as done</em> at the
        end of each lesson.
      </p>
    </section>

    <section aria-labelledby="all-lessons-heading">
      <h2 id="all-lessons-heading">All lessons</h2>
      <div class="progress-list">
        <ul>
          <?php
          // The lesson list is generated here so every page shows the
          // same names. The two-column table below mirrors the localStorage
          // IDs used by main.js.
          $lessons = [
            ['01-glimpse',                    'lessons/01-glimpse.html',                    '1. A first glimpse of HTML'],
            ['02-html-css',                   'lessons/02-html-css.html',                   '2. Making it pretty with CSS'],
            ['03-html-css-js',                'lessons/03-html-css-js.html',                '3. Adding JavaScript'],
            ['04-html-css-js-php',            'lessons/04-html-css-js-php.php',             '4. Meet PHP, the server language'],
            ['05-html-css-js-php-sqlite',     'lessons/05-html-css-js-php-sqlite.php',      '5. Saving messages with SQLite'],
            ['06-final-htmx',                 'lessons/06-final-htmx.php',                  '6. Finishing the app with HTMX'],
            ['07-github-upload',              'lessons/07-github-upload.html',              '7. Uploading the project to GitHub'],
            ['08-github-pages',               'lessons/08-github-pages.html',               '8. Free hosting with GitHub Pages'],
            ['09-vercel',                     'lessons/09-vercel.html',                     '9. Free hosting with Vercel'],
            ['10-vercel-free-database',       'lessons/10-vercel-free-database.html',       '10. Bonus: a free hosted database'],
            ['11-cpanel-deploy',              'lessons/11-cpanel-deploy.html',              '11. Deploying to cPanel shared hosting'],
            ['12-vscode-editing',             'lessons/12-vscode-editing.html',             '12. Editing comfortably in VS Code'],
            ['13-ai-test-and-review',         'lessons/13-ai-test-and-review.html',         '13. Using AI to test and review'],
          ];
          foreach ($lessons as [$id, $href, $title]) {
              echo '<li><a href="' . guestbook_escape($href) . '">'
                 . guestbook_escape($title)
                 . ' <span class="muted" data-lesson-status="' . guestbook_escape($id) . '"></span></a></li>';
          }
          ?>
        </ul>
      </div>
    </section>

    <section class="card" aria-labelledby="start-here-heading">
      <h2 id="start-here-heading">Start here</h2>
      <p>
        New to all of this? Open
        <a href="lessons/01-glimpse.html">Lesson 1 — A first glimpse of HTML</a>
        and follow the lessons in order. Each one has a live example you can
        try, code to copy, and a numbered "Check it works" list.
      </p>
      <p>
        Ready to see the finished product first?
        <a href="app/index.php">Open the final guestbook app</a> — it is what
        you will have built by the end of lesson 6.
      </p>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course — a free, beginner-friendly tour of the whole web stack.</p>
      <p>Progress is stored only in your own browser. Nothing about you is sent to any server.</p>
    </div>
  </footer>

  <script src="assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/01-glimpse.html` (7319 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 1 · A first glimpse of HTML</title>
  <!--
    Lesson 1 is DELIBERATELY plain.
    It links no stylesheet and no script. You are looking at raw HTML,
    exactly how the browser sees a page before any CSS arrives.
    Lesson 2 will add CSS to this file, and lesson 3 will add JavaScript.
  -->
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header>
    <div>
      <a href="../index.php">Back to the course home page</a>
    </div>
  </header>

  <nav aria-label="Lessons">
    <ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="01-glimpse.html" aria-current="page">01 · Glimpse</a></li>
      <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
      <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
      <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
      <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
      <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
      <li><a href="07-github-upload.html">07 · GitHub</a></li>
      <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
      <li><a href="09-vercel.html">09 · Vercel</a></li>
      <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
      <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
      <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
      <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
      <li><a href="../app/index.php">Guestbook app</a></li>
    </ul>
  </nav>

  <main id="main">
    <h1>Lesson 1 — a first glimpse of HTML</h1>

    <section aria-labelledby="what-youll-learn">
      <h2>What you'll learn</h2>
      <ul>
        <li>What HTML tags are and how the browser reads them.</li>
        <li>Tags and attributes.</li>
        <li>How a form works in plain HTML.</li>
        <li>Why this page has no CSS file linked on purpose.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        Everything here is live HTML. There are no styles attached — this is
        exactly how a browser shows a page made only of HTML tags.
      </p>

      <h3>A tiny guestbook form (live example)</h3>
      <!--
        A form collects user input.
          action  = where the browser sends the input when you submit.
          method  = HOW it sends it. "get" puts the data in the URL.
        Every input needs:
          id   = what the label uses (for="...") to link to this field.
          name = what the server will call this piece of data.
      -->
      <form action="#" method="get">
        <p>
          <label for="name">Your name</label><br>
          <input id="name" name="name" type="text">
        </p>
        <p>
          <label for="message">Your message</label><br>
          <input id="message" name="message" type="text">
        </p>
        <p><button type="submit">Sign the guestbook</button></p>
      </form>
      <p class="tip">
        Try submitting the form. Because method="get", your name and message
        appear in the page address as ?name=...&amp;message=... — your first
        look at how a browser sends data to a server.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>
      <p>
        Lesson 1 needs no file of your own: this page IS the example. Here is
        its full source, simplified, so you can rebuild it yourself in a new
        file called <code>my-first-page.html</code>:
      </p>
      <pre><code>&lt;!DOCTYPE html&gt;
&lt;html lang="en"&gt;
&lt;head&gt;
  &lt;meta charset="UTF-8"&gt;
  &lt;meta name="viewport" content="width=device-width, initial-scale=1"&gt;
  &lt;title&gt;My first page&lt;/title&gt;
&lt;/head&gt;
&lt;body&gt;
  &lt;h1&gt;Hello, web!&lt;/h1&gt;

  &lt;p&gt;This page is written in plain HTML.&lt;/p&gt;

  &lt;form action="#" method="get"&gt;
    &lt;p&gt;
      &lt;label for="name"&gt;Your name&lt;/label&gt;
      &lt;input id="name" name="name" type="text"&gt;
    &lt;/p&gt;
    &lt;p&gt;
      &lt;label for="message"&gt;Your message&lt;/label&gt;
      &lt;input id="message" name="message" type="text"&gt;
    &lt;/p&gt;
    &lt;p&gt;&lt;button type="submit"&gt;Send&lt;/button&gt;&lt;/p&gt;
  &lt;/form&gt;
&lt;/body&gt;
&lt;/html&gt;</code></pre>

      <h3>How to read this</h3>
      <ul>
        <li>
          <strong>Tags</strong> are words in angle brackets, like
          <code>&lt;p&gt;</code>. They describe what something is: a
          paragraph, a heading, a form. Most come in pairs — an opening tag
          and a closing tag with a slash, like <code>&lt;/p&gt;</code>.
        </li>
        <li>
          <strong>Attributes</strong> give a tag extra settings inside the
          opening tag. <code>type="text"</code> tells the input to show a one
          line text box. Attributes
          are always written <code>name="value"</code>.
        </li>
        <li>
          <strong>&lt;head&gt;</strong> holds page settings the visitor does not
          see (the title, the character set).
        </li>
        <li>
          <strong>&lt;body&gt;</strong> holds everything the visitor CAN see.
        </li>
      </ul>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Create a new file called <code>my-first-page.html</code>.</li>
        <li>Copy the code above into it and save.</li>
        <li>Double-click the file — it opens in your browser.</li>
        <li>You see a big "Hello, web!" heading and a form with two boxes.</li>
        <li>Type a name and message, then press "Send".</li>
        <li>Look at the address bar: it now ends with
          <code>?name=Ada&amp;message=Hello</code>. The GET method put your
          form data into the URL — that is what a server would receive.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <p>
        Stuck, or just curious? Copy this prompt into any AI assistant
        (ChatGPT, Claude, Gemini…) and it will help you with this lesson:
      </p>
      <pre><code>I am a complete beginner learning HTML from a course called
"The Guestbook Course". I am on Lesson 1: a first glimpse of HTML.

 please explain in very simple words:
1. What an HTML tag is, and the difference between a tag, an element,
   and an attribute. Give me 3 example attributes I will actually use.
2. How an HTML form with method="get" sends data, and where the data
   appears afterwards.
3. What &lt;!DOCTYPE html&gt;, &lt;head&gt; and &lt;body&gt; each do.

Then give me one tiny exercise to check I understood.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="01-glimpse">
      Mark as done
    </label>

    <div class="pager">
      <a href="../index.php">&larr; Course overview</a>
      <a href="02-html-css.html">Next: Lesson 2 — HTML + CSS &rarr;</a>
    </div>
  </main>

  <footer>
    <p>The Guestbook Course · Lesson 1 of 13</p>
  </footer>
</body>
</html>
```

### `guestbook-site/public/lessons/02-html-css.html` (7337 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 2 · HTML + CSS</title>
  <!--
    Lesson 2: the same guestbook page as lesson 1, but now styled.
    The stylesheet link below is the only difference in the head:
      rel="stylesheet" = this file is CSS
      href             = where the CSS file lives
    CSS separates LOOKS from STRUCTURE. HTML says what a thing is,
    CSS says what it looks like.
  -->
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
    </div>
  </header>

  <nav class="site-nav is-open" aria-label="Lessons" data-site-nav>
    <ul>
      <li><a href="../index.php">Home</a></li>
      <li><a href="01-glimpse.html">01 · Glimpse</a></li>
      <li><a href="02-html-css.html" aria-current="page">02 · HTML + CSS</a></li>
      <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
      <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
      <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
      <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
      <li><a href="07-github-upload.html">07 · GitHub</a></li>
      <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
      <li><a href="09-vercel.html">09 · Vercel</a></li>
      <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
      <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
      <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
      <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
      <li><a href="../app/index.php">Guestbook app</a></li>
    </ul>
  </nav>

  <main class="container" id="main">
    <h1>Lesson 2 — HTML + CSS: making it look right</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>The difference between HTML (structure) and CSS (appearance).</li>
        <li>How to link a stylesheet file to a page.</li>
        <li>Selectors: how CSS knows which element to style.</li>
        <li>The box model: every element is a box with padding, border, margin.</li>
        <li>Flexbox: an easy way to line things up in a row.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        This page uses the exact same HTML ideas as lesson 1, but now it links
        <code>assets/css/style.css</code>. See how the headings, form, buttons
        and layout changed — the structure did not.
      </p>

      <form class="form" action="#" method="get">
        <div class="field">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text">
        </div>
        <div class="field">
          <label for="message">Your message</label>
          <textarea id="message" name="message"></textarea>
        </div>
        <button class="button" type="submit">Sign the guestbook</button>
      </form>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>

      <h3>1. Link the stylesheet from your page</h3>
      <pre><code>&lt;link rel="stylesheet" href="../assets/css/style.css"&gt;</code></pre>

      <h3>2. A tiny stylesheet to start with</h3>
      <pre><code>/* style.css — plain CSS, no frameworks */

/* A selector is everything before the { }. It says WHICH
   elements the rules below apply to. Here: every &lt;body&gt;. */
body {
  font-family: Arial, sans-serif; /* which font to draw with   */
  margin: 0;                      /* remove the default gap    */
  padding: 1rem;                  /* space INSIDE the body box  */
}

/* One class selector: matches every element with class="card". */
.card {
  background: #ffffff;
  border: 1px solid #cccccc;   /* the box model: border        */
  padding: 1rem;               /* space inside the border      */
  margin: 1rem 0;              /* space outside the border     */
  border-radius: 8px;          /* rounded corners              */
}

/* Group two selectors with a comma: both get these styles. */
h1, h2 {
  color: #1a5fb4;
}</code></pre>

      <h3>The box model</h3>
      <p>
        The browser draws every element as a rectangular box with four layers:
      </p>
      <ol>
        <li><strong>content</strong> — your text or image;</li>
        <li><strong>padding</strong> — clear space inside the border;</li>
        <li><strong>border</strong> — the line drawn around the padding;</li>
        <li><strong>margin</strong> — clear space outside the border that
          pushes neighbours away.</li>
      </ol>
      <pre><code>/* Visualise it: give any element a temporary red border. */
.card {
  border: 2px solid red;
}</code></pre>

      <h3>Flexbox</h3>
      <p>
        Flexbox is a layout mode. Turn a container into a flex container and
        its children line up in a row (or column) and share the space:
      </p>
      <pre><code>/* Line up buttons in a row with a gap between them. */
.row {
  display: flex;
  gap: 0.5rem;
  align-items: center;   /* centre them vertically */
}</code></pre>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Take your <code>my-first-page.html</code> from lesson 1.</li>
        <li>Add the <code>&lt;link rel="stylesheet"&gt;</code> line to it.</li>
        <li>Create <code>assets/css/style.css</code> next to it.</li>
        <li>Copy the tiny stylesheet into that file and save both.</li>
        <li>Reload the page. The text changes font and cards get a border.</li>
        <li>Open the browser dev tools (F12) and click a heading. The Styles
          panel shows which rules apply — and any rules that were crossed
          out because another rule overrode them.</li>
        <li>Shrink the window. Nothing breaks: CSS you wrote applies at every
          width (this site is built mobile-first with relative units).</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a complete beginner learning CSS from "The Guestbook Course".
I am on Lesson 2: HTML + CSS.

Please explain in very simple words, with tiny worked examples:
1. How a CSS selector works. Show me a tag selector, a class selector,
   and a descendant selector.
2. The CSS box model: content, padding, border, margin, and how
   box-sizing: border-box changes the maths.
3. What display: flex does, and what gap and align-items do.

Then quiz me with three short questions.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="02-html-css">
      Mark as done
    </label>

    <div class="pager">
      <a href="01-glimpse.html">&larr; Lesson 1</a>
      <a href="03-html-css-js.html">Next: Lesson 3 — + JavaScript &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 2 of 13</p>
    </div>
  </footer>
</body>
</html>
```

### `guestbook-site/public/lessons/03-html-css-js.html` (7870 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 3 · HTML + CSS + JavaScript</title>
  <!--
    Lesson 3 adds JavaScript to lessons 1 and 2.
    The script tag goes at the END of body so the HTML above it
    already exists when the script runs.
  -->
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html" aria-current="page">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 3 — adding JavaScript</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>What JavaScript is for and where it runs.</li>
        <li>Events: how the page reacts to clicks and typing.</li>
        <li>The DOM: how JavaScript sees and changes the page.</li>
        <li>localStorage: a tiny database inside your browser.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        This page includes <code>assets/js/main.js</code>. Two things on this
        page are already driven by it:
      </p>
      <ol>
        <li>Press <kbd>Ctrl</kbd>+<kbd>C</kbd>-style "Copy" buttons appear
          above every code block on this page — that is JavaScript added them
          at runtime.</li>
        <li>The menu button in the header only appears on small screens, and
          opening/closing it is handled by JavaScript (with
          <code>aria-expanded</code> updated for screen readers).</li>
      </ol>
      <p>
        The "Mark as done" checkbox at the bottom of the page saves to
        <strong>localStorage</strong> under the key
        <code>guestbook-lesson-progress</code>. Tick it, close the browser,
        come back — it remembers. Fill this form and watch the live counter:
      </p>

      <form class="form" action="#" method="get" data-live-count>
        <div class="field">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text" maxlength="60">
        </div>
        <div class="field">
          <label for="message">Your message</label>
          <textarea id="message" name="message" maxlength="500"></textarea>
        </div>
        <p>
          Typed so far: <output>0</output> characters.
        </p>
        <button class="button" type="submit">Sign the guestbook</button>
      </form>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>

      <h3>1. Load the script at the end of body</h3>
      <pre><code>&lt;script src="../assets/js/main.js"&gt;&lt;/script&gt;</code></pre>

      <h3>2. Reacting to an event</h3>
      <pre><code>// Find an element the same way you select it in CSS.
const button = document.querySelector('.button');

// Run this function every time the button is clicked.
button.addEventListener('click', function () {
  console.log('The button was clicked!');
});</code></pre>

      <h3>3. Reading and changing the DOM</h3>
      <p>
        The DOM (Document Object Model) is the browser's map of the page.
        JavaScript can read it, change it, and create new nodes in it:
      </p>
      <pre><code>// Read text.
const heading = document.querySelector('h1');
console.log(heading.textContent);

// Change it.
heading.textContent = 'JavaScript changed this!';

// Create something brand new and add it to the page.
const p = document.createElement('p');
p.textContent = 'I was created by JavaScript.';
document.body.appendChild(p);</code></pre>

      <h3>4. Using localStorage</h3>
      <pre><code>// Save a value under a key.
localStorage.setItem('guestbook-lesson-progress', '{"03": true}');

// Read it back (localStorage only stores TEXT, so use JSON).
const saved = JSON.parse(
  localStorage.getItem('guestbook-lesson-progress') || '{}'
);
console.log(saved);

// Remove it.
// localStorage.removeItem('guestbook-lesson-progress');</code></pre>
      <p class="caution">
        localStorage is <strong>not private and not secure</strong>: anything
        on the page can read it, and it never leaves the browser. It is fine
        for remembering "you finished lesson 3" and similar small things, but
        never store passwords or other secret data in it.
      </p>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Add the <code>&lt;script src="../assets/js/main.js"&gt;&lt;/script&gt;</code>
          line before <code>&lt;/body&gt;</code> in your page.</li>
        <li>Reload. A <em>Copy</em> button now floats on every code block.</li>
        <li>Click Copy, then paste somewhere (empty text file): the code is
          the same as on screen.</li>
        <li>Tick "Mark as done" and reload the page: the checkbox stays
          ticked — it was saved to localStorage.</li>
        <li>Tick it on two or three lessons, then open the index page: the
          progress bar reflects what you ticked.</li>
        <li>Open dev tools (F12) &rarr; Application &rarr; Local Storage and
          find the key <code>guestbook-lesson-progress</code>. There it is.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a complete beginner learning JavaScript from "The Guestbook
Course". I am on Lesson 3: HTML + CSS + JavaScript.

Please explain in very simple words, with tiny examples:
1. What an event is, and how addEventListener works.
2. What the DOM is, and how querySelector and textContent work.
3. What localStorage is, why JSON.parse is needed, and when NOT
   to use it.

Then give me a 5-line exercise to write myself.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="03-html-css-js">
      Mark as done
    </label>

    <div class="pager">
      <a href="02-html-css.html">&larr; Lesson 2</a>
      <a href="04-html-css-js-php.php">Next: Lesson 4 — + PHP &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 3 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/04-html-css-js-php.php` (9881 bytes)

```php
<?php
/**
 * Lesson 4 — the first PHP page.
 *
 * PHP code runs on the SERVER. The visitor's browser never sees the
 * PHP parts: it only receives the finished HTML that PHP printed.
 * Load this page "View source" and try to find <?php - you cannot.
 */
require_once __DIR__ . '/../../src/config.php';

// ---- Read the submitted form (only if a POST request arrived) ----------
$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$name    = guestbook_field($_POST, 'name');
$message = guestbook_field($_POST, 'message');

// Server-side validation: browsers can be bypassed, so the server
// must ALWAYS check the data itself.
$errors = $submitted ? guestbook_validate($name, $message) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 4 · HTML + CSS + JS + PHP</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">&#128216; The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 &middot; Glimpse</a></li>
          <li><a href="02-html-css.html">02 &middot; HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 &middot; + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php" aria-current="page">04 &middot; + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 &middot; + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 &middot; Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 &middot; GitHub</a></li>
          <li><a href="08-github-pages.html">08 &middot; GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 &middot; Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 &middot; Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 &middot; cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 &middot; VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 &middot; AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 4 — meet PHP, the server language</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>What a request and a response are.</li>
        <li>Why PHP runs on the server and not in the browser.</li>
        <li>How a form posts data with POST and PHP reads it from <code>$_POST</code>.</li>
        <li>Why validation must happen on the server.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The form below posts to <strong>this same page</strong> using PHP.
        Fill it in and press submit: the server receives your data, checks it,
        and echoes it back.
      </p>

      <?php if ($submitted && !$errors): ?>
        <div class="form-success" role="status">
          Thanks, <strong><?php echo guestbook_escape($name); ?></strong>!
          You posted this message:
          <blockquote>&ldquo;<?php echo guestbook_escape($message); ?>&rdquo;</blockquote>
        </div>
      <?php endif; ?>

      <?php if ($submitted && $errors): ?>
        <div class="form-error" role="alert">
          The server found problems. It will not accept the data until they
          are fixed:
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo guestbook_escape($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="form" method="post" action="04-html-css-js-php.php" novalidate>
        <div class="field<?php echo isset($errors['name']) ? ' has-error' : ''; ?>">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text" maxlength="60"
                 value="<?php echo guestbook_escape($submitted ? $name : ''); ?>"
                 aria-describedby="<?php echo isset($errors['name']) ? 'name-error' : ''; ?>">
          <?php if (isset($errors['name'])): ?>
            <p class="field-error" id="name-error"><?php echo guestbook_escape($errors['name']); ?></p>
          <?php endif; ?>
        </div>
        <div class="field<?php echo isset($errors['message']) ? ' has-error' : ''; ?>">
          <label for="message">Your message</label>
          <textarea id="message" name="message" maxlength="500"
                    aria-describedby="<?php echo isset($errors['message']) ? 'message-error' : ''; ?>"><?php echo guestbook_escape($submitted ? $message : ''); ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <p class="field-error" id="message-error"><?php echo guestbook_escape($errors['message']); ?></p>
          <?php endif; ?>
        </div>
        <button class="button" type="submit">Send it to the server</button>
      </form>

      <p class="tip">
        Try submitting with an empty name, or paste 601 characters into the
        message box (add your own text after this sentence, ten more than one
        second ago). The server rejects bad data with a clear message: that is
        server-side validation.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>
      <p>
        The PHP of this page, simplified to the part that makes it interactive:
      </p>
      <pre><code>&lt;?php
// Run BEFORE any HTML is printed: read the form (if any).
$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$name      = trim($_POST['name'] ?? '');
$message   = trim($_POST['message'] ?? '');

$errors = [];
if ($name === '')            { $errors['name'] = 'Please enter your name.'; }
if ($message === '')         { $errors['message'] = 'Please write a short message.'; }
?&gt;

&lt;!-- ...normal HTML... --&gt;

&lt;form method="post" action="04-html-css-js-php.php"&gt;
  &lt;input name="name"  value="&lt;?= htmlspecialchars($name) ?&gt;"&gt;
  &lt;textarea name="message"&gt;&lt;?= htmlspecialchars($message) ?&gt;&lt;/textarea&gt;
  &lt;button type="submit"&gt;Send&lt;/button&gt;
&lt;/form&gt;</code></pre>

      <h3>What these words mean</h3>
      <ul>
        <li><strong>Request / response:</strong> the browser <em>requests</em>
          a page; the server <em>responds</em> with HTML. Every click that
          loads a page is a full request-response round trip.</li>
        <li><strong>PHP:</strong> a language the server executes while building
          the response. The browser never sees PHP code, only its output.</li>
        <li><strong><code>$_POST</code>:</strong> a built-in PHP list of the
          form fields that were posted, ordered by name:
          <code>$_POST['name']</code>.</li>
        <li><strong><code>htmlspecialchars()</code>:</strong> makes submitted
          text safe to print so nobody can inject HTML or JavaScript into
          your page (this stops a common attack called XSS).</li>
      </ul>

      <h3>Why the server must check (validate) again</h3>
      <p>
        HTML attributes like <code>maxlength</code> only stop friendly users.
        Anyone can post a request with curl or by editing the DOM, so the
        server always checks the data itself. That is why
        <code>guestbook_validate()</code> runs in PHP before anything is
        accepted.
      </p>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Run the local PHP server from the project root (lesson 12 shows
          how): <code>php -S localhost:8000 -t public</code>.</li>
        <li>Open <code>http://localhost:8000/lessons/04-html-css-js-php.php</code>.</li>
        <li>Type a name and a message and submit: the page reloads and echoes
          your message back in a green box.</li>
        <li>Submit with an empty field: the server reports an error and marks
          the field in red.</li>
        <li>View the page source (Ctrl+U): there is no PHP visible, only the
          final HTML the server produced.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner learning PHP from a course. I am on Lesson 4:
server-side form handling.

My page posts to itself and reads $_POST. Please explain:
1. The request/response cycle, and what "the page posts to itself"
   means.
2. Why $_SERVER["REQUEST_METHOD"] is checked before reading $_POST.
3. Why every echoed value must go through htmlspecialchars(), and
   what XSS attack it prevents.

Then review this small PHP snippet and tell me what is wrong with it:

&lt;?php echo "Hello " . $_POST["name"]; ?&gt;</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="04-html-css-js-php">
      Mark as done
    </label>

    <div class="pager">
      <a href="03-html-css-js.html">&larr; Lesson 3</a>
      <a href="05-html-css-js-php-sqlite.php">Next: Lesson 5 — + SQLite &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course &middot; Lesson 4 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/05-html-css-js-php-sqlite.php` (11112 bytes)

```php
<?php
/**
 * Lesson 5 — saving messages with SQLite.
 *
 * SQLite is a database stored in ONE file. We talk to it with PDO,
 * which stands for "PHP Data Objects": a safe, built-in way to run
 * SQL. Prepared statements (see below) stop SQL injection.
 */
require_once __DIR__ . '/../../src/config.php';
guestbook_create_table();

$submitted = ($_SERVER['REQUEST_METHOD'] === 'POST');
$name    = guestbook_field($_POST, 'name');
$message = guestbook_field($_POST, 'message');
$errors  = $submitted ? guestbook_validate($name, $message) : [];

// ---- Save the message when the form is valid ---------------------------
if ($submitted && !$errors) {
    // A prepared statement sends the SQL skeleton with ? placeholders.
    // The actual values travel separately, so nobody can turn user
    // input into new SQL code (that attack is "SQL injection").
    $stmt = guestbook_db()->prepare(
        'INSERT INTO messages (name, message) VALUES (:name, :message)'
    );
    $stmt->execute([
        ':name'    => $name,
        ':message' => $message,
    ]);

    // Redirect after POST (the "Post/Redirect/Get" pattern) so that
    // pressing F5 does not submit the same message twice.
    header('Location: 05-html-css-js-php-sqlite.php?saved=1');
    exit;
}

// ---- Read all messages, newest first -----------------------------------
$rows = guestbook_db()
    ->query('SELECT id, name, message, created_at FROM messages ORDER BY id DESC')
    ->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 5 · HTML + CSS + JS + PHP + SQLite</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">&#128216; The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 &middot; Glimpse</a></li>
          <li><a href="02-html-css.html">02 &middot; HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 &middot; + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 &middot; + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php" aria-current="page">05 &middot; + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 &middot; Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 &middot; GitHub</a></li>
          <li><a href="08-github-pages.html">08 &middot; GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 &middot; Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 &middot; Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 &middot; cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 &middot; VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 &middot; AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 5 — saving messages with SQLite</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>What a database table, row and primary key are.</li>
        <li>How PHP talks to a database with PDO.</li>
        <li>Why prepared statements stop SQL injection.</li>
        <li>Why <code>guestbook.sqlite</code> lives OUTSIDE <code>public/</code>.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The form below saves into <code>data/guestbook.sqlite</code> and the
        messages are read back from the database below it. Submissions are
        remembered even after you close the browser.
      </p>

      <?php if (isset($_GET['saved'])): ?>
        <p class="form-success" role="status">Message saved. It is now in the table below.</p>
      <?php endif; ?>

      <?php if ($submitted && $errors): ?>
        <div class="form-error" role="alert">
          The server found problems:
          <ul>
            <?php foreach ($errors as $error): ?>
              <li><?php echo guestbook_escape($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      <?php endif; ?>

      <form class="form" method="post"
            action="05-html-css-js-php-sqlite.php" novalidate>
        <div class="field<?php echo isset($errors['name']) ? ' has-error' : ''; ?>">
          <label for="name">Your name</label>
          <input id="name" name="name" type="text" maxlength="60"
                 value="<?php echo guestbook_escape($submitted ? $name : ''); ?>"
                 aria-describedby="<?php echo isset($errors['name']) ? 'name-error' : ''; ?>">
          <?php if (isset($errors['name'])): ?>
            <p class="field-error" id="name-error"><?php echo guestbook_escape($errors['name']); ?></p>
          <?php endif; ?>
        </div>
        <div class="field<?php echo isset($errors['message']) ? ' has-error' : ''; ?>">
          <label for="message">Your message</label>
          <textarea id="message" name="message" maxlength="500"
                    aria-describedby="<?php echo isset($errors['message']) ? 'message-error' : ''; ?>"><?php echo guestbook_escape($submitted ? $message : ''); ?></textarea>
          <?php if (isset($errors['message'])): ?>
            <p class="field-error" id="message-error"><?php echo guestbook_escape($errors['message']); ?></p>
          <?php endif; ?>
        </div>
        <button class="button" type="submit">Save to the database</button>
      </form>

      <h2>All messages</h2>
      <?php if (!$rows): ?>
        <p class="empty-state">No messages yet. You could be the first!</p>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <article class="entry">
            <div class="entry-header">
              <p class="entry-name"><?php echo guestbook_escape($row['name']); ?></p>
              <span class="entry-date"><?php echo guestbook_escape($row['created_at']); ?></span>
            </div>
            <p class="entry-message"><?php echo guestbook_escape($row['message']); ?></p>
          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>
      <p>The saving part, exactly as it happens on this page:</p>
      <pre><code>&lt;?php
// config.php already created $db, a PDO connection.
$stmt = $db-&gt;prepare(
    'INSERT INTO messages (name, message) VALUES (:name, :message)'
);
$stmt-&gt;execute([
    ':name'    =&gt; $name,
    ':message' =&gt; $message,
]);
?&gt;</code></pre>

      <h3>The database words, slowly</h3>
      <ul>
        <li><strong>Table</strong> &mdash; one kind of thing you store, here
          "messages". Created by <code>CREATE TABLE IF NOT EXISTS messages (...)</code>.</li>
        <li><strong>Row</strong> &mdash; one message: one line inside the
          table, with a name, a message and a timestamp.</li>
        <li><strong>Primary key</strong> &mdash; the column that uniquely
          identifies each row. Ours is <code>id</code>; SQLite numbers rows
          1, 2, 3&hellip; automatically.</li>
        <li><strong>PDO</strong> &mdash; PHP's built-in database library.
          Connection string: <code>new PDO('sqlite:' . $path)</code>.</li>
        <li><strong>Prepared statement</strong> &mdash; SQL with placeholders.
          The values never become part of the SQL text, so special
          characters in a message (like <code>'</code>) cannot break or
          hijack the query.</li>
      </ul>

      <h3>Why the .sqlite file sits outside public/</h3>
      <div class="caution">
        <p>
          <code>public/</code> is what the web server shares with the world.
          Anything inside it can be downloaded by URL. The database contains
          visitor messages, so it must not be inside <code>public/</code>.
        </p>
        <p>
          That is why the project looks like this:
        </p>
        <pre><code>guestbook-site/
  public/    &lt;-- what visitors can reach
  src/       &lt;-- PHP code, one level above public
  data/      &lt;-- guestbook.sqlite, one level above public</code></pre>
        <p>
          PHP (which runs ON the server) can read and write
          <code>data/guestbook.sqlite</code> through the filesystem even
          though no browser URL points at it. The folder also contains an
          <code>.htaccess</code> file that says "Require all denied" as a
          second layer of defence.
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Make sure the local server from lesson 4 is running
          (<code>php -S localhost:8000 -t public</code>, from the project root).</li>
        <li>Open this lesson page in the browser.</li>
        <li>Post a message. The page redirects and your message appears in
          the list.</li>
        <li>Close the browser fully. Reopen the page: the message is still
          there &mdash; it lives in the database now.</li>
        <li>Look in the <code>data/</code> folder of the project:
          <code>guestbook.sqlite</code> was created on first run.</li>
        <li>Look at <code>.gitignore</code>: <code>data/*.sqlite</code> is in
          it, so the database will never be uploaded to GitHub.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner learning PHP and SQLite from a course. Lesson 5.

Please explain, with a tiny worked example each:
1. What a table, a row, a column and a primary key are.
2. What PDO is and how a connection string like "sqlite:data.db"
   works.
3. What a prepared statement is, and HOW it stops SQL injection.
   Show me the dangerous version of the same query first, then the
   safe one.

Then write a 10-line PHP example that inserts and lists rows
safely.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="05-html-css-js-php-sqlite">
      Mark as done
    </label>

    <div class="pager">
      <a href="04-html-css-js-php.php">&larr; Lesson 4</a>
      <a href="06-final-htmx.php">Next: Lesson 6 — final app with HTMX &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course &middot; Lesson 5 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/06-final-htmx.php` (11432 bytes)

```php
<?php
/**
 * Lesson 6 — the final stack: HTMX.
 *
 * HTMX is a small JavaScript library loaded from a CDN (unpkg.com).
 * You add attributes like hx-post="..." to normal HTML, and HTMX
 * swaps in the server's HTML response WITHOUT reloading the page.
 *
 * The server now returns HTML FRAGMENTS (a piece of HTML, not a
 * whole page). That is the big idea of this lesson.
 */
require_once __DIR__ . '/../../src/config.php';
guestbook_create_table();

$method = $_SERVER['REQUEST_METHOD'];
$demo   = $_GET['demo'] ?? '';

// ------------------------------------------------------------------
// Demo endpoint 1: list a few messages (an HTML fragment, not a page)
// ------------------------------------------------------------------
if ($method === 'GET' && $demo === 'list') {
    $rows = guestbook_db()
        ->query('SELECT id, name, message, created_at FROM messages ORDER BY id DESC LIMIT 5')
        ->fetchAll();

    foreach ($rows as $row) {
        echo '<article class="entry">'
           . '<div class="entry-header">'
           . '<p class="entry-name">' . guestbook_escape($row['name']) . '</p>'
           . '<span class="entry-date">' . guestbook_escape($row['created_at']) . '</span>'
           . '</div>'
           . '<p class="entry-message">' . guestbook_escape($row['message']) . '</p>'
           . '</article>';
    }
    if (!$rows) {
        echo '<p class="empty-state">Nothing yet &mdash; post below!</p>';
    }
    exit; // stop here: the browser gets ONLY this fragment
}

// ------------------------------------------------------------------
// Demo endpoint 2: create a message and answer with a fragment
// ------------------------------------------------------------------
if ($method === 'POST' && $demo === 'create') {
    $name    = guestbook_field($_POST, 'name');
    $message = guestbook_field($_POST, 'message');
    $errors  = guestbook_validate($name, $message);

    // A 422 status code means "I understood you, but the data is bad".
    // HTMX keeps the page intact; the error fragment below is swapped in.
    if ($errors) {
        http_response_code(422);
        echo '<div class="form-error" role="alert"><ul>';
        foreach ($errors as $error) {
            echo '<li>' . guestbook_escape($error) . '</li>';
        }
        echo '</ul></div>';
        exit;
    }

    $stmt = guestbook_db()->prepare(
        'INSERT INTO messages (name, message) VALUES (:name, :message)'
    );
    $stmt->execute([':name' => $name, ':message' => $message]);

    // 201 = "Created". The body is the fresh entry as an HTML fragment.
    http_response_code(201);
    echo '<article class="entry">'
       . '<p class="entry-name">' . guestbook_escape($name) . '</p>'
       . '<p class="entry-message">' . guestbook_escape($message) . '</p>'
       . '</article>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 6 · Final stack with HTMX</title>
  <link rel="stylesheet" href="../assets/css/style.css">
  <!-- HTMX itself, loaded straight from a public CDN. -->
  <script src="https://unpkg.com/htmx.org@1.9.12"></script>
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">&#128216; The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 &middot; Glimpse</a></li>
          <li><a href="02-html-css.html">02 &middot; HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 &middot; + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 &middot; + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 &middot; + SQLite</a></li>
          <li><a href="06-final-htmx.php" aria-current="page">06 &middot; Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 &middot; GitHub</a></li>
          <li><a href="08-github-pages.html">08 &middot; GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 &middot; Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 &middot; Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 &middot; cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 &middot; VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 &middot; AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 6 — the final stack: HTMX</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>What HTMX does and why it exists.</li>
        <li>The three attributes every HTMX request uses:
          <code>hx-post</code>/<code>hx-get</code>,
          <code>hx-target</code> and <code>hx-swap</code>.</li>
        <li>Why the server returns HTML fragments instead of full pages.</li>
        <li>How HTTP status codes (201, 422) fit into HTMX.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The list below is loaded with <code>hx-get</code> when the page opens,
        and posting to the form appends your entry with <code>hx-post</code>
        &mdash; <strong>without a page reload</strong>. Watch the network tab:
        you will not see a full page navigation, just one small request.
      </p>

      <ul class="lesson-list" id="lesson-entries" style="list-style:none;padding:0"
          hx-get="06-final-htmx.php?demo=list"
          hx-trigger="load"
          hx-swap="innerHTML">
        <li class="empty-state">Loading&hellip;</li>
      </ul>

      <form class="form" hx-post="06-final-htmx.php?demo=create"
            hx-target="#lesson-entries" hx-swap="afterbegin" novalidate>
        <div class="field">
          <label for="demo-name">Your name</label>
          <input id="demo-name" name="name" type="text" maxlength="60">
        </div>
        <div class="field">
          <label for="demo-message">Your message</label>
          <textarea id="demo-message" name="message" maxlength="500"></textarea>
        </div>
        <button class="button" type="submit">Post without reloading</button>
      </form>

      <!-- HTMX swaps the 422 error fragment in here. -->
      <div id="demo-errors" role="alert"></div>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add</h2>

      <h3>1. Load HTMX from its CDN</h3>
      <pre><code>&lt;script src="https://unpkg.com/htmx.org@1.9.12"&gt;&lt;/script&gt;</code></pre>

      <h3>2. Make HTML interactive with three attributes</h3>
      <pre><code>&lt;!-- The form: WHERE to send, WHAT to update, HOW to update it. --&gt;
&lt;form hx-post="handlers.php?action=create"
      hx-target="#message-list"
      hx-swap="afterbegin"&gt;
  ...
&lt;/form&gt;

&lt;!-- The list fills itself when the page opens. --&gt;
&lt;ul id="message-list" hx-get="handlers.php?action=list"
    hx-trigger="load" hx-swap="innerHTML"&gt;&lt;/ul&gt;</code></pre>

      <h3>What each attribute does</h3>
      <ul>
        <li><code>hx-post</code> / <code>hx-get</code> &mdash; which URL to
          call (and with which HTTP method) when this element triggers.</li>
        <li><code>hx-target</code> &mdash; which element of THIS page receives
          the server's response. <code>#lesson-entries</code> is a CSS
          selector, same as in JavaScript.</li>
        <li><code>hx-swap</code> &mdash; how the response lands there:
          <code>innerHTML</code> replaces the contents,
          <code>afterbegin</code> inserts at the top (newest first!),
          <code>outerHTML</code> replaces the element itself.</li>
        <li><code>hx-trigger="load"</code> &mdash; fire the request
          automatically when the element loads.</li>
      </ul>

      <h3>Why the server returns fragments, not pages</h3>
      <p>
        HTMX puts the server's response text into the target element. If the
        server answered with a whole page, you would get a page inside your
        list! So PHP handlers echo just the piece of HTML that should appear:
        one <code>&lt;li&gt;</code>, one <code>&lt;article&gt;</code>, one
        <code>&lt;tr&gt;</code>. This pattern &mdash; server returns HTML
        fragments &mdash; is the core idea behind HTMX.
      </p>

      <h3>Status codes are how the server talks</h3>
      <ul>
        <li><strong>200 OK</strong> &mdash; everything went fine (updates).</li>
        <li><strong>201 Created</strong> &mdash; a new row was saved
          (creates).</li>
        <li><strong>422 Unprocessable Content</strong> &mdash; validation
          failed; the body describes what is wrong.</li>
        <li><strong>404 / 405 / 500</strong> &mdash; not found, method not
          allowed, server error. The final app (lesson <a
          href="../app/index.php">Guestbook app</a>) returns these correctly
          and never shows a raw PHP error.</li>
      </ul>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Run the local server: <code>php -S localhost:8000 -t public</code>
          from the project root.</li>
        <li>Open <code>http://localhost:8000/lessons/06-final-htmx.php</code>.</li>
        <li>On load, recent messages appear in the list without a reload.</li>
        <li>Post a message. It appears at the top of the list instantly.</li>
        <li>Post with an empty field: a red error box appears; the page did
          NOT reload.</li>
        <li>Open dev tools &rarr; Network. Every action is one small request
          returning a fragment.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner finishing "The Guestbook Course" (Lesson 6, HTMX).

Please explain:
1. What HTMX is, in one paragraph, compared with writing the same
   thing with fetch() and innerHTML by hand.
2. What hx-post, hx-get, hx-target and hx-swap each do, with one
   example each.
3. Why the server must answer with an HTML FRAGMENT instead of a
   full page, and what happens if it does not.
4. How HTTP status codes 200, 201 and 422 are used with HTMX forms.

Then show me a minimal form + fragment example I can try.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="06-final-htmx">
      Mark as done
    </label>

    <div class="pager">
      <a href="05-html-css-js-php-sqlite.php">&larr; Lesson 5</a>
      <a href="07-github-upload.html">Next: Lesson 7 — upload to GitHub &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course &middot; Lesson 6 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/07-github-upload.html` (8246 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 7 · Upload the project to GitHub</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html" aria-current="page">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 7 — upload the project to GitHub</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>What Git and GitHub are (they are NOT the same thing).</li>
        <li>Method A: uploading with the terminal (git commands).</li>
        <li>Method B: uploading by dragging files on github.com.</li>
        <li>Why <code>.gitignore</code> keeps the database out of the repo.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        When this lesson is done, your project looks like this on
        <code>github.com/your-name/guestbook-site</code> — every file in the
        browser, with its history:
      </p>
      <pre><code>guestbook-site/            (the repository root)
  public/                  index.php, lessons/, app/, assets/
  src/                     config.php
  data/                    .gitkeep only — NO guestbook.sqlite!
  .gitignore
  README.md</code></pre>
      <p class="tip">
        Git = the version-control tool that records snapshots of your files,
        running on YOUR computer. GitHub = a website that stores those
        snapshots online and lets other people see them.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Code to add / run</h2>

      <h3>Method A — with the terminal</h3>
      <p>
        Open a terminal INSIDE your project folder (in VS Code: menu
        Terminal &rarr; New Terminal). Then run these commands one by one.
        Each line is explained below.
      </p>
      <pre><code>git init
git add .
git commit -m "First version of the guestbook course"
git branch -M main
git remote add origin https://github.com/YOUR-NAME/guestbook-site.git
git push -u origin main</code></pre>
      <ul>
        <li><code>git init</code> — turn this folder into a Git repository
          (a "repo": a project Git watches).</li>
        <li><code>git add .</code> — stage (select) every file for the next
          snapshot. The dot means "everything here".</li>
        <li><code>git commit -m "..."</code> — save a snapshot with a short
          description. Nothing is on the internet yet.</li>
        <li><code>git branch -M main</code> — name the current line of
          history "main" (GitHub's default name).</li>
        <li><code>git remote add origin URL</code> — connect your local repo
          to the empty GitHub repo you created on the website.</li>
        <li><code>git push -u origin main</code> — upload the snapshot to
          GitHub. <code>-u</code> remembers the link for next time; then
          <code>git push</code> alone is enough.</li>
      </ul>

      <h3>Method B — by dragging files (no terminal)</h3>
      <ol>
        <li>Create an account on <strong>github.com</strong> if you do not
          have one.</li>
        <li>Click the <strong>+</strong> (top right) &rarr;
          <strong>New repository</strong>. Name it
          <code>guestbook-site</code>. Keep it Public. Do NOT tick "add a
          README" (you already have one). Click <strong>Create</strong>.</li>
        <li>On the new empty repo page, click the link
          <strong>uploading an existing file</strong>.</li>
        <li>Drag the CONTENTS of your project folder (the files inside it)
          into the drop area.</li>
        <li>Type a message like "First version" and click
          <strong>Commit changes</strong>.</li>
      </ol>
    </section>

    <section aria-labelledby="why-gitignore">
      <h2 id="why-gitignore">Why .gitignore and .gitkeep matter</h2>
      <div class="caution">
        <p>
          <code>.gitignore</code> lists files Git should NEVER save. Our file
          ignores <code>data/*.sqlite</code>: your database holds real visitor
          messages and must not land in a public repo. GitHub is public —
          anything pushed there can be read by anyone.
        </p>
        <p>
          But Git does not track empty folders, and on some hosts the
          <code>data/</code> folder must exist before the first run. The
          empty file <code>data/.gitkeep</code> is the usual trick: a file
          with no content, just so Git has something to save inside
          <code>data/</code>.
        </p>
        <p>
          After the first run on a server, <code>guestbook.sqlite</code> is
          created by PHP and stays only on that server. Its content is yours
          to manage; the repo never sees it.
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Open <code>https://github.com/YOUR-NAME/guestbook-site</code> in
          a browser.</li>
        <li>You see the same folder tree as on your computer.</li>
        <li>Click <code>data/</code>: only <code>.gitkeep</code> is there.</li>
        <li>Check the repo file list: no <code>.sqlite</code> file anywhere.</li>
        <li>(Terminal users) Run <code>git status</code> later — it shows
          which files changed and are not committed yet.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a complete beginner using Git and GitHub for the first time.
I am on Lesson 7 of "The Guestbook Course".

Please explain:
1. The difference between git (the tool) and GitHub (the website).
2. What init, add, commit, branch, remote, and push each do — one
   sentence each, with an everyday-life comparison.
3. What a .gitignore file is for, and why I would add an empty
   .gitkeep file to keep a folder in a repo.

Then give me a practice exercise: undo a commit safely.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="07-github-upload">
      Mark as done
    </label>

    <div class="pager">
      <a href="06-final-htmx.php">&larr; Lesson 6</a>
      <a href="08-github-pages.html">Next: Lesson 8 — GitHub Pages &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 7 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/08-github-pages.html` (6150 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 8 · Deploy to GitHub Pages</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html" aria-current="page">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 8 — free hosting with GitHub Pages</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>How to switch on GitHub Pages for your repository.</li>
        <li>Which parts of the project can live there.</li>
        <li>What GitHub Pages CANNOT do (this matters!).</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        After this lesson your lessons are online at a real public URL, like:
      </p>
      <pre><code>https://YOUR-NAME.github.io/guestbook-site/public/index.php</code></pre>
      <p>
        (GitHub Pages serves files as-is; <code>index.php</code> links still
        open the page but PHP code inside is NOT executed — see the warning
        below.)
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Steps</h2>
      <ol>
        <li>Open your repository on github.com.</li>
        <li>Click <strong>Settings</strong> (top bar).</li>
        <li>In the left menu click <strong>Pages</strong> (under "Code and
          automation").</li>
        <li>Under "Build and deployment", set <strong>Source</strong> to
          <strong>Deploy from a branch</strong>.</li>
        <li>Choose branch <strong>main</strong> and folder
          <strong>/ (root)</strong>, then click <strong>Save</strong>.</li>
        <li>Wait 1–3 minutes. The Settings &rarr; Pages screen then shows a
          green box with your URL.</li>
        <li>Open the URL and add <code>/public/index.php</code> to see the
          lessons.</li>
      </ol>
    </section>

    <section aria-labelledby="limits">
      <h2 id="limits">What GitHub Pages can and cannot do</h2>
      <div class="caution">
        <p>
          <strong>GitHub Pages serves STATIC files only.</strong> It can host
          the HTML, CSS and JavaScript lessons — everything you built in
          lessons 1–3 works fine there.
        </p>
        <p>
          <strong>PHP does not run and SQLite does not exist there.</strong>
          Pages has no PHP interpreter and no filesystem your code can write
          to. The guestbook app (lessons 4–6) will NOT work on GitHub Pages.
        </p>
        <p>
          For the live CRUD app, continue to <a href="11-cpanel-deploy.html">
          Lesson 11 — cPanel</a>. Vercel (lesson 9) also serves only static
          files for this project.
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Settings &rarr; Pages shows a green "Your site is live at…"
          message.</li>
        <li>The URL opens and you can click through lessons 1, 2, 3, 7, 8.</li>
        <li>Copy buttons and the lesson checkboxes still work on the live
          site (JS and localStorage work fine on Pages).</li>
        <li>Open <code>/public/app/index.php</code> on the live site: the
          browser DOWNLOADS the raw PHP file or shows nothing useful. That is
          the "no PHP here" rule made visible.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner using GitHub Pages for the first time (Lesson 8
of a course). My repo has HTML lessons plus a PHP/SQLite app.

Please explain:
1. What "static hosting" means, and why PHP does not run on
   GitHub Pages.
2. What "Deploy from a branch" does when I save Settings &gt; Pages.
3. How I could host the static lessons there while running the
   PHP app somewhere else.

Keep the answer short and friendly.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="08-github-pages">
      Mark as done
    </label>

    <div class="pager">
      <a href="07-github-upload.html">&larr; Lesson 7</a>
      <a href="09-vercel.html">Next: Lesson 9 — Vercel &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 8 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/09-vercel.html` (6401 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 9 · Deploy to Vercel (free Hobby plan)</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html" aria-current="page">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 9 — free hosting with Vercel</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>How to deploy the static lesson site on Vercel's free Hobby plan.</li>
        <li>What Vercel is good at (static files, instant redeploys).</li>
        <li>Why PHP and SQLite do not persist there.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        When you finish, your lessons live on a URL like
        <code>guestbook-site-XXXX.vercel.app</code> — and every future
        <code>git push</code> to main redeploys it automatically. That is the
        magic of Vercel: hosting tied to your repository.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Steps</h2>
      <ol>
        <li>Go to <strong>vercel.com</strong> and sign up with your GitHub
          account (the free <strong>Hobby</strong> plan is what you want).</li>
        <li>Click <strong>Add New… &rarr; Project</strong>.</li>
        <li>Find <code>guestbook-site</code> in the list of your GitHub repos
          and click <strong>Import</strong>.</li>
        <li>
          In the import screen leave everything default: Framework Preset
          <strong>Other</strong>, no build command, output directory
          <code>public</code>. Then click <strong>Deploy</strong>.
        </li>
        <li>Wait about a minute. Vercel shows a confetti screen with your
          URL — open it.</li>
        <li>From now on, every <code>git push</code> to <code>main</code>
          redeploys the site automatically.</li>
      </ol>
    </section>

    <section aria-labelledby="limits">
      <h2 id="limits">The fine print for this project</h2>
      <div class="caution">
        <p>
          <strong>Vercel serves this project as static files.</strong> The
          HTML/CSS/JS lessons work. But:
        </p>
        <ul>
          <li><strong>PHP is not executed</strong> — Vercel does not run a
            PHP interpreter for a plain static deployment like ours.</li>
          <li><strong>The filesystem is ephemeral.</strong> Even where code
            runs (their serverless functions), files written during a request
            are wiped when it ends: storage resets between requests. A SQLite
            file saved today can be gone in the next request, so a
            file-based database cannot live there.</li>
        </ul>
        <p>
          Lesson 10 shows the Vercel-hosted route to a real database; lesson
          11 is the classic cPanel path where our current code runs unchanged.
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Your project dashboard on vercel.com shows "Ready" with a domain
          link.</li>
        <li>The lessons open at that domain and styling is intact (CSS
          loaded).</li>
        <li>Lesson checkboxes still remember your ticks (localStorage works
          client-side anywhere).</li>
        <li>Open <code>/public/app/index.php</code> there: no guestbook — the
          PHP is served as plain text or 404s. Expected!</li>
        <li>Edit a lesson file locally, commit, push — the live site updates
          within a minute or two.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I deployed a static site to Vercel on the free Hobby plan
(Lesson 9 of a beginner course).

Please explain:
1. What "static deployment" means on Vercel and what happens when
   I git push afterwards.
2. Why files written during a request do not persist on Vercel
   (the term is "ephemeral filesystem").
3. My options for saving data on Vercel's free plan.

Be concise; I am a beginner.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="09-vercel">
      Mark as done
    </label>

    <div class="pager">
      <a href="08-github-pages.html">&larr; Lesson 8</a>
      <a href="10-vercel-free-database.html">Next: Lesson 10 — free database &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 9 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/10-vercel-free-database.html` (8102 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 10 · Bonus: a free hosted database (optional)</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html" aria-current="page">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 10 (optional) — a free hosted database</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>Why a hosted database fixes Vercel's "ephemeral filesystem" problem.</li>
        <li>How to add a free Neon Postgres database through the Vercel
          Marketplace on the Hobby plan.</li>
        <li>What changes in the PHP code (from <code>sqlite:</code> to
          <code>pgsql:</code>).</li>
        <li>Why you must always check the provider's current free-tier limits.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The change is small: your connection string switches from a local
        file to a URL stored in an environment variable:
      </p>
      <pre><code>// Before (SQLite file on disk):
$db = new PDO('sqlite:' . $path);

// After (hosted Postgres, URL from an environment variable):
$url = getenv('POSTGRES_URL');
$db  = new PDO($url);</code></pre>
      <p>
        Same PDO, same prepared statements, same PHP. Only the driver changes.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Steps and code</h2>

      <h3>1. Create the database in the Vercel Marketplace</h3>
      <ol>
        <li>Open your project on <strong>vercel.com</strong>.</li>
        <li>Open the <strong>Storage</strong> tab &rarr;
          <strong>Create Database</strong>.</li>
        <li>Choose <strong>Neon</strong> (a hosted Postgres provider
          available through the Vercel Marketplace, with a free tier usable
          on the Hobby plan).</li>
        <li>Accept the defaults and create it. Vercel then adds
          <code>POSTGRES_URL</code> (and similar variables) to your project's
          environment variables automatically.</li>
      </ol>

      <h3>2. What changes in the code (conceptual example)</h3>
      <pre><code>&lt;?php
// src/config.php — the hosted-database variant (sketch).

// The connection string comes from the environment. It looks like:
// postgres://user:password@host/dbname
// It is a SECRET: it lives on the server, never in your code or repo.
$url = getenv('POSTGRES_URL');

$db = new PDO($url, null, null, [
    PDO::ATTR_ERRMODE =&gt; PDO::ERRMODE_EXCEPTION,
]);

// Postgres uses SERIAL instead of SQLite's AUTOINCREMENT,
// and DOUBLE PRECISION quotes differ. Table creation differs slightly:
$db-&gt;exec(
    'CREATE TABLE IF NOT EXISTS messages (
        id         SERIAL PRIMARY KEY,
        name       TEXT NOT NULL,
        message    TEXT NOT NULL,
        created_at TIMESTAMPTZ NOT NULL DEFAULT now()
    )'
);
?&gt;</code></pre>
      <p class="tip">
        Everything else you learned stays true: prepared statements, escaped
        output, the same queries (minor SQL dialect differences aside).
        <code>SQLITE AUTOINCREMENT</code> becomes
        <code>SERIAL</code>; <code>datetime('now')</code> becomes
        <code>now()</code>.
      </p>

      <h3>3. Keep the secret safe</h3>
      <p>
        The Postgres URL contains a password. It must only live in Vercel's
        environment variables (Project &rarr; Settings &rarr; Environment
        Variables) — never inside a file you commit. Your
        <code>.gitignore</code> habit from lesson 7 applies to any local
        <code>.env</code> file too.
      </p>
    </section>

    <section aria-labelledby="limits">
      <h2 id="limits">Free-tier reality check</h2>
      <div class="caution">
        <p>
          Free tiers change often. Before you depend on one, read the
          provider's current pricing page and check:
        </p>
        <ul>
          <li>monthly storage and compute limits;</li>
          <li>whether the database "sleeps" when idle and takes seconds to
            wake (Neon's free tier does);</li>
          <li>whether a credit card is required;</li>
          <li>what happens if you exceed the free allowance.</li>
        </ul>
        <p>
          Vercel's Hobby plan is for personal, non-commercial use. Always
          verify on <strong>vercel.com/pricing</strong> and
          <strong>neon.tech/pricing</strong> before you build on it.
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>The Vercel project's Storage tab shows your Neon database as
          connected.</li>
        <li><code>Settings &rarr; Environment Variables</code> lists
          <code>POSTGRES_URL</code> — and its value is masked, not shown.</li>
        <li>Your PHP code reads the variable with <code>getenv()</code> and
          connects without errors (try it locally with
          <code>php -S</code> and a copy of the URL in your shell
          environment).</li>
        <li>Posting a message persists: restart the server, the message is
          still there (it now lives in Postgres, not a file).</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner (Lesson 10 of a course). I want to replace a local
SQLite file with a free hosted Neon Postgres database on Vercel.

Please explain:
1. What an environment variable is and why the database URL must
   NOT be committed to the repo.
2. The differences between SQLite and Postgres I must know for this
   small app: AUTOINCREMENT vs SERIAL, and datetime('now') vs now().
3. What "serverless database sleeping" means.

Then show me the smallest safe PDO snippet for Postgres.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="10-vercel-free-database">
      Mark as done
    </label>

    <div class="pager">
      <a href="09-vercel.html">&larr; Lesson 9</a>
      <a href="11-cpanel-deploy.html">Next: Lesson 11 — cPanel &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 10 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/11-cpanel-deploy.html` (9870 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 11 · Deploy the CRUD app to cPanel</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html" aria-current="page">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 11 — deploy the CRUD app to cPanel</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>How cPanel shared hosting is laid out on the server.</li>
        <li>Where each project folder belongs.</li>
        <li>How to check PHP 8.x and the PDO SQLite driver.</li>
        <li>Folder permissions and a troubleshooting table for the classics.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The target layout on the hosting account (File Manager view):
      </p>
      <pre><code>home/your-username/          (the account's home folder — NOT visible to visitors)
  src/
    config.php               &lt;-- PHP can read this; browsers cannot
  data/
    .htaccess                &lt;-- "Require all denied"
    guestbook.sqlite         &lt;-- created on first run
  public_html/               (this IS the web root: your "public" folder)
    index.php
    lessons/
    app/
      index.php
      handlers.php
    assets/
      css/style.css
      js/main.js</code></pre>
      <p class="tip">
        The relative includes keep working: <code>public/app/index.php</code>
        asks for <code>../../src/config.php</code>, which lands in the home
        folder's <code>src/</code>. No code changes needed when uploading.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Steps</h2>
      <ol>
        <li>
          <strong>Zip the project.</strong> On your computer, zip the
          <code>guestbook-site</code> folder (right-click &rarr; Send to
          &rarr; Compressed folder on Windows).
        </li>
        <li>
          <strong>Log in to cPanel</strong> and open
          <strong>File Manager</strong>.
        </li>
        <li>
          <strong>Upload and extract.</strong> Upload the zip into your home
          folder, right-click it &rarr; Extract.
        </li>
        <li>
          <strong>Move public's CONTENTS into public_html.</strong> Your
          hosting's web root is <code>public_html</code>. Move everything
          from <code>guestbook-site/public/</code> INTO
          <code>public_html/</code> (index.php, lessons/, app/, assets/).
        </li>
        <li>
          <strong>Move src/ and data/ one level above public_html.</strong>
          Drag the whole <code>src</code> and <code>data</code> folders from
          <code>guestbook-site/</code> into your home folder, so they sit
          NEXT TO <code>public_html</code>.
        </li>
        <li>
          <strong>Check the PHP version.</strong> In cPanel open
          <strong>MultiPHP Manager</strong>, select your domain, and set PHP
          to a <strong>version 8.x</strong>. (This project needs 8.x.)
        </li>
        <li>
          <strong>Check the SQLite driver.</strong> Open
          <strong>Select PHP Version &rarr; Extensions</strong> (or your
          host's PHP settings) and confirm
          <code>pdo_sqlite</code> and <code>sqlite3</code> are enabled. On
          most hosts they are on by default.
        </li>
        <li>
          <strong>Set data/ permissions.</strong> In File Manager,
          right-click <code>data</code> &rarr; Permissions. PHP must be able
          to write the database file there. Usually
          <strong>755</strong> is enough; if you get "unable to open database
          file" errors, try <strong>775</strong>.
        </li>
        <li>
          <strong>Open your site.</strong> Visit
          <code>https://your-domain.com/</code> for the lessons and
          <code>https://your-domain.com/app/index.php</code> for the
          guestbook. Post a message!
        </li>
      </ol>
    </section>

    <section aria-labelledby="troubleshooting">
      <h2 id="troubleshooting">Troubleshooting</h2>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Symptom</th>
              <th scope="col">Likely cause</th>
              <th scope="col">Fix</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>500 Internal Server Error</strong></td>
              <td>PHP crashed. Common causes: PHP version below 8.x, a
                syntax error, or a bad <code>.htaccess</code> rule.</td>
              <td>Check <code>error_log</code> in your home folder (File
                Manager) for the real reason. Confirm 8.x in MultiPHP
                Manager. Remove or fix any custom <code>.htaccess</code> you
                added.</td>
            </tr>
            <tr>
              <td><strong>“unable to open database file”</strong></td>
              <td>Wrong path or <code>data/</code> is not writable by PHP.</td>
              <td>Check that <code>src/</code> and <code>data/</code> are
                siblings of <code>public_html</code> (see the tree above).
                Set <code>data/</code> permissions to 755, or 775 if needed.</td>
            </tr>
            <tr>
              <td><strong>Blank page</strong></td>
              <td>PHP is failing but <code>display_errors</code> is off in
                production.</td>
              <td>Look in <code>error_log</code>. While debugging you can
                temporarily enable display errors in
                <strong>Select PHP Version &rarr; Options</strong>; switch it
                back off afterwards.</td>
            </tr>
            <tr>
              <td><strong>HTMX not loading</strong> (post does nothing)</td>
              <td>The CDN script was blocked or the URL is wrong.</td>
              <td>Open dev tools &rarr; Console/Network. Confirm
                <code>unpkg.com/htmx.org@1.9.12</code> returns 200. If your
                school/office network blocks CDNs, download htmx.js and load
                it from <code>assets/js/</code> instead.</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>The lesson index opens at <code>https://your-domain.com/</code>.</li>
        <li>Posting in the guestbook works; the message survives a reload
          (it is in <code>guestbook.sqlite</code> on the server now).</li>
        <li>Open <code>https://your-domain.com/data/guestbook.sqlite</code>
          in the browser: you get a forbidden page, not a download. The
          <code>.htaccess</code> is working.</li>
        <li>Open <code>https://your-domain.com/src/config.php</code>: also
          forbidden/nothing — PHP files outside public_html cannot be
          reached by URL at all.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner deploying a small PHP + SQLite site to cPanel
shared hosting (Lesson 11 of a course). My layout:

  ~/src/config.php
  ~/data/           (755)
  ~/public_html/    (contents of my "public" folder)

I get [PASTE YOUR SYMPTOM HERE: 500 error / blank page /
"unable to open database file" / HTMX silent failure].

Here is the last 30 lines of my error_log:
[PASTE LOG LINES]

Walk me through the most likely cause step by step, and tell me
exactly what to click in cPanel to fix it.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="11-cpanel-deploy">
      Mark as done
    </label>

    <div class="pager">
      <a href="10-vercel-free-database.html">&larr; Lesson 10</a>
      <a href="12-vscode-editing.html">Next: Lesson 12 — VS Code &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 11 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/12-vscode-editing.html` (7938 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 12 · Editing comfortably in VS Code</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html" aria-current="page">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 12 — editing comfortably in VS Code</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>How to open the project folder the right way.</li>
        <li>Which extensions to install (recommended in
          <code>.vscode/extensions.json</code>).</li>
        <li>When to use Live Server (static pages only).</li>
        <li>How to run PHP locally with
          <code>php -S localhost:8000 -t public</code>.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        Two local servers, two jobs — mixing them up is the classic beginner
        mistake:
      </p>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th scope="col">Server</th>
              <th scope="col">Command</th>
              <th scope="col">Serves</th>
              <th scope="col">Runs PHP?</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td><strong>Live Server</strong> (VS Code extension)</td>
              <td>click "Go Live"</td>
              <td>HTML/CSS/JS lessons</td>
              <td>No — PHP files come out as raw text</td>
            </tr>
            <tr>
              <td><strong>PHP built-in server</strong></td>
              <td><code>php -S localhost:8000 -t public</code></td>
              <td>everything</td>
              <td>Yes</td>
            </tr>
          </tbody>
        </table>
      </div>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Steps</h2>
      <ol>
        <li>
          <strong>Open the folder, not a file.</strong> VS Code menu
          File &rarr; Open Folder… &rarr; choose
          <code>guestbook-site</code>. Opening the FOLDER (not a single
          file) is what makes the editor understand your project.
        </li>
        <li>
          <strong>Install the recommended extensions.</strong> VS Code pops
          up a notification with the recommendations from
          <code>.vscode/extensions.json</code>; install all three:
          <ul>
            <li><em>Intelephense</em> — understands PHP: autocomplete,
              jump-to-definition, and error highlighting.</li>
            <li><em>Live Server</em> — one-click local server for static
              pages, with auto-reload on save.</li>
            <li><em>Live Server (ms-vscode.live-server)</em> — the newer
              official variant; either of the two works.</li>
          </ul>
        </li>
        <li>
          <strong>Static pages:</strong> click "Go Live" in the bottom-right
          status bar. Your HTML lessons open with automatic reload whenever
          you save a file.
        </li>
        <li>
          <strong>PHP pages:</strong> use the terminal instead. In VS Code:
          Terminal &rarr; New Terminal, then:
          <pre><code>php -S localhost:8000 -t public</code></pre>
          <code>-S localhost:8000</code> starts the server on port 8000;
          <code>-t public</code> makes <code>public/</code> the web root.
          Then open
          <code>http://localhost:8000/lessons/06-final-htmx.php</code>.
        </li>
      </ol>
      <div class="caution">
        <p>
          <strong>Run the PHP server from the PROJECT ROOT</strong> (the
          folder that contains <code>public/</code>, <code>src/</code> and
          <code>data/</code>). The page code points at
          <code>../src/config.php</code> relative to the FILES — this always
          resolves while PHP is started with
          <code>-t public</code> from the root. If you start it inside
          <code>public/</code>, the database and config land in the wrong
          place or the include fails.
        </p>
        <p>
          On the first run, the SQLite file is created automatically in
          <code>data/</code>. If you get a permissions error, check that the
          folder exists and is writable (<code>data/.gitkeep</code> keeps it
          in Git even when empty).
        </p>
      </div>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Open the project folder in VS Code; the Explorer shows
          public/, src/, data/.</li>
        <li>The three recommended extensions appear under
          Extensions &rarr; Installed.</li>
        <li>"Go Live" serves <code>01-glimpse.html</code> and reloads on
          save.</li>
        <li><code>php -S localhost:8000 -t public</code> (from the root)
          serves the lessons AND the app at
          <code>http://localhost:8000/app/index.php</code>.</li>
        <li>After first app load, <code>data/guestbook.sqlite</code> exists
          on disk.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner setting up VS Code to edit a small PHP project
(Lesson 12 of a course).

Please explain:
1. Why "Open Folder" is better than opening single files.
2. What the Live Server extension does and when it is NOT enough
   (PHP!).
3. What each part of "php -S localhost:8000 -t public" means, and
   why I must run it from the project root.
4. What Intelephense gives me that plain VS Code does not.

Then give me a checklist to verify my local setup works.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="12-vscode-editing">
      Mark as done
    </label>

    <div class="pager">
      <a href="11-cpanel-deploy.html">&larr; Lesson 11</a>
      <a href="13-ai-test-and-review.html">Next: Lesson 13 — AI test &amp; review &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 12 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/public/lessons/13-ai-test-and-review.html` (8327 bytes)

```html
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lesson 13 · Use AI to test and review your site</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>

  <header class="site-header">
    <div class="container">
      <a class="brand" href="../index.php">📖 The Guestbook Course</a>
      <button class="menu-button" type="button" data-menu-button
              aria-expanded="false" aria-controls="site-nav">
        <span class="menu-icon" aria-hidden="true"></span>
        <span>Lessons</span>
      </button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Lessons">
        <ul>
          <li><a href="../index.php">Home</a></li>
          <li><a href="01-glimpse.html">01 · Glimpse</a></li>
          <li><a href="02-html-css.html">02 · HTML + CSS</a></li>
          <li><a href="03-html-css-js.html">03 · + JavaScript</a></li>
          <li><a href="04-html-css-js-php.php">04 · + PHP</a></li>
          <li><a href="05-html-css-js-php-sqlite.php">05 · + SQLite</a></li>
          <li><a href="06-final-htmx.php">06 · Final + HTMX</a></li>
          <li><a href="07-github-upload.html">07 · GitHub</a></li>
          <li><a href="08-github-pages.html">08 · GitHub Pages</a></li>
          <li><a href="09-vercel.html">09 · Vercel</a></li>
          <li><a href="10-vercel-free-database.html">10 · Free database</a></li>
          <li><a href="11-cpanel-deploy.html">11 · cPanel</a></li>
          <li><a href="12-vscode-editing.html">12 · VS Code</a></li>
          <li><a href="13-ai-test-and-review.html" aria-current="page">13 · AI review</a></li>
          <li><a href="../app/index.php">Guestbook app</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container" id="main">
    <h1>Lesson 13 — use AI to test and review your site</h1>

    <section aria-labelledby="what-youll-learn">
      <h2 id="what-youll-learn">What you'll learn</h2>
      <ul>
        <li>How to ask an AI assistant to review code for security problems.</li>
        <li>How to get an accessibility and responsive-design review.</li>
        <li>How to read AI answers critically and verify fixes yourself.</li>
      </ul>
    </section>

    <section aria-labelledby="glimpse">
      <h2 id="glimpse">Glimpse</h2>
      <p>
        The workflow is a loop. You paste code + a specific question, the AI
        suggests fixes, and <strong>you verify each fix in the browser</strong>
        before believing it. Three ready-to-paste prompts are below.
      </p>
    </section>

    <section aria-labelledby="code-to-add">
      <h2 id="code-to-add">Three ready-to-paste prompts</h2>

      <h3>Prompt A — security and SQL injection review</h3>
      <pre><code>Act as a strict senior PHP reviewer. This is my guestbook handler
file. Review it ONLY for security issues:
1. SQL injection — is every query a prepared statement? Any SQL
   built from user input?
2. XSS — is every echoed value passed through htmlspecialchars()?
3. Input validation — are lengths and required fields checked on
   the SERVER, not just in HTML?
4. Anything else unsafe you notice.

For each issue: quote the line, explain the danger in one sentence,
and show the corrected code. If something is already correct, say
so explicitly.

[PASTE THE FULL FILE HERE]</code></pre>

      <h3>Prompt B — accessibility and keyboard navigation</h3>
      <pre><code>Act as an accessibility auditor. Review this HTML page for:
1. Keyboard navigation — can every interactive element be reached
   and operated with Tab / Enter / Space only? Is focus visible?
2. Screen reader basics — one h1 per page, logical heading order,
   every form field has a label linked with for/id, error messages
   linked with aria-describedby.
3. Contrast — list any color pairs that may fail WCAG AA.
4. Touch targets — anything smaller than 44x44 px.

Report as a numbered list: issue, why it matters, the exact fix.
If something is already accessible, say so explicitly.

[PASTE THE FULL HTML HERE]</code></pre>

      <h3>Prompt C — responsive layout check</h3>
      <pre><code>Act as a responsive-design tester. Here is my HTML and CSS.
Imagine testing at 320px, 768px and 1280px wide and list, for each
width:
1. What breaks or overflows (horizontal scrollbars, squeezed
   text, buttons too small to tap, tables wider than the screen).
2. Whether touch targets stay at least 44x44 px.
3. Whether images/embeds stay within max-width: 100%.
4. What you would change in the CSS, with the exact code.

Be specific: name selectors and values, do not give generic
advice.

[PASTE HTML HERE]
[PASTE CSS HERE]</code></pre>
    </section>

    <section aria-labelledby="reading-critically">
      <h2 id="reading-critically">Reading the answer critically</h2>
      <p>
        AI suggestions are drafts, not truth. Treat every answer like a
        pull request from an intern you must verify:
      </p>
      <ol>
        <li><strong>Check claimed problems exist.</strong> If the AI says
          "this line has SQL injection", test it: try to inject
          <code>' OR 1=1 --</code> in your app and see what actually happens.
          Often the code is already safe and the AI is guessing.</li>
        <li><strong>Apply one fix at a time.</strong> Change one thing,
          reload, verify, then continue. Big pasted rewrites hide regressions
          and teach you nothing.</li>
        <li><strong>Keep behaviour tests.</strong> After each fix, repeat the
          "Check it works" steps from earlier lessons. A "fix" that breaks
          create/read/update/delete is worse than the bug.</li>
        <li><strong>Ask for the why.</strong> If you do not understand a
          suggested change, ask the AI to explain it in beginner terms. Copy
          that explanation into your notes.</li>
        <li><strong>Beware of invented APIs.</strong> If the AI suggests a
          function you never saw in this course, check the PHP manual
          (php.net) before using it — AIs sometimes invent plausible-sounding
          functions that do not exist.</li>
      </ol>
    </section>

    <section aria-labelledby="check-it-works">
      <h2 id="check-it-works">Check it works</h2>
      <ol>
        <li>Prompt A against <code>public/app/handlers.php</code>: the AI
          confirms every query uses prepared statements and every echo is
          escaped (or it names a real line to fix).</li>
        <li>Prompt B against <code>public/app/index.php</code>: labels,
          headings and focus styles are confirmed or fixed.</li>
        <li>Prompt C against the stylesheet: for each claimed break, open the
          real browser at that width (dev tools &rarr; device toolbar) and
          see it with your own eyes before changing code.</li>
        <li>Re-run lesson 6's "Check it works" list after each fix: the CRUD
          flow must keep working end to end.</li>
      </ol>
    </section>

    <section aria-labelledby="ask-your-ai">
      <h2 id="ask-your-ai">Ask your AI</h2>
      <pre><code>I am a beginner who just finished a course project. I want to use
you to test and review it (Lesson 13).

Please:
1. Suggest THREE more review prompts, in the same style as
   security / accessibility / responsive, that would catch other
   common beginner mistakes (for example: error messages leaking
   file paths, missing input trimming, broken links).
2. For each prompt, tell me exactly which file of my project I
   should paste.

My project files: public/app/index.php, public/app/handlers.php,
public/assets/css/style.css, public/assets/js/main.js.</code></pre>
    </section>

    <label class="mark-done">
      <input type="checkbox" data-lesson-done="13-ai-test-and-review">
      Mark as done
    </label>

    <div class="pager">
      <a href="12-vscode-editing.html">&larr; Lesson 12</a>
      <a href="../app/index.php">Finish: open the guestbook app &rarr;</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container">
      <p>The Guestbook Course · Lesson 13 of 13</p>
    </div>
  </footer>

  <script src="../assets/js/main.js"></script>
</body>
</html>
```

### `guestbook-site/src/config.php` (5348 bytes)

```php
<?php
/**
 * config.php — the shared "engine" of the site.
 *
 * Every PHP page includes this file first. It does four jobs:
 *   1. Connect to the SQLite database (or create it on first run).
 *   2. Make sure the guestbook table exists.
 *   3. Provide small helper functions used by many pages.
 *   4. Turn PHP errors on while you develop (you can turn them off later).
 *
 * WHY IS THIS FILE NOT IN public/?
 *   Files inside public/ can be opened by any visitor in a browser.
 *   This file contains settings, so it lives one folder above public/
 *   where the web server cannot send it to visitors.
 */

// Show errors while you learn. On real hosting you may want to change
// "1" to "0" after everything works (see lesson 11 for details).
ini_set('display_errors', '1');
error_reporting(E_ALL);

/**
 * Returns the full path of the SQLite database file.
 *
 * __DIR__ is the folder that contains THIS file (src/).
 * So __DIR__ . '/../data/guestbook.sqlite' points to:
 *   the src folder, go UP one level, then into data/guestbook.sqlite
 *
 * The .sqlite file sits OUTSIDE public/ on purpose: the web server can
 * still write to it (PHP runs as the same user), but visitors cannot
 * download it by typing its URL into a browser.
 */
function guestbook_database_path(): string
{
    return __DIR__ . '/../data/guestbook.sqlite';
}

/**
 * Creates (if needed) and returns a PDO database connection.
 *
 * PDO = "PHP Data Objects". It is PHP's built-in, safe way to talk to
 * databases. It protects us from SQL injection (see lesson 05).
 *
 * We store the connection in a static variable so that a page that
 * calls guestbook_db() several times only opens the database once.
 */
function guestbook_db(): PDO
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $path = guestbook_database_path();

    // Make sure the data folder exists. On cPanel it always will,
    // but this keeps the project working anywhere.
    $folder = dirname($path);
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    // "sqlite:" plus a file path tells PDO to use an SQLite file.
    // If the file does not exist yet, SQLite creates it automatically.
    $db = new PDO('sqlite:' . $path);

    // Throw a clear exception when something goes wrong,
    // instead of failing silently.
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return true associative arrays (column name => value).
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $db;
}

/**
 * Creates the guestbook table if it is not there yet.
 * Safe to call on every page load: "CREATE TABLE IF NOT EXISTS"
 * does nothing when the table already exists.
 */
function guestbook_create_table(): void
{
    $db = guestbook_db();

    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            name     TEXT    NOT NULL,
            message  TEXT    NOT NULL,
            created_at TEXT  NOT NULL DEFAULT (datetime('now'))
        )
    ");
}

/**
 * Escapes text so it is safe to print inside HTML.
 *
 * htmlspecialchars() turns the special HTML characters < > & " '
 * into harmless codes like &lt; and &gt;. This stops visitors from
 * injecting code into your page (an attack called XSS).
 *
 * EVERY piece of data from the database or a form must go through
 * this before it is echoed.
 */
function guestbook_escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Trims (removes spaces from the ends of) a submitted value and
 * removes any invisible UTF-8 junk. Returns "" when the field is missing.
 */
function guestbook_field(array $source, string $key): string
{
    $value = isset($source[$key]) && is_string($source[$key]) ? $source[$key] : '';
    return trim($value);
}

/**
 * Validates guestbook input.
 *
 * Returns a list of error messages. An empty list means the input is good.
 *   - name    : required, at most 60 characters
 *   - message : required, at most 500 characters
 */
function guestbook_validate(string $name, string $message): array
{
    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Please enter your name.';
    } elseif (mb_strlen($name) > 60) {
        $errors['name'] = 'Your name must be 60 characters or fewer.';
    }

    if ($message === '') {
        $errors['message'] = 'Please write a short message.';
    } elseif (mb_strlen($message) > 500) {
        $errors['message'] = 'Your message must be 500 characters or fewer.';
    }

    return $errors;
}

/**
 * ctype is a bundled PHP extension but a few hosts switch it off too.
 * These tiny replacements keep the id checks working everywhere.
 */
if (!function_exists('ctype_digit')) {
    function ctype_digit(mixed $value): bool
    {
        return is_string($value) && $value !== '' && preg_match('/^[0-9]+$/', $value) === 1;
    }
}

/**
 * A tiny helper so the validation rules above keep working even on the
 * rare hosting plan where the mbstring extension is switched off:
 * if mb_strlen() is missing we fall back to the always-available
 * strlen() (good enough for counting typed characters).
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $value): int
    {
        return strlen($value);
    }
}
```

## 3. Review report

### Verification performed

| Check | Method | Result |
|---|---|---|
| PHP syntax | `php -l` on all 6 PHP files | PASS (no errors) |
| CRUD end-to-end | `php -S` server + curl: create/list/update/delete | PASS (201/200 on all ops) |
| Empty state | Delete all rows, reload app | PASS (friendly empty message) |
| Lesson pages | All 13 render 200, correct title/prev/next nav | PASS |
| Internal links | Crawl all href/src; verify targets exist | PASS (no broken links) |
| HTMX targets | Every `hx-target` `#id` exists in the same page | PASS |
| XSS escaping | Payload `<script>` in name/message rendered escaped | PASS |
| .htaccess / .gitignore | `data/.htaccess` denies all; `.gitignore` excludes `*.sqlite` | PASS |

### Architecture notes

- `src/config.php` lives **outside** `public/` so the web server never serves your settings or DB path.
- SQLite database lives in `data/` (outside `public/`): writable by PHP, unreachable by URL.
- `public/app/handlers.php` is a single endpoints file (create/update/delete/toggle) returning
  HTMX-friendly partials; `public/app/index.php` renders the list + form.
- All user output goes through `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`.
- Prepared statements everywhere — no string-interpolated SQL.
- Lesson progress is stored client-side in `localStorage` (key `guestbook-lesson-progress`), no cookies needed.
- No Composer, no build step, no npm — plain PHP + vanilla JS + one CSS file.

### Fixed during verification

1. Stray `return $errors;` inside a validation `if` block caused a 500 on create — removed.
2. `mb_strlen()` used before checking the extension exists — added a byte-length fallback.
3. `ctype_digit()` (ext-ctype, sometimes absent) replaced with `preg_match('/^[0-9]+$/', ...)`.

## 4. Run guide (local)

Requirements: PHP 8.x with PDO SQLite (bundled by default).

```bash
cd guestbook-site
php -S localhost:8000 -t public
```

Open http://localhost:8000 — the lesson index appears.
Open http://localhost:8000/app — the working guestbook CRUD.

The database is created automatically on first visit (`data/guestbook.sqlite`).

## 5. Deploy guide

### Option A — cPanel shared hosting (lesson 11)

1. Upload everything inside `public/` into `public_html/`.
2. Upload `src/` and `data/` **next to** `public_html/` (one level up), or keep them
   inside `public_html` — the `.htaccess` in `data/` already blocks direct access.
3. Make `data/` writable by the server: `chmod 775 data` in File Manager.
4. Visit the site once; PHP creates the database file.
5. If errors show on screen, set `display_errors` to `0` in `src/config.php`.

### Option B — GitHub + Pages (lessons 07–08)

Pages is static-only: it serves lessons 01–03 and 07–13 as-is, but **PHP pages
will not run**. Use it for the static lessons, then Option A/C for the dynamic app.

### Option C — Vercel (lessons 09–10)

Deploy the static lessons as-is; for the PHP app follow lesson 10's SQLite notes
(read-only filesystems need an external SQLite URL or a different DB).
