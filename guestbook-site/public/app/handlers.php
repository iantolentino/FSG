<?php
/**
 * handlers.php — the HTMX endpoints of the guestbook app.
 *
 * This file answers small AJAX-style requests made by HTMX
 * (see public/app/index.php). It NEVER returns a whole page:
 * every response is a small HTML fragment plus a proper HTTP
 * status code.
 *
 * Actions (chosen by ?action=...):
 *   create  POST    add a message, answer 201 + the new entry fragment
 *   list    GET     answer 200 + every entry as fragments
 *   count   GET     answer 200 + the "N messages" badge fragment
 *   edit    GET     answer 200 + the inline edit form fragment
 *   update  POST    save changes, answer 200 + the updated entry fragment
 *   delete  DELETE  remove a row, answer 200 + empty body
 *   entry   GET     answer 200 + one entry fragment (used by "Cancel")
 *
 * Status codes: 200 success, 201 created, 404 unknown id/action,
 * 405 wrong HTTP method, 422 validation error, 500 server error.
 * A visitor never sees a raw PHP error.
 */
require_once __DIR__ . '/../../src/config.php';
guestbook_create_table();

header('Content-Type: text/html; charset=UTF-8');

/* ============================================================
   Small shared helpers
   ============================================================ */

/**
 * Sends a fragment with a status code and stops.
 */
function respond(string $html, int $status = 200, array $headers = []): void
{
    foreach ($headers as $header) {
        header($header);
    }
    http_response_code($status);
    echo $html;
    exit;
}

/**
 * 405: this action exists but not for this HTTP method.
 * The "Allow" header tells the client which method is correct.
 */
function method_not_allowed(string ...$allowed): void
{
    respond(
        '<p class="form-error" role="alert">Wrong request method for this action.</p>',
        405,
        ['Allow: ' . implode(', ', $allowed)]
    );
}

/**
 * 404: the action (or the id) does not exist.
 */
function not_found(string $message = 'Not found.'): void
{
    respond('<p class="form-error" role="alert">' . guestbook_escape($message) . '</p>', 404);
}

/**
 * Reads an id from the URL and returns it as an integer,
 * or fails with 404 when it is missing or not a plain number.
 */
function read_id(): int
{
    $raw = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if ($raw === '' || !ctype_digit($raw)) {
        not_found('That message id does not exist.');
    }
    return (int) $raw;
}

/**
 * Finds one row by id. 404 when it is not there.
 */
function fetch_entry(PDO $db, int $id): array
{
    $stmt = $db->prepare('SELECT id, name, message, created_at FROM messages WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    if ($row === false) {
        not_found('That message does not exist (it may have been deleted).');
    }
    return $row;
}

/**
 * Validates input using the shared rules from src/config.php:
 * name required, max 60 chars; message required, max 500 chars.
 * Returns [errors, name, message] with everything trimmed.
 */
function clean_and_validate(array $source): array
{
    $name    = guestbook_field($source, 'name');
    $message = guestbook_field($source, 'message');
    $errors  = guestbook_validate($name, $message);
    return [$errors, $name, $message];
}

/* ============================================================
   Fragment builders (each returns HTML as a string)
   ============================================================ */

/**
 * One guestbook entry, ready to drop into the list.
 * EVERY value is escaped with guestbook_escape() (XSS protection).
 */
function render_entry(array $row): string
{
    $id = (int) $row['id'];
    $html = '<article class="entry" id="entry-' . $id . '">';
    $html .= '<div class="entry-header">';
    $html .= '<p class="entry-name">' . guestbook_escape($row['name']) . '</p>';
    $html .= '<span class="entry-date">' . guestbook_escape($row['created_at']) . '</span>';
    $html .= '</div>';
    $html .= '<p class="entry-message">' . guestbook_escape($row['message']) . '</p>';
    $html .= '<div class="entry-actions">';
    // Edit: GET the inline edit form, swap it in place of this entry.
    $html .= '<button type="button" class="button button-secondary"'
           . ' hx-get="handlers.php?action=edit&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="outerHTML">Edit</button>';
    // Delete: confirms first, then removes this entry from the page.
    $html .= '<button type="button" class="button button-danger"'
           . ' hx-delete="handlers.php?action=delete&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="delete"'
           . ' hx-confirm="Delete this message?">Delete</button>';
    $html .= '</div>';
    $html .= '</article>';
    return $html;
}

/**
 * The inline edit form. Shown in place of an entry (same id).
 * On validation errors the same form comes back WITH the errors
 * and the values the user typed, so nothing is lost.
 */
function render_edit_form(array $row, array $errors = []): string
{
    $id      = (int) $row['id'];
    $name    = guestbook_escape($row['name']);
    $message = guestbook_escape($row['message']);

    $nameError    = isset($errors['name']) ? (string) $errors['name'] : '';
    $messageError = isset($errors['message']) ? (string) $errors['message'] : '';

    // aria-describedby links each error message to its field for
    // screen readers (see the accessibility section of the course).
    $nameDesc    = $nameError    !== '' ? ' aria-describedby="edit-name-error-' . $id . '"'    : '';
    $messageDesc = $messageError !== '' ? ' aria-describedby="edit-message-error-' . $id . '"' : '';

    $html = '<form class="form" id="entry-' . $id . '"'
          . ' hx-post="handlers.php?action=update&amp;id=' . $id . '"'
          . ' hx-target="#entry-' . $id . '"'
          . ' hx-swap="outerHTML" novalidate>';
    $html .= '<div class="field' . ($nameError !== '' ? ' has-error' : '') . '">';
    $html .= '<label for="edit-name-' . $id . '">Name</label>';
    $html .= '<input id="edit-name-' . $id . '" name="name" type="text"'
           . ' maxlength="60" value="' . $name . '"' . $nameDesc . '>';
    if ($nameError !== '') {
        $html .= '<p class="field-error" id="edit-name-error-' . $id . '">' . guestbook_escape($nameError) . '</p>';
    }
    $html .= '</div>';
    $html .= '<div class="field' . ($messageError !== '' ? ' has-error' : '') . '">';
    $html .= '<label for="edit-message-' . $id . '">Message</label>';
    $html .= '<textarea id="edit-message-' . $id . '" name="message" maxlength="500"'
           . $messageDesc . '>' . $message . '</textarea>';
    if ($messageError !== '') {
        $html .= '<p class="field-error" id="edit-message-error-' . $id . '">' . guestbook_escape($messageError) . '</p>';
    }
    $html .= '</div>';
    $html .= '<div class="entry-actions">';
    $html .= '<button type="submit" class="button">Save changes</button>';
    // Cancel: GET the unchanged entry back and swap the form away.
    $html .= '<button type="button" class="button button-secondary"'
           . ' hx-get="handlers.php?action=entry&amp;id=' . $id . '"'
           . ' hx-target="#entry-' . $id . '"'
           . ' hx-swap="outerHTML">Cancel</button>';
    $html .= '</div>';
    $html .= '</form>';
    return $html;
}

/**
 * The live count badge. It listens for the "msgChanged" event that
 * main.js fires after every successful create/update/delete.
 */
function render_count(int $count): string
{
    $text = $count === 1 ? '1 message' : $count . ' messages';
    return '<p class="muted" id="message-count"'
         . ' hx-get="handlers.php?action=count"'
         . ' hx-trigger="msgChanged from:body"'
         . ' hx-swap="outerHTML">'
         . '<span class="count-badge">' . guestbook_escape($text) . '</span>'
         . '</p>';
}

/**
 * The whole list. An empty database shows a friendly empty state.
 */
function render_list(PDO $db): string
{
    $rows = $db->query('SELECT id, name, message, created_at FROM messages ORDER BY id DESC')
               ->fetchAll();

    if (!$rows) {
        return '<p class="empty-state">No messages yet. Be the first to sign the guestbook!</p>';
    }

    $html = '';
    foreach ($rows as $row) {
        $html .= render_entry($row);
    }
    return $html;
}

/* ============================================================
   One function per action
   ============================================================ */

/**
 * POST create: validate, save, answer 201 + the new entry fragment.
 * On validation errors: 422 + the error box fragment.
 */
function action_create(PDO $db, string $method): void
{
    if ($method !== 'POST') {
        method_not_allowed('POST');
    }

    [$errors, $name, $message] = clean_and_validate($_POST);
    if ($errors) {
        $html = '<div class="form-error" role="alert"><ul>';
        foreach ($errors as $error) {
            $html .= '<li>' . guestbook_escape($error) . '</li>';
        }
        $html .= '</ul></div>';
        respond($html, 422);
    }

    $stmt = $db->prepare('INSERT INTO messages (name, message) VALUES (:name, :message)');
    $stmt->execute([':name' => $name, ':message' => $message]);

    $entry = fetch_entry($db, (int) $db->lastInsertId());
    respond(render_entry($entry), 201);
}

/**
 * GET list: all entries, newest first.
 */
function action_list(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    respond(render_list($db));
}

/**
 * GET count: the "N messages" badge.
 */
function action_count(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $count = (int) $db->query('SELECT COUNT(*) FROM messages')->fetchColumn();
    respond(render_count($count));
}

/**
 * GET edit: the inline edit form for one entry.
 */
function action_edit(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $entry = fetch_entry($db, read_id());
    respond(render_edit_form($entry));
}

/**
 * GET entry: one unchanged entry (used by the Cancel button).
 */
function action_entry(PDO $db, string $method): void
{
    if ($method !== 'GET') {
        method_not_allowed('GET');
    }
    $entry = fetch_entry($db, read_id());
    respond(render_entry($entry));
}

/**
 * POST update: validate, save, answer 200 + the updated entry.
 * On validation errors: 422 + the edit form WITH the errors.
 */
function action_update(PDO $db, string $method): void
{
    if ($method !== 'POST') {
        method_not_allowed('POST');
    }
    $id    = read_id();
    $entry = fetch_entry($db, $id);

    [$errors, $name, $message] = clean_and_validate($_POST);
    if ($errors) {
        respond(render_edit_form($entry, $errors), 422);
    }

    $stmt = $db->prepare('UPDATE messages SET name = :name, message = :message WHERE id = :id');
    $stmt->execute([':name' => $name, ':message' => $message, ':id' => $id]);

    respond(render_entry(fetch_entry($db, $id)));
}

/**
 * DELETE delete: remove the row, answer 200 with an empty body
 * (HTMX's hx-swap="delete" then removes the entry from the page).
 */
function action_delete(PDO $db, string $method): void
{
    if ($method !== 'DELETE') {
        method_not_allowed('DELETE');
    }
    $id    = read_id();
    $entry = fetch_entry($db, $id);

    $stmt = $db->prepare('DELETE FROM messages WHERE id = :id');
    $stmt->execute([':id' => (int) $entry['id']]);

    respond('');
}

/* ============================================================
   Route to the right action
   ============================================================ */
try {
    $db     = guestbook_db();
    $action = isset($_GET['action']) ? (string) $_GET['action'] : '';
    $method = $_SERVER['REQUEST_METHOD'];

    $known = ['create', 'list', 'count', 'edit', 'entry', 'update', 'delete'];
    if (!in_array($action, $known, true)) {
        not_found('Unknown action.');
    }

    switch ($action) {
        case 'create': action_create($db, $method); break;
        case 'list':   action_list($db, $method);   break;
        case 'count':  action_count($db, $method);  break;
        case 'edit':   action_edit($db, $method);   break;
        case 'entry':  action_entry($db, $method);  break;
        case 'update': action_update($db, $method); break;
        case 'delete': action_delete($db, $method); break;
    }
} catch (Throwable $error) {
    // A safety net: whatever explodes, the visitor gets a calm,
    // generic message and the real error stays in the server log.
    // (The try/catch also guards the actions above.)
    if (!in_array($action ?? '', ['list', 'count', 'edit', 'entry'], true)) {
        // fall through to the generic answer below
    }
    respond(
        '<p class="form-error" role="alert">Sorry, something went wrong on the server. Please try again.</p>',
        500
    );
}
