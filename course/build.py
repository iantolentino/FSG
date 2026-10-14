"""Render the Basics of Full Stack curriculum and safe project downloads."""
from pathlib import Path
import hashlib
import html
import json
import re
import shutil
import sys
import zipfile

ROOT = Path(__file__).resolve().parents[1]
COURSE = ROOT / 'course'
LESSONS = json.loads((COURSE / 'lessons.json').read_text(encoding='utf-8'))
TITLE = 'Basics of Full Stack'
LEGACY = {
    '01-glimpse.html': '02-html-landing',
    '02-html-css.html': '03-css-design',
    '03-html-css-js.html': '04-javascript-animation',
    '04-html-css-js-php.php': '07-php-backend',
    '05-html-css-js-php-sqlite.php': '08-sqlite-crud',
    '06-final-htmx.php': '09-connect-crud',
    '07-github-upload.html': '05-git-github',
    '08-github-pages.html': '06-github-pages',
    '09-vercel.html': '11-vercel',
    '10-vercel-free-database.html': '12-hosted-database',
    '11-cpanel-deploy.html': '10-cpanel',
    '12-vscode-editing.html': '01-workspace',
    '13-ai-test-and-review.html': '13-ai-review',
}

def esc(value):
    return html.escape(str(value), quote=True)

def header(prefix, current='index'):
    active = ' aria-current="page"' if current == 'index' else ''
    links = [f'<li><a href="{prefix}index.html"{active}>Course map</a></li>']
    path = [('01-workspace', 'Start'), ('05-git-github', 'Save your work'), ('07-php-backend', 'Add the backend'), ('09-connect-crud', 'Finish the app')]
    for slug, label in path:
        selected = ' aria-current="page"' if current == slug else ''
        links.append(f'<li><a href="{prefix}lessons/{slug}.html"{selected}>{label}</a></li>')
    optional = ''.join(f'<li><a href="{prefix}lessons/{lesson["slug"]}.html">{lesson["number"]:02} {esc(lesson["title"])}</a></li>' for lesson in LESSONS[9:12])
    current_optional = current in [lesson['slug'] for lesson in LESSONS[9:12]]
    current_capstone = ' aria-current="page"' if current == '13-ai-review' else ''
    help_active = current in ('setup', 'glossary', 'review')
    resources = ''.join(f'<li><a href="{prefix}{slug}.html">{label}</a></li>' for slug,label in [('setup','Basic setup'),('glossary','Glossary'),('review','Reviewer checklist')])
    current_attr = ' aria-current="page"' if help_active else ''
    return f'''<header class="site-header"><div class="container">
      <a class="brand" href="{prefix}index.html"><span class="brand-mark" aria-hidden="true">FS</span><span>Basics of Full Stack<span class="brand-subtitle">Build one working app</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Course menu</button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Course"><ul>{''.join(links)}
        <li><details class="nav-disclosure"{' open' if current_optional else ''}><summary>After the core app <span>Optional</span></summary><ul>{optional}</ul></details></li>
        <li><a href="{prefix}lessons/13-ai-review.html"{current_capstone}>Review your work</a></li>
        <li><details class="nav-disclosure"{' open' if help_active else ''}><summary{current_attr}>Help and reference</summary><ul>{resources}</ul></details></li>
      </ul><a class="nav-resource" href="https://github.com/iantolentino/FSG">Project files on GitHub</a></nav>
    </div></header>'''

def asset_url(prefix, asset):
    version = hashlib.sha256((COURSE / asset).read_bytes()).hexdigest()[:12]
    return f'{prefix}{asset}?v={version}'

def page(title, body, prefix='', current='index', description=''):
    return f'''<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>{esc(title)}</title><meta name="description" content="{esc(description)}">
<link rel="stylesheet" href="{asset_url(prefix, 'assets/css/style.css')}"></head>
<body><a class="skip-link" href="#main">Skip to main content</a>{header(prefix,current)}
<main class="container" id="main">{body}</main>
<footer class="site-footer"><div class="container"><p>{TITLE} / Learn by building North Studio.</p><p>HTML, CSS, JavaScript, PHP, SQLite, and deployment fundamentals.</p></div></footer>
<script src="{asset_url(prefix, 'assets/js/main.js')}"></script></body></html>'''

def lesson_cards(lessons):
    return '<ol class="lesson-grid">' + ''.join(
        f'<li><a href="lessons/{l["slug"]}.html"><span class="lesson-number">{l["number"]:02}</span><span><strong>{esc(l["title"])}</strong><span class="lesson-card-summary">{esc(l["summary"])}</span><span class="lesson-link-label">Read lesson<span data-lesson-status="{l["slug"]}"></span></span></span><span class="card-arrow" aria-hidden="true">&rarr;</span></a></li>'
        for l in lessons) + '</ol>'

def index_page():
    core = LESSONS[:9]
    lesson_list = ''.join(f'<li><a href="lessons/{lesson["slug"]}.html"><span class="nav-number">{lesson["number"]:02}</span>{esc(lesson["title"])}</a></li>' for lesson in core)
    body=f'''<div class="course-hero"><p class="eyebrow">A BEGINNER COURSE YOU CAN TAKE ONE STEP AT A TIME</p><h1>Build one working app.</h1>
    <p class="hero-description">You do not need to understand everything first. Follow three steps: make a web page, save it, then connect it to a database.</p>
    <div class="hero-actions"><a class="button" href="lessons/01-workspace.html">Start step 1 <span aria-hidden="true">&rarr;</span></a><a class="text-link" href="setup.html">Need help setting up?</a></div>
    <p class="hero-meta">A simple project &middot; Free tools &middot; Hosting can wait</p></div>
    <section class="card progress-card" aria-labelledby="progress-heading"><div class="progress-heading"><div><p class="eyebrow">PICK UP WHERE YOU LEFT OFF</p><h2 id="progress-heading">Your progress</h2></div><p class="muted" data-progress-caption>0 of 9 core lessons done</p></div>
    <div class="progress-bar" role="progressbar" aria-label="Completed core lessons" aria-valuemin="0" aria-valuemax="9" aria-valuenow="0" data-progress-bar data-lessons="{' '.join(x['slug'] for x in core)}"><div class="progress-bar__fill"></div></div><p class="progress-note">Saved in this browser only.</p><a class="button button-secondary" href="lessons/01-workspace.html" data-resume>Continue to step 1 &rarr;</a></section>
    <section class="path-section" aria-labelledby="path-title"><p class="eyebrow">THE PATH</p><h2 id="path-title">Three steps. One finished app.</h2>
      <div class="path-cards">
        <article class="path-card"><p class="path-step">STEP 1 <span>Lessons 1–4</span></p><h3>Make your web page</h3><p>Build a small business page and make it work on a phone.</p><p class="path-result"><strong>You finish with:</strong> a page you made yourself.</p><a href="lessons/01-workspace.html">Start step 1 &rarr;</a></article>
        <article class="path-card"><p class="path-step">STEP 2 <span>Lessons 5–6</span></p><h3>Save and share it</h3><p>Keep a history of your work and put the page online.</p><p class="path-result"><strong>You finish with:</strong> a shareable web page.</p><a href="lessons/05-git-github.html">Start step 2 &rarr;</a></article>
        <article class="path-card"><p class="path-step">STEP 3 <span>Lessons 7–9</span></p><h3>Make it save information</h3><p>Add a PHP server and database. Let a visitor send a request; manage it in a private dashboard.</p><p class="path-result"><strong>You finish with:</strong> your first full-stack app.</p><a href="lessons/07-php-backend.html">Start step 3 &rarr;</a></article>
      </div>
      <div class="done-banner"><strong>When step 3 works, you have reached the main goal.</strong><span>You can stop there. Hosting and extra services are optional.</span></div>
    </section>
    <details class="lesson-index"><summary>Show the 9 core lessons</summary><ol class="lesson-index-list">{lesson_list}</ol></details>
    <details class="optional-path"><summary>Optional: publish your app or try another database <span>Lessons 10–12</span></summary><p>The core app is complete before these lessons. Continue if you have a PHP host or want to explore Vercel.</p>{lesson_cards(LESSONS[9:12])}<p><a href="lessons/13-ai-review.html">Finish with the project review &rarr;</a></p></details>
    <section class="help-row"><div><h2>Not sure what a word means?</h2><p>Look it up in the plain-language glossary, or open the setup guide.</p></div><div class="resource-links"><a href="glossary.html">Glossary &rarr;</a><a href="setup.html">Setup guide &rarr;</a><a href="review.html">Reviewer checklist &rarr;</a></div></section>
    <details class="reference-downloads"><summary>Want to compare with finished examples?</summary><p>Try building each step yourself first. Downloads contain reference code, not configured credentials or a populated database.</p><div class="hero-actions"><a class="button button-secondary" href="downloads/north-studio-full-stack.zip">PHP/SQLite reference app</a><a class="text-link" href="downloads/north-studio-vercel-neon.zip">Optional Vercel exercise</a></div></details>'''
    return page(TITLE,body,description='Build one small full-stack app in three clear steps. Hosting is optional.')

def lesson_page(lesson):
    number=lesson['number']; slug=lesson['slug']
    path_label = f'CORE PATH / STEP {number:02} OF 09' if number <= 9 else ('OPTIONAL EXTENSION' if number <= 12 else 'FINISH / REVIEW YOUR APP')
    body=f'<p class="eyebrow">{path_label} &middot; LESSON {number:02}</p><h1>{esc(lesson["title"])}</h1><p class="lesson-intro">{esc(lesson["summary"])}</p>'
    body+=f'<section class="card" aria-labelledby="outcome-heading"><h2 id="outcome-heading">What you will finish</h2><p>{esc(lesson["outcome"])}</p><p class="prerequisite"><strong>Before you start:</strong> {esc(lesson["prerequisite"])}</p></section>'
    toc = ''.join(f'<li><a href="#section-{i}">{esc(section["heading"])}</a></li>' for i,section in enumerate(lesson['sections']))
    body+=f'<details class="lesson-toc"><summary>Jump to a section</summary><nav aria-label="On this page"><ol>{toc}<li><a href="#check-it-works">Check your work</a></li><li><a href="#understanding-heading">Explain it back</a></li></ol></nav></details>'
    for i,section in enumerate(lesson['sections']):
        body+=f'<section aria-labelledby="section-{i}"><h2 id="section-{i}">{esc(section["heading"])}</h2>{section["html"]}</section>'
    support=lesson['support']
    body+=f'<aside class="tip" aria-labelledby="recovery-heading"><h2 id="recovery-heading">Stuck? Start here</h2><p><strong>{esc(support["symptom"])}</strong></p><p>{esc(support["fix"])}</p><a href="../setup.html">Basic setup help</a> &middot; <a href="../glossary.html">Look up a term</a></aside>'
    if lesson['preview']:
        preview='../examples/'+lesson['preview']
        body+=f'<section aria-labelledby="preview-heading"><h2 id="preview-heading">The separate project preview</h2><p><a href="{preview}" target="_blank" rel="noopener">Open the project in a new tab &rarr;</a></p><iframe class="project-preview" src="{preview}" title="North Studio: {esc(lesson["title"])}" loading="lazy"></iframe></section>'
    if lesson['files']:
        body+='<section aria-labelledby="source-heading"><h2 id="source-heading">Reference files</h2><p>Expand a file to read and copy its complete source. These files belong to the North Studio project.</p>'
        for filename in lesson['files']:
            source=(ROOT/filename).read_text(encoding='utf-8')
            body+=f'<details class="source-details"><summary>{esc(filename)}</summary><pre><code>{esc(source)}</code></pre></details>'
        body+='</section>'
    if lesson['download']:
        body+=f'<p class="download-row"><a class="button button-secondary" href="../downloads/{lesson["download"]}">Download the reference project</a></p>'
    body+='<section aria-labelledby="check-it-works"><h2 id="check-it-works">Check your work</h2><ol>'+''.join(f'<li>{esc(check)}</li>' for check in lesson['checks'])+'</ol></section>'
    body+=f'<section class="card" aria-labelledby="understanding-heading"><p class="eyebrow">KEY IDEA / {esc(support["concept"])}</p><h2 id="understanding-heading">Explain it back</h2><p>{esc(support["question"])}</p><p class="muted">Use your own words and point to a file or browser result. If you cannot explain it yet, revisit the example before marking the lesson done.</p></section>'
    body+=f'<details class="extra-practice"><summary>Optional practice challenge</summary><p>{esc(lesson["challenge"])}</p></details>'
    body+=f'<details class="extra-practice"><summary>Need help? Open a prompt to review this lesson</summary><p>Include the relevant project files with this prompt. Check the suggestion before using it.</p><pre><code>{esc(lesson["ai"])}</code></pre></details>'
    if lesson['references']:
        body+='<section aria-labelledby="references-heading"><h2 id="references-heading">Official references</h2><ul>'+''.join(f'<li><a href="{esc(url)}" target="_blank" rel="noopener">{esc(label)}</a></li>' for label,url in lesson['references'])+'</ul></section>'
    body+=f'<label class="mark-done"><input type="checkbox" data-lesson-done="{slug}">Mark lesson {number} as done</label><div class="pager">'
    previous=LESSONS[number-2] if 2 <= number <= 9 else (LESSONS[8] if number == 13 else None)
    following=LESSONS[number] if 1 <= number < 9 else (LESSONS[12] if number == 9 else None)
    body+=f'<a href="{previous["slug"]+".html" if previous else "../index.html"}">&larr; {esc(previous["title"] if previous else "Course overview")}</a>'
    body+=f'<a href="{following["slug"]+".html" if following else "../index.html"}">{esc(following["title"] if following else "You finished the core course — back to the map")} &rarr;</a></div>'
    return page(f'Lesson {number}: {lesson["title"]} | {TITLE}',body,'../',slug,lesson['summary'])

def resource_page(kind):
    if kind == 'setup':
        title = 'Your simplest full-stack setup'
        body = '''<p class="eyebrow">START HERE / BASIC SETUP</p><h1>Your simplest full-stack setup</h1>
<p class="lesson-intro">One computer, one editor, one browser, and PHP with SQLite. No paid hosting is needed to complete the local application.</p>
<section class="card"><h2>What “full stack” means here</h2><p>The frontend is the page a visitor uses. The backend checks requests and controls access. The database stores records so they survive a reload. You will connect all three in one small application.</p><div class="stack-grid"><div><strong>Browser</strong><p>HTML: content<br>CSS: appearance<br>JavaScript: interaction</p></div><div><strong>PHP server</strong><p>Receives HTTP requests, validates data, checks sign-in, and returns JSON.</p></div><div><strong>SQLite database</strong><p>Keeps project requests in a private file on the server.</p></div></div><p>A submission travels from browser → PHP → SQLite; PHP returns a response and the browser shows the result.</p></section>
<section><h2>Install only what you need now</h2><ul><li><strong>Lessons 1–4:</strong> VS Code and a modern browser. Start with a local HTML file.</li><li><strong>Lessons 5–6:</strong> Git and a GitHub account for version history and static publishing.</li><li><strong>Lessons 7–9:</strong> PHP 8.3+ with PDO SQLite. SQLite needs no separate database server for this project.</li></ul><p>cPanel hosting and the separate Vercel/PostgreSQL exercise come later. You can finish and review the local full-stack milestone without provisioning either.</p><p>Use the installation links and extension checks in <a href="lessons/07-php-backend.html">lesson 7</a>. On Windows, open a new terminal after updating PATH so it can find PHP.</p></section>
<section><h2>Run the complete reference first</h2><ol><li><a href="downloads/north-studio-full-stack.zip">Download the PHP/SQLite project</a> and extract the ZIP. Do not run commands from inside the ZIP viewer.</li><li>Open the extracted folder in VS Code. Confirm it contains public/, server/, and tools/.</li><li>Choose <strong>Terminal → New Terminal</strong>. Run the checks below, one line at a time. You need PDO and pdo_sqlite in the module list.</li></ol><pre><code>php --version
php --ini
php -m</code></pre><p>Then run these two commands from the extracted project folder:</p><pre><code>php tools/setup.php
php -S localhost:8000 -t public</code></pre><p>Setup asks for a password of at least 12 characters. The server command stays running: leave that terminal open. This is a local development server.</p><ol start="4"><li>Open <a href="http://localhost:8000/">http://localhost:8000/</a>, submit a fictional request, and wait for its success message.</li><li>Open <a href="http://localhost:8000/requests.html">http://localhost:8000/requests.html</a>, sign in with your setup password, and find that request.</li><li>Edit it, reload, then delete it and reload again. Stop the server with Ctrl+C; start it again to check persistence before deleting your final test record.</li></ol><p>The browser address must start with <code>http://localhost:8000</code>. Double-clicking index.html or using a static Live Server preview will not execute PHP.</p></section>
<section><h2>If setup stops working</h2><dl class="glossary"><dt>“php is not recognized” / “command not found”</dt><dd>Install PHP, add its executable directory to PATH, and open a new terminal. Check php --version again.</dd><dt>“could not find driver”</dt><dd>Check php -m for pdo_sqlite. Find the active php.ini with php --ini, enable the extension, and restart the server.</dd><dt>“Could not open input file: tools/setup.php”</dt><dd>You are in the wrong folder. Open the extracted folder containing tools/ and start the terminal there.</dd><dt>“Configuration exists”</dt><dd>Setup already ran. Start the server and use your existing password. To deliberately reset your local learning password, remove server/config.local.php and rerun setup; keep the database.</dd><dt>Port 8000 is already in use</dt><dd>Stop your earlier development server or use php -S localhost:8001 -t public and open localhost:8001 for both pages.</dd></dl></section>
<section><h2>Learn by rebuilding</h2><p>The first run proves your tools work. Now start at lesson 1, build the frontend yourself, then compare your work with the reference. For each lesson: read, make one change, check the result, and explain why it works.</p><a class="button" href="lessons/01-workspace.html">Start lesson 1 &rarr;</a></section>'''
    elif kind == 'review':
        title = 'Review a beginner full-stack project'
        rows = [
            ('Setup', 'Extract a clean copy; follow its README and choose a local password.', 'App runs without copied secrets or a prepopulated database.'),
            ('Create', 'Submit Ada / ada@example.com / A landing page for my shop.', 'Network create returns 201 and an id; the page reports success.'),
            ('Read', 'Sign in on requests.html and reload.', 'The same fictional request appears from the database.'),
            ('Update', 'Change its message and status, save, and reload.', 'Both changes persist; Cancel leaves stored values unchanged.'),
            ('Persistence', 'Stop and restart PHP, then sign in again.', 'The record and its latest changes are still present.'),
            ('Delete', 'Delete the test request, confirm, and reload.', 'The record stays removed; an empty list has useful feedback.'),
            ('Validation', 'Submit blank or invalid fields; inspect server validation as well as browser checks.', 'Invalid writes are rejected; API field validation returns 422.'),
            ('Access', 'Sign out and repeat a list request in Network; repeat a write with no CSRF token.', 'Management is denied without sign-in (401); missing token is rejected (403).'),
            ('Safe rendering', 'Save fictional project details containing <b>test</b>.', 'Dashboard displays the markup as text, not formatted HTML.'),
            ('Usability', 'Test at 320px and desktop; use Tab and Enter through the form and dashboard.', 'Content is readable; labels, focus, error messages, and controls work.'),
            ('Private files', 'Inspect the server document root and repository contents.', 'Only public/ is served; config.local.php and populated SQLite files are excluded from Git.'),
            ('Understanding', 'Ask the learner to trace one request and explain a small code change.', 'They identify browser code, API validation, SQL, and response handling.')]
        table = ''.join(f'<tr><td>{esc(a)}</td><td>{esc(b)}</td><td>{esc(c)}</td></tr>' for a,b,c in rows)
        body = f'''<p class="eyebrow">FOR LEARNERS AND REVIEWERS</p><h1>Review the work with evidence</h1><p class="lesson-intro">Use the same steps to check your own project or review someone else’s. A polished page is one part of the result; saved data and explainable behavior matter too.</p>
<section class="card"><h2>Minimum completion target</h2><p>A responsive landing page and a locally runnable PHP/SQLite app with create, read, update, and delete. Include setup instructions, fictional test data, and an honest record of results. Static Pages publication is a separate milestone. Mark cPanel and Vercel as untested if you did not deploy them.</p><p>Let the reviewer create their own local password. Share source and instructions, never your configured credentials.</p></section>
<section><h2>Repeatable acceptance checks</h2><p>Use browser Network tools on the local app, not on this course. To check server validation, use a controlled request with invalid values; browser validation alone does not exercise the API.</p><div class="table-wrap"><table><thead><tr><th>Area</th><th>Action</th><th>Expected evidence</th></tr></thead><tbody>{table}</tbody></table></div><p>A 200 status on a guessed private path is not proof of exposure: some servers return the index page for unknown URLs. Inspect the actual response and the document-root configuration.</p></section>
<section><h2>Record a useful review</h2><pre><code>Project / commit:
Reviewer / date:
Environment: OS, PHP version, PDO SQLite available
Check:
Steps and fictional input:
Expected result:
Actual result / sanitized evidence:
Status: pass / needs work / not tested
Relevant file or function:
Next action:</code></pre><p>Use <strong>pass</strong> when you reproduced the result, <strong>needs work</strong> when the observed behavior fails the check, and <strong>not tested</strong> when the environment or evidence is missing. Explain blockers instead of assigning a misleading score.</p></section>
<section><h2>What comes after the basics?</h2><p>Practice automated tests, collaborating through pull requests, backup and restore, deployment monitoring, and handling real user data. This course gives you a small stack you can explain and extend; professional readiness takes more projects and practice.</p><a class="button" href="lessons/13-ai-review.html">Return to the capstone &rarr;</a></section>'''
    else:
        title = 'Plain-language glossary'
        terms = [
            ('Frontend', 'The HTML, CSS, and JavaScript used by the browser to display the page and handle interaction.'),
            ('Backend', 'Code running on the server that validates requests, controls access, and works with the database.'),
            ('Full stack', 'Working across the frontend, backend, and database so a complete feature works end to end.'),
            ('Terminal', 'A text interface for commands. The current folder determines where relative file paths start.'),
            ('Document root', 'The folder a web server exposes to visitors. Use public/ for this project.'),
            ('HTTP request and response', 'A message sent to a server and its answer. The method describes the operation; the status code describes the result.'),
            ('API', 'A defined interface for code to exchange data. This project uses api.php actions and JSON responses.'),
            ('JSON', 'A text format for structured data such as objects and lists. It is not a database.'),
            ('DOM', 'The browser’s representation of the HTML page. Changing the DOM does not automatically save a database record.'),
            ('CRUD', 'Create, Read, Update, Delete: four basic operations on stored records.'),
            ('Database and SQL', 'A database stores organized data. SQL is the language used here to read and change SQLite rows.'),
            ('Prepared statement', 'An SQL statement with separately bound values so user input is treated as data rather than query syntax.'),
            ('Authentication and authorization', 'Authentication checks who you are; authorization checks which actions you may take.'),
            ('Session', 'Server-side state associated with a browser cookie. The app uses it to remember admin sign-in.'),
            ('CSRF token', 'A value checked on state-changing requests to help prevent another site from causing unwanted actions through your session.'),
            ('Origin', 'The combination of scheme, hostname, and port. localhost:8000 and localhost:8001 are different origins.'),
            ('Environment variable', 'A configuration value supplied to server code, such as DATABASE_URL. Secrets must stay out of browser code.'),
            ('Commit and push', 'A commit records a local version in Git. A push sends commits to a remote repository such as GitHub.'),
            ('Static hosting', 'Hosting that serves files without running this project’s PHP backend. GitHub Pages is used for the frontend milestone.'),
            ('Deployment', 'Putting your files and configuration onto a hosting environment and verifying they work there.')]
        definitions = ''.join(f'<dt>{esc(term)}</dt><dd>{esc(definition)}</dd>' for term,definition in terms)
        body = f'<p class="eyebrow">REFERENCE / PLAIN LANGUAGE</p><h1>Words you will meet</h1><p class="lesson-intro">Use this page whenever a lesson introduces an unfamiliar term. Each explanation refers to the small application you are building.</p><dl class="glossary">{definitions}</dl><p><a href="setup.html">Review how the parts fit together &rarr;</a></p>'
    return page(f'{title} | {TITLE}',body,current=kind,description=title)


def package(folder, target):
    with zipfile.ZipFile(target,'w',zipfile.ZIP_DEFLATED) as archive:
        for file in sorted(folder.rglob('*')):
            if not file.is_file(): continue
            relative=file.relative_to(folder)
            if any(part in ('node_modules','.git','.vercel','__pycache__') for part in relative.parts): continue
            if file.name == 'config.local.php' or '.sqlite' in file.name: continue
            if file.name.startswith('.env') and file.name != '.env.example': continue
            archive.write(file,relative.as_posix())

def main():
    if len(sys.argv)!=2: raise SystemExit('Usage: build-pages.py .pages-build')
    output=Path(sys.argv[1]).resolve()
    # Only this explicitly generated directory can be cleaned by the builder.
    expected=ROOT/'.pages-build'
    if expected.is_symlink() or output != expected or ROOT not in output.parents:
        raise SystemExit('Output must be the repository .pages-build directory, without symlinks')
    if len(LESSONS)!=13 or [l['number'] for l in LESSONS]!=list(range(1,14)): raise SystemExit('Curriculum must contain 13 lessons in order')
    if output.exists(): shutil.rmtree(output)
    (output/'lessons').mkdir(parents=True)
    (output/'downloads').mkdir()
    shutil.copytree(COURSE/'assets',output/'assets')
    shutil.copytree(ROOT/'projects/landing',output/'examples/landing')
    (output/'index.html').write_text(index_page(),encoding='utf-8')
    for kind in ('setup', 'glossary', 'review'):
        (output/f'{kind}.html').write_text(resource_page(kind),encoding='utf-8')
    for lesson in LESSONS:
        (output/'lessons'/f'{lesson["slug"]}.html').write_text(lesson_page(lesson),encoding='utf-8')
    for filename,slug in LEGACY.items():
        # HTML routes are preserved as redirects; no PHP source is published.
        if not filename.endswith('.html'): continue
        target=slug+'.html'
        redirect=page(f'Lesson moved | {TITLE}',f'<h1>This lesson has moved.</h1><p>The program now follows a complete project from setup to backend deployment.</p><p><a href="{target}">Continue to the updated lesson &rarr;</a></p>','../',slug)
        redirect=redirect.replace('</head>',f'<meta http-equiv="refresh" content="0;url={target}"></head>')
        (output/'lessons'/filename).write_text(redirect,encoding='utf-8')
    for stage in ('01-html','02-css','03-javascript'):
        package(ROOT/'projects/landing'/stage,output/'downloads'/f'north-studio-{stage}.zip')
    package(ROOT/'projects/full-stack',output/'downloads/north-studio-full-stack.zip')
    package(ROOT/'projects/vercel-neon',output/'downloads/north-studio-vercel-neon.zip')
    (output/'.nojekyll').write_text('',encoding='utf-8')
    print('Built 13 lessons, 3 support guides, 3 project previews, and 5 project downloads.')

if __name__=='__main__': main()
