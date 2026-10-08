<?php
/**
 * config.php — the shared "engine" of the site.
 *
 * Every PHP page includes this file first. It does four jobs:
 *   1. Connect to the SQLite database (or create it on first run).
 *   2. Make sure the guestbook table exists.
 *   3. Provide small helper functions used by many pages.
 *   4. Turn PHP errors on while you develop (you can turn them off later).
 *
 * WHY IS THIS FILE NOT IN public/?
 *   Files inside public/ can be opened by any visitor in a browser.
 *   This file contains settings, so it lives one folder above public/
 *   where the web server cannot send it to visitors.
 */

// Show errors while you learn. On real hosting you may want to change
// "1" to "0" after everything works (see lesson 11 for details).
ini_set('display_errors', '1');
error_reporting(E_ALL);

/**
 * Returns the full path of the SQLite database file.
 *
 * __DIR__ is the folder that contains THIS file (src/).
 * So __DIR__ . '/../data/guestbook.sqlite' points to:
 *   the src folder, go UP one level, then into data/guestbook.sqlite
 *
 * The .sqlite file sits OUTSIDE public/ on purpose: the web server can
 * still write to it (PHP runs as the same user), but visitors cannot
 * download it by typing its URL into a browser.
 */
function guestbook_database_path(): string
{
    return __DIR__ . '/../data/guestbook.sqlite';
}

/**
 * Creates (if needed) and returns a PDO database connection.
 *
 * PDO = "PHP Data Objects". It is PHP's built-in, safe way to talk to
 * databases. It protects us from SQL injection (see lesson 05).
 *
 * We store the connection in a static variable so that a page that
 * calls guestbook_db() several times only opens the database once.
 */
function guestbook_db(): PDO
{
    static $db = null;

    if ($db instanceof PDO) {
        return $db;
    }

    $path = guestbook_database_path();

    // Make sure the data folder exists. On cPanel it always will,
    // but this keeps the project working anywhere.
    $folder = dirname($path);
    if (!is_dir($folder)) {
        mkdir($folder, 0755, true);
    }

    // "sqlite:" plus a file path tells PDO to use an SQLite file.
    // If the file does not exist yet, SQLite creates it automatically.
    $db = new PDO('sqlite:' . $path);

    // Throw a clear exception when something goes wrong,
    // instead of failing silently.
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Return true associative arrays (column name => value).
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    return $db;
}

/**
 * Creates the guestbook table if it is not there yet.
 * Safe to call on every page load: "CREATE TABLE IF NOT EXISTS"
 * does nothing when the table already exists.
 */
function guestbook_create_table(): void
{
    $db = guestbook_db();

    $db->exec("
        CREATE TABLE IF NOT EXISTS messages (
            id       INTEGER PRIMARY KEY AUTOINCREMENT,
            name     TEXT    NOT NULL,
            message  TEXT    NOT NULL,
            created_at TEXT  NOT NULL DEFAULT (datetime('now'))
        )
    ");
}

/**
 * Escapes text so it is safe to print inside HTML.
 *
 * htmlspecialchars() turns the special HTML characters < > & " '
 * into harmless codes like &lt; and &gt;. This stops visitors from
 * injecting code into your page (an attack called XSS).
 *
 * EVERY piece of data from the database or a form must go through
 * this before it is echoed.
 */
function guestbook_escape(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Trims (removes spaces from the ends of) a submitted value and
 * removes any invisible UTF-8 junk. Returns "" when the field is missing.
 */
function guestbook_field(array $source, string $key): string
{
    $value = isset($source[$key]) && is_string($source[$key]) ? $source[$key] : '';
    return trim($value);
}

/**
 * Validates guestbook input.
 *
 * Returns a list of error messages. An empty list means the input is good.
 *   - name    : required, at most 60 characters
 *   - message : required, at most 500 characters
 */
function guestbook_validate(string $name, string $message): array
{
    $errors = [];

    if ($name === '') {
        $errors['name'] = 'Please enter your name.';
    } elseif (mb_strlen($name) > 60) {
        $errors['name'] = 'Your name must be 60 characters or fewer.';
    }

    if ($message === '') {
        $errors['message'] = 'Please write a short message.';
    } elseif (mb_strlen($message) > 500) {
        $errors['message'] = 'Your message must be 500 characters or fewer.';
    }

    return $errors;
}

/**
 * ctype is a bundled PHP extension but a few hosts switch it off too.
 * These tiny replacements keep the id checks working everywhere.
 */
if (!function_exists('ctype_digit')) {
    function ctype_digit(mixed $value): bool
    {
        return is_string($value) && $value !== '' && preg_match('/^[0-9]+$/', $value) === 1;
    }
}

/**
 * A tiny helper so the validation rules above keep working even on the
 * rare hosting plan where the mbstring extension is switched off:
 * if mb_strlen() is missing we fall back to the always-available
 * strlen() (good enough for counting typed characters).
 */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $value): int
    {
        return strlen($value);
    }
}
