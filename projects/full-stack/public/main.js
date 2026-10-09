// Content stays visible when JavaScript is unavailable or motion is reduced.
const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (!reducedMotion && 'IntersectionObserver' in window) {
  document.documentElement.classList.add('reveal-active');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  document.querySelectorAll('[data-reveal]').forEach((section) => observer.observe(section));
}

let session;
const form = document.querySelector('#contact-form');
const status = document.querySelector('#form-status');
form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = form.querySelector('button');
  button.disabled = true;
  status.textContent = 'Sending your request...';
  try {
    session = await fetch('api.php?action=session').then((r) => r.json());
    if (!session.csrf) throw new Error(session.error || 'The backend is unavailable.');
    const response = await fetch('api.php?action=create', {
      method: 'POST', headers: {'Content-Type': 'application/json', 'X-CSRF-Token': session.csrf},
      body: JSON.stringify(Object.fromEntries(new FormData(form)))
    });
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Could not send the request.');
    status.textContent = 'Thank you. Your project request has been saved.';
    form.reset();
  } catch (error) { status.textContent = error.message; }
  finally { button.disabled = false; }
});
