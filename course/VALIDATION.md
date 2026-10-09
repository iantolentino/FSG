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
