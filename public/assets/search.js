/* Terazi's search. It runs entirely in this browser: search-index.json (the
   headlines of the last few days) is downloaded once, and the words typed
   are never sent anywhere. They live in the address after "#". */
(() => {
  const results = document.getElementById('search-results');
  const form = document.querySelector('form.search-page');
  if (!results || !form) return;
  const input = form.querySelector('input');
  const status = document.getElementById('search-status');
  const sides = document.getElementById('search-sides');
  const moreWrap = document.getElementById('search-more-wrap');
  const more = document.getElementById('search-more');
  const S = JSON.parse(results.dataset.strings);
  const PER_PAGE = 40;
  const LABELS = ['gov', 'ind', 'opp'];

  // Same matching as the PHP version (Text::fold): Turkish lower case,
  // accents dropped, so "imamoglu" finds "İmamoğlu" and "seçim" finds "SEÇİMLER".
  const PLAIN = { 'ı': 'i', 'ğ': 'g', 'ü': 'u', 'ş': 's', 'ö': 'o', 'ç': 'c', 'â': 'a', 'î': 'i', 'û': 'u', '̇': '' };
  const fold = (s) => s.replace(/I/g, 'ı').replace(/İ/g, 'i').toLowerCase().replace(/[ığüşöçâîû̇]/g, (c) => PLAIN[c]);
  // Words to look for (at most 8); apostrophe suffixes dropped: "İstanbul'da" → "istanbul".
  const termsOf = (q) => [...new Set(fold(q.slice(0, 200).replace(/['’‘`ʼ]\p{L}+/gu, '')).split(/[^\p{L}\p{N}]+/u).filter(Boolean))].slice(0, 8);
  // sprintf for the page's texts: "%d", "%s" and "%2$s".
  const fmt = (s, ...args) => { let i = 0; return s.replace(/%(?:(\d+)\$)?([ds])/g, (m, n) => String(args[n ? n - 1 : i++])); };
  const el = (tag, cls, text) => {
    const e = document.createElement(tag);
    if (cls) e.className = cls;
    if (text !== undefined) e.textContent = text;
    return e;
  };

  let index = null;
  let loading = null;
  const load = () => (loading ??= fetch(results.dataset.index)
    .then((r) => { if (!r.ok) throw new Error(String(r.status)); return r.json(); })
    .then((d) => { d.folded = d.articles.map((a) => fold(a[3] + ' ' + a[4])); index = d; })
    .catch((e) => { loading = null; throw e; }));

  const state = () => {
    const p = new URLSearchParams(location.hash.slice(1));
    const side = p.get('side') || '';
    return { q: (p.get('q') || '').trim(), side: LABELS.includes(side) ? side : '' };
  };
  const hashFor = (q, side) => {
    const p = new URLSearchParams();
    if (q) p.set('q', q);
    if (side) p.set('side', side);
    return '#' + p.toString();
  };

  // Coverage bar of a story: [gov, ind, opp, outlets].
  function bar(s) {
    const cov = el('div', 'cov');
    cov.setAttribute('role', 'img');
    cov.setAttribute('aria-label', LABELS.map((l, i) => S.labels[l] + ': ' + s[i]).join(', '));
    const b = el('div', 'bar');
    LABELS.forEach((l, i) => {
      if (s[i] > 0) {
        const seg = el('span', 'seg seg-' + l);
        seg.style.flexGrow = s[i];
        b.append(seg);
      }
    });
    cov.append(b);
    return cov;
  }

  // One result, the same markup as the Latest news page.
  function item(a) {
    const [src, , time, title, summary, url, story] = a;
    const [name, label] = index.sources[src] || [src, 'ind'];
    const li = el('li', 'news-item');
    const head = el('p', 'news-src');
    const sw = el('span', 'sw sw-' + label);
    sw.setAttribute('aria-hidden', 'true');
    head.append(sw, el('span', 'outlet', name), el('time', '', time));
    const h = el('h3', 'hl hl-entry');
    if (/^https?:\/\//i.test(url)) {
      const link = el('a', '', title);
      link.href = url;
      link.target = '_blank';
      link.rel = 'noopener noreferrer';
      link.referrerPolicy = 'no-referrer';
      h.append(link);
    } else {
      h.textContent = title;
    }
    li.append(head, h);
    if (summary) li.append(el('p', 'entry-sum dek-s', summary));
    const s = story ? index.stories[story] : null;
    if (s) {
      const box = el('div', 'news-story');
      const cmp = el('a', '', fmt(S.compare, s[3]));
      cmp.href = index.story.replace('{id}', story);
      box.append(bar(s), cmp);
      li.append(box);
    }
    return li;
  }

  let matches = [];
  let shown = 0;
  let wrap = null;
  let list = null;
  let lastDay = null;

  function showMore() {
    for (const a of matches.slice(shown, shown + PER_PAGE)) {
      if (a[1] !== lastDay) {
        lastDay = a[1];
        wrap.append(el('h2', 'news-day', index.days[a[1]] || a[1]));
        list = el('ol', 'news-list');
        wrap.append(list);
      }
      list.append(item(a));
    }
    shown = Math.min(shown + PER_PAGE, matches.length);
    moreWrap.hidden = shown >= matches.length;
  }

  async function run() {
    const { q, side } = state();
    input.value = q;
    results.replaceChildren();
    moreWrap.hidden = true;
    sides.hidden = true;
    const terms = termsOf(q);
    if (!terms.length) {
      status.textContent = '';
      return;
    }
    status.textContent = S.loading;
    try {
      await load();
    } catch (e) {
      status.textContent = S.failed;
      return;
    }
    const now = state();
    if (now.q !== q || now.side !== side) return; // a newer search took over while loading

    matches = index.articles.filter((a, i) =>
      (!side || (index.sources[a[0]] || [])[1] === side) && terms.every((t) => index.folded[i].includes(t)));
    status.textContent = matches.length === 1 ? fmt(S.one, q) : fmt(S.count, matches.length, q);
    for (const a of sides.querySelectorAll('a')) {
      a.href = hashFor(q, a.dataset.side);
      if (a.dataset.side === side) a.setAttribute('aria-current', 'page');
      else a.removeAttribute('aria-current');
    }
    sides.hidden = false;
    if (!matches.length) {
      results.append(el('p', 'rail-none', S.none));
      return;
    }
    wrap = el('div', 'news');
    results.append(wrap);
    shown = 0;
    lastDay = null;
    showMore();
  }

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    location.hash = hashFor(input.value.trim(), state().side);
  });
  more.addEventListener('click', showMore);
  window.addEventListener('hashchange', run);
  if (!navigator.connection?.saveData) input.addEventListener('focus', () => load().catch(() => {}), { once: true });
  run();
  if (!state().q) input.focus();
})();
