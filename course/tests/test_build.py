"""Check generated course navigation and download boundaries (standard library only)."""
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urlsplit, unquote
import unittest
import zipfile

ROOT = Path(__file__).resolve().parents[2]
OUTPUT = ROOT / '.pages-build'


class Page(HTMLParser):
    def __init__(self, source):
        super().__init__()
        self.ids = []
        self.links = []
        self.feed(source)

    def handle_starttag(self, tag, attrs):
        attrs = dict(attrs)
        if 'id' in attrs:
            self.ids.append(attrs['id'])
        for key in ('href', 'src'):
            if key in attrs:
                self.links.append(attrs[key])


class CourseBuildTests(unittest.TestCase):
    def test_local_links_and_fragments_resolve(self):
        pages = {p: Page(p.read_text()) for p in OUTPUT.rglob('*.html')}
        self.assertGreaterEqual(len(pages), 17)
        for path, page in pages.items():
            self.assertEqual(len(page.ids), len(set(page.ids)), path)
            for link in page.links:
                parsed = urlsplit(link)
                if parsed.scheme or parsed.netloc:
                    continue
                target = (path.parent / unquote(parsed.path)).resolve() if parsed.path else path
                self.assertTrue(target.is_file(), f'{path}: {link}')
                if parsed.fragment and target in pages:
                    self.assertIn(unquote(parsed.fragment), pages[target].ids, f'{path}: {link}')

    def test_every_lesson_has_learning_support(self):
        canonical = [p for p in (OUTPUT / 'lessons').glob('*.html') if 'Mark lesson' in p.read_text()]
        self.assertEqual(len(canonical), 13)
        for path in canonical:
            text = path.read_text()
            for required in ('Jump to a section', 'Stuck? Start here', 'Explain it back', 'Check your work'):
                self.assertIn(required, text, path)
        for name in ('setup', 'glossary', 'review'):
            self.assertTrue((OUTPUT / f'{name}.html').is_file())

    def test_overview_has_one_core_path_and_tucks_optional_work_away(self):
        page = (OUTPUT / 'index.html').read_text()
        self.assertIn('Three steps. One finished app.', page)
        self.assertIn('data-lessons="01-workspace 02-html-landing 03-css-design 04-javascript-animation 05-git-github 06-github-pages 07-php-backend 08-sqlite-crud 09-connect-crud"', page)
        self.assertIn('<details class="optional-path">', page)
        self.assertIn('You can stop there. Hosting and extra services are optional.', page)
        self.assertNotIn('13 lessons <span aria-hidden="true">/', page)
        core_end = (OUTPUT / 'lessons/09-connect-crud.html').read_text()
        self.assertIn('href="13-ai-review.html"', core_end)
        self.assertIn('href="13-ai-review.html"', core_end.split('class="pager"', 1)[1])
        self.assertNotIn('href="10-cpanel.html"', core_end.split('class="pager"', 1)[1])

    def test_favicon_is_linked_and_copied(self):
        icon = OUTPUT / 'favicon.svg'
        self.assertTrue(icon.is_file())
        svg = icon.read_text()
        self.assertIn('<svg', svg)
        for path in (OUTPUT / 'index.html', OUTPUT / 'lessons/01-workspace.html', OUTPUT / 'setup.html'):
            self.assertTrue(any('favicon.svg?' in link for link in Page(path.read_text()).links), path)

    def test_downloads_exclude_private_state(self):
        archives = list((OUTPUT / 'downloads').glob('*.zip'))
        self.assertEqual(len(archives), 5)
        for path in archives:
            with zipfile.ZipFile(path) as archive:
                self.assertIsNone(archive.testzip())
                for name in archive.namelist():
                    file = Path(name)
                    self.assertNotEqual(file.name, 'config.local.php')
                    self.assertNotIn('.sqlite', file.name)
                    self.assertFalse(file.name.startswith('.env') and file.name != '.env.example')
                    self.assertFalse(set(file.parts) & {'node_modules', '.git', '.vercel'})


if __name__ == '__main__':
    unittest.main()
