# North Studio: the full-stack course project

A separate landing page and a PHP/SQLite project-request manager. The course website is not the application being built.

## Run

Requires a supported PHP 8.x release (8.3+) with PDO SQLite. From this folder:

```sh
php tools/setup.php
php -S localhost:8000 -t public
```

Choose a password with at least 12 characters. `server/config.local.php` stores its hash and is ignored by Git. Visit http://localhost:8000/ to create a request. Visit http://localhost:8000/requests.html and sign in to read, update and delete requests.

## Layout

- `public/`: HTML, CSS, JavaScript and the PHP API entry point.
- `server/`: database connection, validation and private configuration.
- `data/`: SQLite database, created on first request and excluded from Git.
- `tools/setup.php`: creates your admin configuration. Run locally before cPanel upload.

The form calls the same origin's `api.php`. Static hosting does not execute it. Deploy the full project to a PHP host with `server/` and `data/` next to the domain's document root; copy only public's contents into that document root. SQLite storage must be writable by PHP, and PDO SQLite must be enabled by the host. Keep an existing database when updating the public files.

This is a learning application. Use fictional requests during exercises. Before collecting real inquiries, add appropriate privacy handling and deployment-level abuse protection. The session-based delay is an exercise guard, not comprehensive rate limiting.
