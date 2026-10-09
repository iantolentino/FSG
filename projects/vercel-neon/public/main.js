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

const form = document.querySelector('#contact-form');
const status = document.querySelector('#form-status');
form.addEventListener('submit', async (event) => {
  event.preventDefault();
  const button = form.querySelector('button'); button.disabled = true;
  status.textContent = 'Saving the database exercise...';
  try {
    const response = await fetch('/api/request', {method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(Object.fromEntries(new FormData(form)))});
    const result = await response.json();
    if (!response.ok) throw new Error(result.error || 'Request failed.');
    status.textContent = 'Saved request ' + result.id + '. Check the row in the database console.';form.reset();
  } catch(error) { status.textContent = error.message; }
  finally { button.disabled = false; }
});
