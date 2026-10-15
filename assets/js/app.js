/* Client-side behaviour: mobile menu, delete confirmation, form validation, password meter, async email check. */
document.addEventListener('DOMContentLoaded', () => {
  // Mobile sidebar toggle
  const toggle = document.getElementById('navToggle');
  const sidebar = document.getElementById('sidebar');
  if (toggle && sidebar) toggle.addEventListener('click', () => sidebar.classList.toggle('hidden'));

  // Confirm before destructive actions
  document.querySelectorAll('form[data-confirm]').forEach(f =>
    f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));

  // Inline validation (HTML5 constraint API + custom rules)
  const showError = (el, msg) => {
    let p = el.parentElement.querySelector('.js-err');
    if (!msg) { if (p) p.remove(); el.classList.remove('border-red-500'); return; }
    if (!p) { p = document.createElement('p'); p.className = 'js-err text-red-600 text-xs mt-1'; el.parentElement.appendChild(p); }
    p.textContent = msg; el.classList.add('border-red-500');
  };

  document.querySelectorAll('form[data-validate]').forEach(form => {
    form.addEventListener('submit', e => {
      let ok = true;
      form.querySelectorAll('input, select, textarea').forEach(el => {
        if (el.type === 'hidden') return;
        if (!el.checkValidity()) { showError(el, el.validationMessage); ok = false; }
        else showError(el, '');
      });
      const pw = form.querySelector('#password'), cf = form.querySelector('#confirm');
      if (pw && cf && pw.value !== cf.value) { showError(cf, 'Passwords do not match.'); ok = false; }
      if (pw && pw.value && (pw.value.length < 8 || !/[A-Za-z]/.test(pw.value) || !/\d/.test(pw.value))) {
        showError(pw, 'Min 8 characters with letters and numbers.'); ok = false;
      }
      if (!ok) { e.preventDefault(); form.querySelector('.js-err')?.scrollIntoView({ block: 'center' }); }
    });
    form.querySelectorAll('input, select').forEach(el => el.addEventListener('input', () => showError(el, '')));
  });

  // Password strength meter
  const pw = document.getElementById('password'), bar = document.getElementById('strengthBar');
  if (pw && bar) pw.addEventListener('input', () => {
    const v = pw.value;
    let s = 0;
    if (v.length >= 8) s++; if (/[A-Z]/.test(v)) s++; if (/\d/.test(v)) s++; if (/[^A-Za-z0-9]/.test(v)) s++;
    bar.style.width = (s * 25) + '%';
    bar.className = 'h-1.5 rounded transition-all ' + ['bg-red-500', 'bg-red-500', 'bg-amber-500', 'bg-lime-500', 'bg-emerald-500'][s];
  });

  // Async (fetch) email availability check on registration
  const em = document.getElementById('regEmail'), hint = document.getElementById('emailHint');
  if (em && hint) {
    let t;
    em.addEventListener('input', () => {
      clearTimeout(t);
      if (!em.checkValidity() || !em.value) { hint.textContent = ''; return; }
      t = setTimeout(async () => {
        try {
          const r = await fetch('check_email.php?email=' + encodeURIComponent(em.value));
          const j = await r.json();
          hint.textContent = j.available ? '✓ Email is available' : '✗ Email is already taken or invalid';
          hint.className = 'text-xs mt-1 ' + (j.available ? 'text-emerald-600' : 'text-red-600');
        } catch (_) { hint.textContent = ''; }
      }, 400);
    });
  }
});
