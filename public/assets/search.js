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
    .then((d) => { d.folded = d.articles.map((a) => [fold(a[3]), fold(a[4])]); index = d; })
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

  // One article: outlet, date and headline, plus the summary when it stands
  // alone (the same markup as the Latest news page) rather than under a story.
  function hit(a, alone) {
    const [src, day, time, title, summary, url] = a;
    const [name, label] = index.sources[src] || [src, 'ind'];
    const li = el('li', alone ? 'news-item' : 'search-hit');
    const head = el('p', 'news-src');
    const sw = el('span', 'sw sw-' + label);
    sw.setAttribute('aria-hidden', 'true');
    head.append(sw, el('span', 'outlet', name), el('time', '', (index.days[day] || day) + ', ' + time));
    const h = alone ? el('h3', 'hl hl-entry') : el('p', 'hl search-hit-hl');
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
    if (alone && summary) li.append(el('p', 'entry-sum dek-s', summary));
    return li;
  }

  // One result: a story covered by several outlets (its headline, coverage
  // bar and up to 3 matching headlines), or a lone article.
  function item(g) {
    const s = g.story ? index.stories[g.story] : null;
    if (!s) return hit(g.hits[0].a, true);
    const href = index.story.replace('{id}', g.story);
    const li = el('li', 'news-item search-story');
    const h = el('h3', 'hl hl-entry');
    const link = el('a', '', s[4]);
    link.href = href;
    h.append(link);
    const box = el('div', 'news-story');
    const cmp = el('a', '', fmt(S.compare, s[3]));
    cmp.href = href;
    box.append(bar(s), cmp);
    const hits = el('ul', 'search-hits');
    for (const x of g.hits.slice(0, 3)) hits.append(hit(x.a, false));
    li.append(h, box, hits);
    const more = g.n - Math.min(3, g.hits.length);
    if (more > 0) {
      const p = el('p', 'search-hits-more');
      const a = el('a', '', fmt(S.more_in, more));
      a.href = href;
      p.append(a);
      li.append(p);
    }
    return li;
  }

  // How well an article matches, or null unless every term is found. Per term:
  // start of a headline word 3, inside one 2, start of a summary word 1,
  // inside one 0.5; averaged. The same as Text::searchScore in the PHP version.
  const escape = (t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  function score(i, terms, words) {
    const [title, summary] = index.folded[i];
    let sum = 0;
    for (let k = 0; k < terms.length; k++) {
      const t = terms[k];
      if (words[k].test(title)) sum += 3;
      else if (title.includes(t)) sum += 2;
      else if (words[k].test(summary)) sum += 1;
      else if (summary.includes(t)) sum += 0.5;
      else return null;
    }
    return sum / terms.length;
  }

  // Matching articles grouped by story, best first: a group ranks by its best
  // article (match score plus a bonus for recent news that halves in about a
  // day) plus a little for each extra match. One headline per outlet is shown.
  function search(terms, side) {
    const words = terms.map((t) => new RegExp('(?:^|[^\\p{L}\\p{N}])' + escape(t), 'u'));
    const now = index.built || Date.now() / 1000;
    const groups = new Map();
    let total = 0;
    index.articles.forEach((a, i) => {
      if (side && (index.sources[a[0]] || [])[1] !== side) return;
      const m = score(i, terms, words);
      if (m === null) return;
      total++;
      const key = a[6] && index.stories[a[6]] ? a[6] : 'a' + i;
      if (!groups.has(key)) groups.set(key, { story: key === a[6] ? a[6] : 0, hits: [] });
      groups.get(key).hits.push({ a, s: m + 1.5 * Math.exp(-Math.max(0, now - a[7]) / 129600) });
    });
    const out = [...groups.values()];
    for (const g of out) {
      g.hits.sort((x, y) => y.s - x.s || y.a[7] - x.a[7]);
      g.n = g.hits.length;
      g.score = g.hits[0].s + 0.5 * Math.log2(g.n);
      g.newest = Math.max(...g.hits.map((x) => x.a[7]));
      const seen = new Set();
      g.hits = g.hits.filter((x) => !seen.has(x.a[0]) && seen.add(x.a[0]));
    }
    out.sort((x, y) => y.score - x.score || y.newest - x.newest);
    return [total, out];
  }

  let matches = [];
  let shown = 0;
  let list = null;

  function showMore() {
    for (const g of matches.slice(shown, shown + PER_PAGE)) list.append(item(g));
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

    const [total, groups] = search(terms, side);
    matches = groups;
    status.textContent = total === 1 ? fmt(S.one, q) : fmt(S.count, total, q);
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
    const wrap = el('div', 'news');
    list = el('ol', 'news-list search-results');
    wrap.append(list);
    results.append(wrap);
    shown = 0;
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
