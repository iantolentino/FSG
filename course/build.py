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
    links = [f'<li><a href="{prefix}index.html"' + (' aria-current="page"' if current == 'index' else '') + '>Course overview</a></li>']
    phase = ''
    for lesson in LESSONS:
        if lesson['phase'] != phase:
            phase = lesson['phase']
            links.append(f'<li class="nav-phase">{esc(phase)}</li>')
        active = ' aria-current="page"' if current == lesson['slug'] else ''
        links.append(f'<li><a href="{prefix}lessons/{lesson["slug"]}.html"{active}><span class="nav-number">{lesson["number"]:02}</span>{esc(lesson["title"])}</a></li>')
    return f'''<header class="site-header"><div class="container">
      <a class="brand" href="{prefix}index.html"><span class="brand-mark" aria-hidden="true">FS</span><span>Basics of Full Stack<span class="brand-subtitle">Build. Publish. Connect.</span></span></a>
      <button class="menu-button" type="button" data-menu-button aria-expanded="false" aria-controls="site-nav"><span class="menu-icon" aria-hidden="true"></span>Lessons</button>
      <nav class="site-nav" id="site-nav" data-site-nav aria-label="Course"><ul>{''.join(links)}</ul><a class="nav-resource" href="https://github.com/iantolentino/FSG">Project files on GitHub</a></nav>
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
    ids = ' '.join(l['slug'] for l in LESSONS)
    phases = list(dict.fromkeys(l['phase'] for l in LESSONS))
    curriculum = ''.join(f'<section aria-labelledby="phase-{n}"><p class="eyebrow">PART {n:02}</p><h2 id="phase-{n}">{esc(phase)}</h2>{lesson_cards([l for l in LESSONS if l["phase"]==phase])}</section>' for n,phase in enumerate(phases,1))
    body=f'''<div class="course-hero"><p class="eyebrow">A PRACTICAL BEGINNER PROGRAM</p><h1>Basics of<br>Full Stack</h1>
    <p class="hero-description">Build a landing page. Publish your first website. Then connect it to a real backend with Create, Read, Update, and Delete.</p>
    <div class="hero-actions"><a class="button" href="lessons/01-workspace.html">Start the program &rarr;</a><a class="text-link" href="#project">See the project</a></div>
    <p class="hero-meta">13 lessons <span aria-hidden="true">/</span> One practical project <span aria-hidden="true">/</span> No frameworks required for the core app</p></div>
    <section class="card" aria-labelledby="progress-heading"><div class="progress-heading"><h2 id="progress-heading">Your progress</h2><p class="muted" data-progress-caption>0 of 13 lessons done</p></div>
    <div class="progress-bar" data-progress-bar data-lessons="{ids}"><div class="progress-bar__fill"></div></div><p class="progress-note">Mark lessons as done as you go. Progress is saved only in this browser.</p></section>
    <section id="project" aria-labelledby="project-heading"><p class="eyebrow">WHAT YOU WILL BUILD</p><h2 id="project-heading">North Studio, from page to application.</h2>
    <p class="section-intro">The learning project is separate from this course website. You will build a small business landing page, add design and motion, then save project inquiries in SQLite and manage them through an authenticated CRUD dashboard.</p>
    <div class="project-stages"><a href="examples/landing/01-html/index.html" target="_blank" rel="noopener">Plain HTML preview &rarr;</a><a href="examples/landing/02-css/index.html" target="_blank" rel="noopener">Designed preview &rarr;</a><a href="examples/landing/03-javascript/index.html" target="_blank" rel="noopener">Animated preview &rarr;</a></div>
    <p class="runtime-note">All 13 lessons are readable here. The PHP/SQLite backend runs locally or on a compatible cPanel host. The later Vercel database exercise uses Node.js and hosted PostgreSQL.</p></section>
    {curriculum}
    <section aria-labelledby="downloads-heading"><h2 id="downloads-heading">Project reference files</h2><p>Build each step yourself, then compare it with the supplied reference. Downloads contain templates and code, never configured credentials or a populated database.</p><div class="hero-actions"><a class="button" href="downloads/north-studio-full-stack.zip">Download the PHP/SQLite project</a><a class="text-link" href="downloads/north-studio-vercel-neon.zip">Vercel database exercise</a></div></section>'''
    return page(TITLE,body,description='A 13-lesson beginner program: build a landing page, publish it, and add a working PHP/SQLite CRUD backend.')

def lesson_page(lesson):
    number=lesson['number']; slug=lesson['slug']
    body=f'<p class="eyebrow">{esc(lesson["phase"])} / LESSON {number:02} OF 13</p><h1>{esc(lesson["title"])}</h1><p class="lesson-intro">{esc(lesson["summary"])}</p>'
    body+=f'<section class="card" aria-labelledby="outcome-heading"><h2 id="outcome-heading">What you will finish</h2><p>{esc(lesson["outcome"])}</p><p class="prerequisite"><strong>Before you start:</strong> {esc(lesson["prerequisite"])}</p></section>'
    for i,section in enumerate(lesson['sections']):
        body+=f'<section aria-labelledby="section-{i}"><h2 id="section-{i}">{esc(section["heading"])}</h2>{section["html"]}</section>'
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
    body+=f'<section aria-labelledby="challenge-heading"><h2 id="challenge-heading">Try it yourself</h2><p>{esc(lesson["challenge"])}</p></section>'
    body+=f'<section aria-labelledby="ai-heading"><h2 id="ai-heading">Ask AI for help</h2><p>Use this prompt with the relevant project files. Read the suggested change and verify it before keeping it.</p><pre><code>{esc(lesson["ai"])}</code></pre></section>'
    if lesson['references']:
        body+='<section aria-labelledby="references-heading"><h2 id="references-heading">Official references</h2><ul>'+''.join(f'<li><a href="{esc(url)}" target="_blank" rel="noopener">{esc(label)}</a></li>' for label,url in lesson['references'])+'</ul></section>'
    body+=f'<label class="mark-done"><input type="checkbox" data-lesson-done="{slug}">Mark lesson {number} as done</label><div class="pager">'
    previous=LESSONS[number-2] if number>1 else None
    following=LESSONS[number] if number<13 else None
    body+=f'<a href="{previous["slug"]+".html" if previous else "../index.html"}">&larr; {esc(previous["title"] if previous else "Course overview")}</a>'
    body+=f'<a href="{following["slug"]+".html" if following else "../index.html"}">{esc(following["title"] if following else "Finish: course overview")} &rarr;</a></div>'
    return page(f'Lesson {number}: {lesson["title"]} | {TITLE}',body,'../',slug,lesson['summary'])

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
    print('Built 13 lessons, 3 project previews, and 5 project downloads.')

if __name__=='__main__': main()
