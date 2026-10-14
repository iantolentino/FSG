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
            for required in ('In this lesson', 'Stuck? Start here', 'Explain it back', 'Check your work'):
                self.assertIn(required, text, path)
        for name in ('setup', 'glossary', 'review'):
            self.assertTrue((OUTPUT / f'{name}.html').is_file())

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
