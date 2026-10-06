/* Terazi's script for every page. It talks to nothing but this site. */

/* "12 min ago": pages are built ahead of time, so recalculate in the browser. */
(() => {
  const words = JSON.parse(document.body.dataset.ago || '{}');
  const times = document.querySelectorAll('time[data-ago]');
  if (!times.length || !words.now) return;
  const say = (s, n) => s.replace('%d', n);
  const update = () => {
    const now = Date.now() / 1000;
    for (const t of times) {
      const d = Math.max(0, now - Date.parse(t.dateTime) / 1000);
      t.textContent = d < 90 ? words.now : d < 3600 ? say(words.min, Math.floor(d / 60))
        : d < 86400 ? say(words.hour, Math.floor(d / 3600)) : say(words.day, Math.floor(d / 86400));
    }
  };
  update();
  setInterval(update, 60000);
})();

/* Search boxes go to the search page with the words after "#", which the
   browser never sends to a server. The search page itself runs search.js. */
(() => {
  for (const form of document.querySelectorAll('form.search:not(.search-page)')) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const q = form.querySelector('input').value.trim();
      const url = new URL(form.getAttribute('action'), location.href);
      url.hash = q ? new URLSearchParams({ q }).toString() : '';
      location.assign(url.href);
    });
  }
})();

/* Random story: random.html lists today's stories; open one of them.
   A story reached this way shows the "another random story" button. */
(() => {
  const page = document.getElementById('random-page');
  if (page) {
    const ids = JSON.parse(page.dataset.ids || '[]');
    if (ids.length) {
      const id = ids[Math.floor(Math.random() * ids.length)];
      location.replace(page.dataset.pattern.replace('{id}', id) + '#random');
    }
  }
  if (location.hash === '#random') {
    for (const b of document.querySelectorAll('[data-random-again]')) b.hidden = false;
  }
})();

/* Every 2 minutes (and when you come back to the tab), check whether the
   site has been rebuilt since the page loaded. If so, offer a reload.
   Skipped when the browser asks to save data. */
(() => {
  const loaded = Number(document.body.dataset.updated) || 0;
  const note = document.getElementById('fresh-note');
  if (!note || !loaded || navigator.connection?.saveData) return;
  let busy = false;
  async function check() {
    if (document.hidden || !note.hidden || busy) return;
    busy = true;
    try {
      const r = await fetch('updated.json', { cache: 'no-store' });
      const { updated } = await r.json();
      if (updated > loaded) note.hidden = false;
    } catch (e) { /* offline: try again next time */ }
    busy = false;
  }
  setInterval(check, 120000);
  document.addEventListener('visibilitychange', check);
})();

/* Story page on phones, where the three sides are stacked: each side's
   heading opens and closes its column. They start closed so all three
   headings (with their counts) fit on the screen; the bar of sides above
   them opens the one it jumps to. Wide screens always show every column. */
(() => {
  const cols = [...document.querySelectorAll('.compare .col')];
  if (!cols.length) return;
  const phone = matchMedia('(max-width: 899px)');
  const toggles = new Map();
  for (const col of cols) {
    const h = col.querySelector('.col-h');
    const body = col.querySelector('.col-body');
    if (!h || !body) continue;
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'col-toggle';
    btn.setAttribute('aria-controls', body.id);
    btn.append(...h.childNodes);
    h.append(btn);
    const set = (open) => {
      col.classList.toggle('is-closed', !open);
      btn.setAttribute('aria-expanded', String(open));
    };
    btn.addEventListener('click', () => set(col.classList.contains('is-closed')));
    toggles.set(h.id, set);
  }
  const fit = () => {
    for (const [id, set] of toggles) set(!phone.matches || location.hash === '#' + id);
    for (const h of document.querySelectorAll('.col-toggle')) h.disabled = !phone.matches;
  };
  fit();
  phone.addEventListener('change', fit);
  for (const a of document.querySelectorAll('.side-jump a')) {
    a.addEventListener('click', () => toggles.get(a.hash.slice(1))?.(true));
  }
})();

/* Phones: the menu button opens and closes the links under the name. */
(() => {
  const btn = document.querySelector('.menu-btn');
  const menu = document.getElementById('menu');
  if (!btn || !menu) return;
  btn.setAttribute('role', 'button');
  const set = (open) => {
    menu.classList.toggle('is-open', open);
    btn.setAttribute('aria-expanded', String(open));
  };
  set(false);
  btn.addEventListener('click', (e) => {
    e.preventDefault();
    set(!menu.classList.contains('is-open'));
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && menu.classList.contains('is-open')) {
      set(false);
      btn.focus();
    }
  });
})();
