# Basics of Full Stack

A practical beginner program built around **North Studio**, an independent landing-page and project-request application. The application is separate from the course website.

Live course: **https://iantolentino.github.io/FSG/**

## Learning flow

1. Set up VS Code and learn the editing and browser tools.
2. Build a plain HTML landing page.
3. Add responsive CSS design.
4. Add JavaScript and accessible animation.
5. Save and upload the project with Git and GitHub.
6. Publish the static landing page on GitHub Pages.
7. Set up a PHP backend and private configuration.
8. Build persistent SQLite Create, Read, Update, and Delete routes.
9. Connect the landing form and authenticated request dashboard.
10. Upload the HTML/CSS/JavaScript/PHP/SQLite application to cPanel.
11. Learn Vercel deployment, domains, and environments.
12. Connect a hosted PostgreSQL database through a Vercel server function.
13. Use AI for debugging and review, then finish the capstone.

## Project references

- `projects/landing/01-html/`: plain HTML landing page.
- `projects/landing/02-css/`: the same page with responsive design.
- `projects/landing/03-javascript/`: the same page with motion and frontend feedback.
- `projects/full-stack/`: complete PHP/SQLite request application, including admin authentication and all four CRUD operations.
- `projects/vercel-neon/`: optional Node.js/PostgreSQL connection and insert exercise. It is not a complete serverless port of the PHP admin dashboard.

Run the core app with a supported PHP 8.x release (8.3+) and PDO SQLite:

```sh
cd projects/full-stack
php tools/setup.php
php -S localhost:8000 -t public
```

Choose the admin password during setup. Open http://localhost:8000/ to submit a fictional project request, then http://localhost:8000/requests.html to manage it. Private configuration and database files are excluded from Git and from generated downloads.

## Course authoring and publishing

`course/lessons.json` contains the thirteen lessons. `course/assets/` supplies the shared UI. `course/build.py` renders the program, standalone frontend previews, source references, project downloads, and redirects for the previous HTML lesson URLs.

```sh
python .github/scripts/build-pages.py .pages-build
```

`.github/workflows/pages.yml` builds and deploys on pushes to main. The repository's Pages source is GitHub Actions. All thirteen lesson pages are static and work on Pages. The PHP exercises run locally or on a compatible PHP host; they are distributed as source downloads, not served as executable backend files on Pages.

The older `guestbook-site/` and `REPORT.md` are legacy references and are not published by the current course build.
