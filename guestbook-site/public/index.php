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

  <header class="site-header"><div class="container">
      <a class="brand" href="index.php"><span class="brand-mark" aria-hidden="true">G</span><span>Guestbook<span class="brand-subtitle">A course in building for the web</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Lessons</button>
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
          <li><a href="app/index.php"> Guestbook app</a></li>
        </ul>
      </nav>
    </div></header>

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
