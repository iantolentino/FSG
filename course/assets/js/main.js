document.documentElement.classList.add('js');

/*
 * main.js — the one JavaScript file for the whole site.
 *
 * It does three jobs:
 *   1. Remembers which lessons you marked as done (localStorage).
 *   2. Draws the progress bar on the index page from that list.
 *   3. Opens and closes the mobile navigation menu.
 *
 * It also adds a "Copy" button to every code block, because typing
 * commands by hand is slow and easy to get wrong.
 *
 * Everything is plain ES2015+ JavaScript. No libraries, no build step.
 */

/* ============================================================
   1. Lesson progress (localStorage)
   ------------------------------------------------------------
   localStorage is a tiny database built into every browser.
   It keeps small pieces of text, saved under a key, even after
   the browser is closed. We use the key "basics-full-stack-progress-v1"
   and store one lesson file name ("done") per marked lesson.
   ============================================================ */

const PROGRESS_KEY = 'basics-full-stack-progress-v1';

/**
 * Reads the saved progress object from localStorage.
 * Returns a plain object like { "lessons/01-glimpse.html": true }.
 * If nothing is saved (or the JSON is broken) it returns {}.
 */
function readProgress() {
  try {
    const raw = window.localStorage.getItem(PROGRESS_KEY);
    if (!raw) {
      return {};
    }
    const parsed = JSON.parse(raw);
    // Only keep it if it is really an object, never trust the store blindly.
    return parsed && typeof parsed === 'object' ? parsed : {};
  } catch (err) {
    // Private-browsing modes can block storage. The site still works
    // without progress saving, so we just swallow the error.
    return {};
  }
}

/**
 * Saves the progress object back into localStorage.
 * Any storage error is ignored on purpose (see readProgress above).
 */
function writeProgress(progress) {
  try {
    window.localStorage.setItem(PROGRESS_KEY, JSON.stringify(progress));
  } catch (err) {
    /* storage unavailable: keep going without saving */
  }
}

/**
 * Marks a lesson as done or not done.
 * @param {string} lessonId - the slug used in data attributes, e.g. "01-glimpse"
 * @param {boolean} done
 */
function setLessonDone(lessonId, done) {
  const progress = readProgress();
  if (done) {
    progress[lessonId] = true;
  } else {
    delete progress[lessonId];
  }
  writeProgress(progress);
}

/**
 * Counts how many of the known lessons are marked done.
 * @param {string[]} lessonIds - every lesson the site knows about
 */
function countDone(lessonIds) {
  const progress = readProgress();
  return lessonIds.filter((id) => progress[id]).length;
}

/* ============================================================
   2. Wire up every "Mark as done" checkbox on the page
   ============================================================ */
function initProgressCheckboxes() {
  const checkboxes = document.querySelectorAll('[data-lesson-done]');

  checkboxes.forEach((box) => {
    const lessonId = box.getAttribute('data-lesson-done');

    // Show the saved state when the page loads.
    box.checked = Boolean(readProgress()[lessonId]);

    box.addEventListener('change', () => {
      setLessonDone(lessonId, box.checked);

      // Update the inline status text (if the page has one).
      const status = document.querySelector(`[data-lesson-status="${lessonId}"]`);
      if (status) {
        status.textContent = box.checked ? 'Done' : 'Not done yet';
      }

      // If this page owns a progress bar too (e.g. index page),
      // refresh it immediately.
      renderProgressBar();
    });
  });
}

/* ============================================================
   3. The progress bar on the index page
   ============================================================ */

/**
 * Looks for a progress bar and fills it according to saved progress.
 * The page tells us the lesson list via a data attribute:
 *   <div class="progress-bar" data-lessons="01-glimpse 02-html-css ...">
 */
function renderProgressBar() {
  const bar = document.querySelector('[data-progress-bar]');
  if (!bar) {
    return;
  }

  const lessonIds = (bar.getAttribute('data-lessons') || '')
    .split(/[\s,]+/)
    .filter(Boolean);

  const done = countDone(lessonIds);
  const total = lessonIds.length;
  const percent = total === 0 ? 0 : Math.round((done / total) * 100);

  const fill = bar.querySelector('.progress-bar__fill');
  if (fill) {
    fill.style.width = percent + '%';
  }

  // Update the "3 / 13 lessons done" caption if present.
  const caption = document.querySelector('[data-progress-caption]');
  if (caption) {
    caption.textContent = `${done} of ${total} lessons done`;
  }
}

/* ============================================================
   4. Copy buttons on code blocks
   ============================================================ */

/**
 * Wraps every <pre> in a container and adds a Copy button above it.
 * Clicking copies the code text to the clipboard (no clipboard.js
 * library needed — the browser has navigator.clipboard built in).
 */
function initCopyButtons() {
  document.querySelectorAll('pre').forEach((pre) => {
    // Never wrap the same block twice (.onView on reloaded pages).
    if (pre.parentElement && pre.parentElement.classList.contains('code-block')) {
      return;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'code-block';

    pre.parentNode.insertBefore(wrapper, pre);
    wrapper.appendChild(pre);

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'copy-btn';
    button.textContent = 'Copy';
    button.setAttribute('aria-label', 'Copy code to clipboard');

    button.addEventListener('click', async () => {
      const text = pre.innerText;
      try {
        await navigator.clipboard.writeText(text);
        button.textContent = 'Copied!';
      } catch (err) {
        // Older browsers may reject the clipboard API. Ignore quietly;
        // the learner can still select the code manually.
        button.textContent = 'Press Ctrl+C';
      }
      // Put the label back after a short pause.
      setTimeout(() => {
        button.textContent = 'Copy';
      }, 1500);
    });

    wrapper.appendChild(button);
  });
}

/* ============================================================
   5. The mobile menu button
   ============================================================ */

/**
 * Toggles the header navigation on small screens.
 * The button is a real <button aria-expanded="..."> so screen readers
 * announce whether the menu is open or closed.
 */
function initMenuButton() {
  const button = document.querySelector('[data-menu-button]');
  const nav = document.querySelector('[data-site-nav]');
  if (!button || !nav) {
    return; // page has no menu (nothing to do)
  }

  const setOpen = (open) => {
    nav.classList.toggle('is-open', open);
    button.setAttribute('aria-expanded', open ? 'true' : 'false');
  };

  button.addEventListener('click', () => {
    const isOpen = button.getAttribute('aria-expanded') === 'true';
    setOpen(!isOpen);
  });

  // Close the menu with the Escape key (keyboard accessibility).
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus(); // send keyboard focus back to the button
    }
  });
}

/* ============================================================
   6. Start everything once the page is ready
   ============================================================ */
function initLiveCounts() {
  document.querySelectorAll('[data-live-count]').forEach((form) => {
    const message = form.querySelector('[name="message"]');
    const output = form.querySelector('output');
    if (!message || !output) return;
    const update = () => { output.textContent = String(message.value.length); };
    message.addEventListener('input', update);
    update();
  });
}

function initMain() {
  initProgressCheckboxes();
  renderProgressBar();
  document.querySelectorAll('[data-lesson-status]').forEach((status) => {
    status.textContent = readProgress()[status.dataset.lessonStatus] ? ' / Completed' : '';
  });
  initCopyButtons();
  initMenuButton();
  initLiveCounts();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initMain);
} else {
  initMain();
}
