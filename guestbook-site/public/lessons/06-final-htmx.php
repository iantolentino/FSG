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

  <header class="site-header"><div class="container">
      <a class="brand" href="../index.php"><span class="brand-mark" aria-hidden="true">G</span><span>Guestbook<span class="brand-subtitle">A course in building for the web</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Lessons</button>
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
    </div></header>

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
