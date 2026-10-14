# Program validation — 9 October 2026

The course contains thirteen lessons in dependency order. The learning project is North Studio, not the course website.

## Course and frontend checks

- Browser checks covered the overview and thirteen canonical lessons at 1440, 768, 390, and 320 pixels.
- Internal links, active navigation, mobile menu opening and Escape closing, and browser script errors were checked.
- Lesson completion persists on reload and updates the overview's thirteen-lesson progress indicator.
- No page had document overflow or emoji in its rendered text. Code scrolls inside its own blocks, and Copy controls do not overlap the code.
- The three standalone landing-page previews work. The animated preview reports that it is a frontend exercise instead of claiming to save data.
- Expanded source references and clipboard copying were checked separately on a phone-sized viewport.
- Previous static HTML lesson URLs redirect to corresponding lessons in the new program.

## PHP/SQLite project checks

The generated full-stack download was extracted into an isolated folder and run with a portable PHP 8.4.26 runtime and PDO SQLite. The system PHP installation was not modified.

- The setup tool creates private configuration with a password hash, not a plaintext password.
- The landing page creates a fictional request; the authenticated dashboard reads, updates, cancels changes, and deletes it.
- Data persists after reloading the browser and restarting the PHP process.
- Invalid fields and missing CSRF tokens are rejected. Management requests are denied after sign-out.
- Submitted markup renders as text in the dashboard.
- Configuration and SQLite database contents cannot be downloaded through the public document root. PHP's development server can serve its ordinary index page for an unknown path; a 200 response by itself does not prove a private file was exposed.
- The dashboard has no horizontal overflow at 320 pixels.

## Downloads and Vercel extension

Five generated archives were checked for integrity and for exclusion of configured credentials, populated SQLite databases, environment secrets, and dependency directories.

The optional Vercel starter passed method, request-origin, input-validation, and missing-configuration checks. A real hosted PostgreSQL connection was not provisioned or tested. Its scope is a server connection and insert exercise; the complete authenticated CRUD application is the PHP/SQLite project.

## Beginner guidance revision — 14 October 2026

Added a minimum setup guide, a glossary, and a reviewer acceptance page. All thirteen lessons now include section links, a targeted troubleshooting note, and an explanation checkpoint. The overview distinguishes the local core milestone from hosting exercises and resumes at the first incomplete lesson.

Checks performed for this revision:

- Course generation completed with 13 lessons, 3 support guides, 3 previews, and 5 downloads.
- `python -m unittest discover -s course/tests -v` passed: generated local links and fragments resolve, page ids are unique, every lesson includes learning support, and archives exclude private configuration, databases, secrets, and dependencies.
- `node --check course/assets/js/main.js` passed.
- A Node VM check exercised initial progress, next incomplete lesson, all-complete review link, unmarking a lesson, and malformed/array storage recovery. Progress ARIA values matched the completed count.
- `git diff --check` passed.

This revision did not run browser viewport/accessibility checks or execute the PHP app: a browser control skill and PHP runtime were unavailable. The earlier checks above describe the earlier revision, not new results. Hosted cPanel and Vercel behavior remains untested in this revision. The revised source has not been published to GitHub Pages.


## Simplified learner flow — 14 October 2026

The overview now starts with three milestones and gives a clear completion point at the local full-stack app. Optional deployment lessons and reference downloads are tucked into disclosures. The sidebar links to major milestones; optional hosting and help pages open only when requested. Lesson section links, practice challenges, and AI review prompts are collapsed until needed. Lessons 1–9 are the progress count, and lesson 9 leads to the project review instead of an optional deployment step.

Checks for this revision: course generation, generated link and fragment integrity, all thirteen lesson support checkpoints, download privacy, three-step overview and optional disclosures, core lesson 9 to lesson 13 pager, JavaScript syntax, and `git diff --check`. Browser viewport and PHP runtime checks were unavailable.

## Course favicon — 14 October 2026

Added a small SVG course icon using the existing teal palette, a friendly smiling browser window, and warm accent dots. The generated site copies it to the root and links it on overview, lessons, and support pages with a content-based cache version.

Checks: site build, favicon route/reference checks, generated links, lesson path and support checks, private download checks, JavaScript syntax, and `git diff --check` passed.


## Simplified course path — 14 October 2026

The overview now gives five plain steps: open VS Code; build with HTML, CSS, JavaScript; upload to GitHub; publish the static website on Vercel; add PHP and SQLite. The detailed lesson links are tucked under one disclosure. The sidebar keeps the lesson list collapsed. Lesson 5 leads to Vercel lesson 11, which leads to PHP lesson 7, then SQLite lesson 8, then the connected form in lesson 9. Optional topics remain available without interrupting the main path.

The overview says explicitly that this course's Vercel exercise publishes the frontend. The PHP + SQLite backend runs locally; the complete PHP application needs a compatible PHP host such as cPanel. Vercel's local function storage is not durable SQLite storage. This avoids implying that the same PHP + file-backed SQLite app can be deployed to Vercel unchanged.

Checks: generated course, internal links and fragments, lesson support checkpoints, exact five-step copy, hosting boundary copy, and main-path previous/next links passed. Node JavaScript syntax and `git diff --check` passed. Browser viewport and PHP execution were unavailable.
