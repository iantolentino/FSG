# Basics of Full Stack

A practical beginner program built around **North Studio**, an independent landing-page and project-request application. The application is separate from the course website.

Live course: **https://iantolentino.github.io/FSG/**

## The simple learning path

Follow the five cards on the [course home page](https://iantolentino.github.io/FSG/):

1. Open a project folder in VS Code.
2. Build a page with HTML, style it with CSS, and add simple JavaScript.
3. Upload the files to GitHub.
4. Connect GitHub to Vercel and publish the website.
5. Add a PHP backend and SQLite database, then connect the form.

You can complete the full PHP + SQLite app on your computer. Vercel is used in this beginner path to publish the HTML/CSS/JavaScript website. A local SQLite file is not durable storage for a Vercel serverless app. To put the complete PHP + SQLite app online, use a PHP host such as cPanel; that deployment is an optional lesson. See Vercel’s [SQLite hosting explanation](https://vercel.com/kb/guide/is-sqlite-supported-in-vercel).

VS Code edits the files; the browser displays the website; PHP processes the form on the server; SQLite saves the requests. The course introduces GitHub and Vercel before the database so you can publish the simple website first.

## Project references

- `projects/landing/01-html/`: plain HTML page.
- `projects/landing/02-css/`: responsive CSS version.
- `projects/landing/03-javascript/`: JavaScript and frontend form feedback.
- `projects/full-stack/`: complete PHP/SQLite request app with admin sign-in and Create, Read, Update, and Delete.
- `projects/vercel-neon/`: optional Vercel server-function exercise using hosted PostgreSQL. It is separate from the PHP + SQLite app.

Run the PHP + SQLite app locally with PHP 8.3+ and PDO SQLite:

```sh
cd projects/full-stack
php tools/setup.php
php -S localhost:8000 -t public
```

Choose a local admin password during setup. Visit `http://localhost:8000/` to submit a fictional request, then `http://localhost:8000/requests.html` to manage it. Keep the private configuration and database out of GitHub.

## Course authoring and publishing

`course/lessons.json` contains the thirteen short lessons behind the five-step overview. The main path covers VS Code, HTML, CSS, JavaScript, GitHub, Vercel for the static website, and PHP + SQLite locally. Optional lessons cover GitHub Pages, PHP hosting, and a separate hosted database. `course/assets/` supplies the shared UI. `course/build.py` renders the program, basic setup guide, glossary, reviewer checklist, standalone frontend previews, source references, project downloads, and redirects for the previous HTML lesson URLs.

```sh
python .github/scripts/build-pages.py .pages-build
```

`.github/workflows/pages.yml` builds and deploys on pushes to main. The repository's Pages source is GitHub Actions. All thirteen lesson pages are static and work on Pages. The PHP exercises run locally or on a compatible PHP host; they are distributed as source downloads, not served as executable backend files on Pages.

The older `guestbook-site/` and `REPORT.md` are legacy references and are not published by the current course build.
