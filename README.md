# FSG

This repository contains The Guestbook Course in [`guestbook-site/`](guestbook-site/).

## GitHub Pages

The Pages workflow publishes the static lessons at **https://iantolentino.github.io/FSG/**. It includes lessons 1–3 and 7–13. GitHub Pages cannot run PHP or SQLite, so lessons 4–6 and the interactive guestbook app require a PHP server; the Pages home page links to the run guide.

To enable publishing for this repository, open **Settings → Pages** and set **Build and deployment → Source** to **GitHub Actions**. The workflow deploys automatically after each push to `main`; you can also start it from the Actions tab.

## Run the full course locally

Install PHP 8 with PDO SQLite, open a terminal at the repository root, then run:

```sh
cd guestbook-site
php -S localhost:8000 -t public
```

Open <http://localhost:8000/>.
