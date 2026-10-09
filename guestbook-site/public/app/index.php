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

  <header class="site-header"><div class="container">
      <a class="brand" href="../index.php"><span class="brand-mark" aria-hidden="true">G</span><span>Guestbook<span class="brand-subtitle">A course in building for the web</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Lessons</button>
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
    </div></header>

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
