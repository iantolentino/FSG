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
