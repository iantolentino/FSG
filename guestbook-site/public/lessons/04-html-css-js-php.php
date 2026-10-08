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
