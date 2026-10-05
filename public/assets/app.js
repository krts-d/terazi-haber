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
