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


def site_header(prefix: str, current: str = "index.html") -> str:
    home_current = ' aria-current="page"' if current == "index.html" else ""
    links = [f'<li><a href="{prefix}index.html"{home_current}>Course overview</a></li>']
    for filename, title in LESSON_TITLES:
        number, label = title.split(" · ", 1)
        active = ' aria-current="page"' if filename == current else ""
        links.append(f'<li><a href="{prefix}lessons/{filename}"{active}><span class="nav-number">{number}</span>{label}</a></li>')
    return f'''<header class="site-header"><div class="container">
      <a class="brand" href="{prefix}index.html"><span class="brand-mark" aria-hidden="true">G</span><span>Guestbook<span class="brand-subtitle">A course in building for the web</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Lessons</button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Course"><p class="nav-label">LEARN AT YOUR OWN PACE</p><ul>{''.join(links)}</ul>
      <a class="nav-resource" href="https://github.com/iantolentino/FSG">View project on GitHub</a></nav>
    </div></header>'''


def index_html() -> str:
    links = "\n".join(
        f'<li><a href="lessons/{filename}"><span class="lesson-number">{title.split(" · ")[0]}</span><span><strong>{title.split(" · ")[1]}</strong><span class="lesson-link-label">Read lesson <span data-lesson-status="{filename[:-5]}"></span></span></span><span class="card-arrow" aria-hidden="true">&rarr;</span></a></li>'
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
<body class="course-home">
  <a class="skip-link" href="#main">Skip to main content</a>
  {site_header('')}
  <main class="container" id="main">
    <div class="course-hero">
      <p class="eyebrow">THE GUESTBOOK COURSE</p>
      <h1>Small steps.<br>A real web app.</h1>
      <p class="hero-description">Learn how the web works by building something of your own. Start with HTML, add style and interaction, then put your project online.</p>
      <div class="hero-actions"><a class="button" href="lessons/01-glimpse.html">Start with lesson 1 &rarr;</a><a class="text-link" href="#available-lessons">Explore the course</a></div>
      <p class="hero-meta">Beginner friendly <span aria-hidden="true">/</span> Learn at your own pace <span aria-hidden="true">/</span> Free to follow</p>
    </div>
    <section class="card" aria-labelledby="progress-heading">
      <div class="progress-heading"><h2 id="progress-heading">Your progress</h2><p class="muted" data-progress-caption>0 of 10 lessons done</p></div>
      <div class="progress-bar" data-progress-bar
           data-lessons="01-glimpse 02-html-css 03-html-css-js 07-github-upload 08-github-pages 09-vercel 10-vercel-free-database 11-cpanel-deploy 12-vscode-editing 13-ai-test-and-review">
        <div class="progress-bar__fill"></div>
      </div>
      <p class="progress-note">Mark lessons as done as you go. Your progress stays in this browser.</p>
    </section>
    <section aria-labelledby="available-lessons">
      <p class="eyebrow">YOUR LEARNING PATH</p>
      <h2 id="available-lessons">One lesson at a time.</h2>
      <p class="section-intro">Read, try the examples, and build your confidence. Ten lessons are available here.</p>
      <ol class="lesson-grid">
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


def prepare_lesson(document: str, filename: str) -> str:
    document = re.sub(r'<header\b.*?</header>', lambda _: site_header('../', filename), document, count=1, flags=re.S)
    number = filename[:2]
    document = document.replace('<h1>', f'<p class="eyebrow">THE GUESTBOOK COURSE / LESSON {number}</p><h1>', 1)
    return document


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
        document = prepare_lesson(document, filename)
        (lesson_output / filename).write_text(document, encoding="utf-8")

    (output / "index.html").write_text(index_html(), encoding="utf-8")
    (output / ".nojekyll").write_text("", encoding="utf-8")


if __name__ == "__main__":
    main()
