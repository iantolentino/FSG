#!/usr/bin/env python3
"""Build the static subset of the Guestbook course for GitHub Pages."""

from pathlib import Path
import re
import shutil
import sys


ROOT = Path(__file__).resolve().parents[2]
SOURCE = ROOT / "guestbook-site" / "public"
LESSONS = (
    "01-glimpse.html",
    "02-html-css.html",
    "03-html-css-js.html",
    "07-github-upload.html",
    "08-github-pages.html",
    "09-vercel.html",
    "10-vercel-free-database.html",
    "11-cpanel-deploy.html",
    "12-vscode-editing.html",
    "13-ai-test-and-review.html",
)

LESSON_TITLES = (
    ("01-glimpse.html", "01 · Glimpse"),
    ("02-html-css.html", "02 · HTML + CSS"),
    ("03-html-css-js.html", "03 · HTML + CSS + JavaScript"),
    ("07-github-upload.html", "07 · Upload to GitHub"),
    ("08-github-pages.html", "08 · GitHub Pages"),
    ("09-vercel.html", "09 · Vercel"),
    ("10-vercel-free-database.html", "10 · Free database"),
    ("11-cpanel-deploy.html", "11 · cPanel"),
    ("12-vscode-editing.html", "12 · VS Code"),
    ("13-ai-test-and-review.html", "13 · AI review"),
)


def index_html() -> str:
    links = "\n".join(
        f'        <li><a href="lessons/{filename}">{title}</a></li>'
        for filename, title in LESSON_TITLES
    )
    return f'''<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>The Guestbook Course</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
  <a class="skip-link" href="#main">Skip to main content</a>
  <header class="site-header">
    <div class="container">
      <a class="brand" href="index.html">📖 The Guestbook Course</a>
    </div>
  </header>
  <main class="container" id="main">
    <h1>The Guestbook Course</h1>
    <p>A beginner course that builds a guestbook from HTML through to a PHP and SQLite app.</p>
    <section class="card" aria-labelledby="progress-heading">
      <h2 id="progress-heading">Your progress</h2>
      <div class="progress-bar" data-progress-bar
           data-lessons="01-glimpse 02-html-css 03-html-css-js 07-github-upload 08-github-pages 09-vercel 10-vercel-free-database 11-cpanel-deploy 12-vscode-editing 13-ai-test-and-review">
        <div class="progress-bar__fill"></div>
      </div>
      <p class="muted" data-progress-caption>0 of 10 available lessons done</p>
      <p class="muted">Progress is saved in this browser only.</p>
    </section>
    <section aria-labelledby="available-lessons">
      <h2 id="available-lessons">Lessons available on this site</h2>
      <p>These lessons use static HTML, CSS, and JavaScript, so they work on GitHub Pages.</p>
      <ol>
{links}
      </ol>
    </section>
    <section id="php-lessons" aria-labelledby="php-heading">
      <h2 id="php-heading">PHP lessons and the guestbook app</h2>
      <div class="caution">
        <p>GitHub Pages does not run PHP or SQLite. Lessons 4–6 and the interactive guestbook app need a PHP server.</p>
        <p><a href="https://github.com/iantolentino/FSG/tree/main/guestbook-site">View the project files and run guide on GitHub</a>.</p>
        <p>To run the full course locally, open a terminal at the repository root and run:</p>
        <pre><code>cd guestbook-site
php -S localhost:8000 -t public</code></pre>
        <p>Then open <code>http://localhost:8000/</code>.</p>
      </div>
    </section>
  </main>
  <footer class="site-footer">
    <div class="container"><p>The Guestbook Course</p></div>
  </footer>
  <script src="assets/js/main.js"></script>
</body>
</html>
'''


def filter_static_navigation(document: str) -> str:
    # The source course has a shared nav with links to lessons that require PHP.
    def clean_nav(match: re.Match[str]) -> str:
        nav = match.group(0)

        def keep_or_remove(item: re.Match[str]) -> str:
            hrefs = re.findall(r'href=["\']([^"\']+)["\']', item.group(0), re.I)
            if any(href.lower().endswith(".php") for href in hrefs):
                return ""
            return item.group(0)

        return re.sub(r"<li\b.*?</li>", keep_or_remove, nav, flags=re.I | re.S)

    document = re.sub(r"<nav\b.*?</nav>", clean_nav, document, flags=re.I | re.S)
    return document.replace('href="../index.php"', 'href="../index.html"')


def repair_pager_links(document: str, filename: str) -> str:
    def replace_php_link(match: re.Match[str]) -> str:
        href, attrs, label = match.groups()
        target = href.rsplit("/", 1)[-1].lower()

        if filename == "03-html-css-js.html" and target == "04-html-css-js-php.php":
            return '<a href="07-github-upload.html">Next available static lesson: Lesson 7 — GitHub &rarr;</a>'
        if filename == "07-github-upload.html" and target == "06-final-htmx.php":
            return '<a href="../index.html">&larr; Course home</a>'
        if "app/index.php" in href.lower():
            return '<a href="../index.html#php-lessons">Finish: run the guestbook app locally &rarr;</a>'
        return '<a href="../index.html#php-lessons">This lesson needs a PHP server</a>'

    return re.sub(
        r'<a\s+href="([^"]+\.php)"([^>]*)>(.*?)</a>',
        replace_php_link,
        document,
        flags=re.I | re.S,
    )


def main() -> None:
    if len(sys.argv) != 2:
        raise SystemExit("Usage: build-pages.py OUTPUT_DIRECTORY")

    output = Path(sys.argv[1]).resolve()
    if output == ROOT or ROOT not in output.parents:
        raise SystemExit("Output directory must be inside the repository")

    lesson_output = output / "lessons"
    lesson_output.mkdir(parents=True, exist_ok=True)
    shutil.copytree(SOURCE / "assets", output / "assets", dirs_exist_ok=True)

    for filename in LESSONS:
        source_file = SOURCE / "lessons" / filename
        document = source_file.read_text(encoding="utf-8")
        document = filter_static_navigation(document)
        document = repair_pager_links(document, filename)
        (lesson_output / filename).write_text(document, encoding="utf-8")

    (output / "index.html").write_text(index_html(), encoding="utf-8")
    (output / ".nojekyll").write_text("", encoding="utf-8")


if __name__ == "__main__":
    main()
