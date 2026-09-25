// Аудит вёрстки темы Metronic: кадры + метрики стилей по страницам.
// node audit.js <mode> [--only=01-desktop,02-proposals] [--out=dir]
// mode: light (1600×1000, fullPage), dark (1600×1000, экран), mobile (390×844, fullPage)
const fs = require('fs');
const path = require('path');
const playwright = require('playwright'); // запуск: NODE_PATH=<каталог shots>/node_modules (см. README)

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8123';
const args = process.argv.slice(2);
const mode = args.find(a => !a.startsWith('--')) || 'light';
const only = (args.find(a => a.startsWith('--only=')) || '').slice(7).split(',').filter(Boolean);
const outDir = (args.find(a => a.startsWith('--out=')) || '').slice(6) || (process.env.AUDIT_OUT || path.join(require('os').tmpdir(), 'osmo-audit'));
const pages = JSON.parse(fs.readFileSync(path.join(__dirname, (args.find(a => a.startsWith('--pages=')) || '--pages=pages.json').slice(8)), 'utf8'))
  .filter(p => !only.length || only.includes(p.name));
fs.mkdirSync(outDir, { recursive: true });

const HIDE_CSS = `
  .phpdebugbar, #debugbar { display: none !important; }
  #toasts { display: none !important; }
  *, *::before, *::after { transition: none !important; animation: none !important; caret-color: transparent !important; }
`;

// Метрики считаются в странице
function collect() {
  const vis = el => {
    const r = el.getBoundingClientRect();
    if (r.width < 1 || r.height < 1) return false;
    const s = getComputedStyle(el);
    return s.visibility !== 'hidden' && s.display !== 'none' && parseFloat(s.opacity) > 0.05;
  };
  const tally = arr => { const m = {}; arr.forEach(v => { m[v] = (m[v] || 0) + 1; }); return Object.entries(m).sort((a, b) => b[1] - a[1]); };
  const sel = el => {
    const cls = [...el.classList].slice(0, 4).join('.');
    return el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + (cls ? '.' + cls : '');
  };
  const path = el => { const p = []; let e = el; for (let i = 0; e && i < 4; i++, e = e.parentElement) p.unshift(sel(e)); return p.join(' > '); };
  const parse = c => { const m = c && c.match(/rgba?\(([^)]+)\)/); if (!m) return null; const p = m[1].split(',').map(x => parseFloat(x)); return { r: p[0], g: p[1], b: p[2], a: p.length > 3 ? p[3] : 1 }; };
  const lum = ({ r, g, b }) => { const f = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); }; return 0.2126 * f(r) + 0.7152 * f(g) + 0.0722 * f(b); };
  const bgOf = el => {
    const layers = []; let e = el;
    while (e) { const c = parse(getComputedStyle(e).backgroundColor); if (c && c.a > 0) { layers.push(c); if (c.a >= 0.99) break; } e = e.parentElement; }
    let base = document.documentElement.getAttribute('data-bs-theme') === 'dark' ? { r: 21, g: 23, b: 30 } : { r: 255, g: 255, b: 255 };
    for (let i = layers.length - 1; i >= 0; i--) { const l = layers[i]; base = { r: l.r * l.a + base.r * (1 - l.a), g: l.g * l.a + base.g * (1 - l.a), b: l.b * l.a + base.b * (1 - l.a) }; }
    return base;
  };
  const hex = c => '#' + [c.r, c.g, c.b].map(v => Math.round(v).toString(16).padStart(2, '0')).join('');

  const textEls = []; const seen = new Set();
  const w = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
  while (w.nextNode()) {
    const t = w.currentNode; if (!t.textContent.trim()) continue;
    const el = t.parentElement; if (!el || seen.has(el)) continue;
    if (el.closest('script,style,noscript,.phpdebugbar,#debugbar,template,.d-none,[hidden]')) continue;
    seen.add(el); if (vis(el)) textEls.push(el);
  }
  const lowContrast = []; const tiny = [];
  for (const el of textEls) {
    const s = getComputedStyle(el); const c = parse(s.color); if (!c) continue;
    const bg = bgOf(el);
    const fg = { r: c.r * c.a + bg.r * (1 - c.a), g: c.g * c.a + bg.g * (1 - c.a), b: c.b * c.a + bg.b * (1 - c.a) };
    const L1 = lum(fg), L2 = lum(bg); const ratio = (Math.max(L1, L2) + 0.05) / (Math.min(L1, L2) + 0.05);
    const size = parseFloat(s.fontSize); const bold = parseInt(s.fontWeight, 10) >= 700;
    const large = size >= 24 || (bold && size >= 18.66);
    const txt = [...el.childNodes].filter(n => n.nodeType === 3).map(n => n.textContent).join(' ').trim().replace(/\s+/g, ' ').slice(0, 40);
    if (ratio < (large ? 3 : 4.5)) lowContrast.push({ txt, ratio: +ratio.toFixed(2), fg: hex(fg), bg: hex(bg), size, path: path(el) });
    if (size < 11) tiny.push({ txt, size, path: path(el) });
  }
  const all = [...document.querySelectorAll('body *')].filter(el => !el.closest('.phpdebugbar,#debugbar')).filter(vis);
  const boxes = all.filter(el => { const s = getComputedStyle(el); return (parse(s.backgroundColor)?.a > 0) || parseFloat(s.borderTopWidth) > 0 || parseFloat(s.borderLeftWidth) > 0; });
  const iconStyles = tally([...document.querySelectorAll('i[class*="fa-"], i.fa, i.fas, i.far, i.fal, i.fad')].filter(vis).map(i => {
    const c = [...i.classList]; return c.find(x => /^(fa-(light|regular|solid|thin|duotone|brands|sharp)|fas|far|fal|fad|fab|fat|fa)$/.test(x)) || 'none';
  }));
  const btns = tally([...document.querySelectorAll('.btn')].filter(vis).map(b => [...b.classList].filter(x => /^btn-(?!icon$|sm$|lg$|flex$|active)/.test(x) && !/^btn-(active|color|text|bg|icon-)/.test(x)).sort().join(' ') || 'btn'));
  const badges = tally([...document.querySelectorAll('.badge')].filter(vis).map(b => [...b.classList].filter(x => /^badge-/.test(x)).sort().join(' ') || 'badge'));
  const overflowX = document.documentElement.scrollWidth - window.innerWidth;
  const outside = all.filter(el => { const r = el.getBoundingClientRect(); return r.right > window.innerWidth + 1 && r.width < window.innerWidth * 2; })
    .filter(el => { let p = el.parentElement; while (p) { const o = getComputedStyle(p).overflowX; if (o === 'hidden' || o === 'auto' || o === 'scroll' || o === 'clip') return false; p = p.parentElement; } return true; })
    .slice(0, 8).map(el => ({ path: path(el), right: Math.round(el.getBoundingClientRect().right) }));
  const brokenImg = [...document.images].filter(i => i.complete && i.naturalWidth === 0 && vis(i)).map(i => i.getAttribute('src'));
  const heads = [...document.querySelectorAll('h1,h2,h3,h4,h5,h6,.card-title,.page-heading')].filter(vis).slice(0, 25)
    .map(h => ({ tag: h.tagName.toLowerCase() + (h.classList.contains('card-title') ? '.card-title' : ''), txt: h.textContent.trim().replace(/\s+/g, ' ').slice(0, 40), size: getComputedStyle(h).fontSize, weight: getComputedStyle(h).fontWeight }));
  const emptySquares = [...document.querySelectorAll('i[class*="fa-"]')].filter(vis).filter(i => {
    const b = getComputedStyle(i, '::before').content; return !b || b === 'none' || b === 'normal' || b === '""';
  }).slice(0, 10).map(i => i.className);
  return {
    title: document.title.trim(), url: location.pathname + location.search,
    size: { w: window.innerWidth, h: document.documentElement.scrollHeight }, overflowX,
    textCount: textEls.length,
    fontSizes: tally(textEls.map(e => getComputedStyle(e).fontSize)).slice(0, 16),
    fontWeights: tally(textEls.map(e => getComputedStyle(e).fontWeight)),
    families: tally(textEls.map(e => getComputedStyle(e).fontFamily.split(',')[0].trim())),
    colors: tally(textEls.map(e => getComputedStyle(e).color)).slice(0, 16),
    radii: tally(boxes.map(e => getComputedStyle(e).borderRadius)).slice(0, 12),
    shadows: tally(all.map(e => getComputedStyle(e).boxShadow).filter(x => x !== 'none')).slice(0, 8),
    iconStyles, btns: btns.slice(0, 20), badges: badges.slice(0, 16),
    lowContrastCount: lowContrast.length,
    lowContrast: Object.values(lowContrast.reduce((m, x) => { const k = x.fg + x.bg + x.size; if (!m[k]) m[k] = { ...x, n: 0 }; m[k].n++; return m; }, {})).sort((a, b) => b.n - a.n).slice(0, 12),
    tinyCount: tiny.length, tiny: tiny.slice(0, 6),
    outside, brokenImg, heads, emptyIcons: emptySquares,
  };
}

// Размеры в CSS-пикселях; WebKit на этой машине масштабирует окно по DPI Windows,
// поэтому размер окна пересчитывается через замер innerWidth.
const TARGET = { light: [1440, 900], dark: [1440, 900], wide: [1920, 1080], mobile: [390, 844], print: [1440, 900] };

// Макет PDF из КП: попап «Создание PDF» отдаёт форму с csrf — отправляем её в этой же вкладке
async function openPdf(page, p) {
  await page.goto(BASE + p.pdf.box, { waitUntil: 'load' });
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }),
    page.evaluate(o => {
      const f = document.querySelector('#generate');
      f.removeAttribute('target');
      f.querySelector('select[name=language]').value = o.language;
      f.querySelector('select[name=template]').value = o.template;
      f.submit();
    }, p.pdf),
  ]);
}

(async () => {
  const browser = await playwright.webkit.launch();
  const probe = await browser.newContext({ viewport: { width: 1000, height: 1000 } });
  const pp = await probe.newPage(); await pp.goto('about:blank');
  const ratio = 1000 / await pp.evaluate(() => window.innerWidth); await probe.close();
  const [tw, th] = TARGET[mode] || TARGET.light;
  const vp = { width: Math.round(tw * ratio), height: Math.round(th * ratio) };
  const results = {};
  const mfile = path.join(outDir, `metrics-${mode}.json`);
  if (fs.existsSync(mfile)) Object.assign(results, JSON.parse(fs.readFileSync(mfile, 'utf8')));
  for (const p of pages) {
    const t0 = Date.now();
    const ctx = await browser.newContext({ viewport: vp, locale: 'ru-RU', deviceScaleFactor: 1, isMobile: false, hasTouch: mode === 'mobile' });
    if (mode === 'dark') await ctx.addInitScript(() => { try { localStorage.setItem('data-bs-theme', 'dark'); } catch (e) {} });
    const page = await ctx.newPage();
    page.setDefaultTimeout(25000);
    const errors = [];
    page.on('pageerror', e => errors.push('pageerror: ' + String(e.message).slice(0, 200)));
    page.on('console', m => { if (m.type() === 'error') errors.push('console: ' + m.text().slice(0, 200)); });
    page.on('response', r => { if (r.status() >= 400 && !/debugbar|favicon/.test(r.url())) errors.push(r.status() + ' ' + r.url().replace(BASE, '').slice(0, 120)); });
    try {
      if (p.guest) {
        await page.goto(BASE + '/ui-theme/metronic', { waitUntil: 'domcontentloaded' }).catch(() => {});
        await ctx.clearCookies();
        await ctx.addCookies([{ name: 'ui_theme', value: 'metronic', url: BASE }]);
      } else {
        await page.goto(BASE + '/ui-theme/metronic', { waitUntil: 'domcontentloaded' });
      }
      if (p.pdf) await openPdf(page, p);
      else await page.goto(BASE + p.url, { waitUntil: 'load' });
      await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
      if (mode === 'print') await page.emulateMedia({ media: 'print' });
      if (p.waitFor) await page.waitForSelector(p.waitFor, { timeout: 8000 }).catch(() => {});
      await page.addStyleTag({ content: HIDE_CSS });
      // ленивые блоки стола грузятся после отрисовки — даём время
      await page.waitForTimeout(p.wait || 700);
      if (p.wait) await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
      const m = await page.evaluate(collect);
      m.errors = errors.slice(0, 12); m.ms = Date.now() - t0;
      results[p.name] = m;
      // fullPage в WebKit под Windows режет кадр (масштаб DPI) — вместо него растягиваем окно на высоту документа
      const full = mode !== 'dark' && p.full !== false;
      if (full) {
        const hCss = Math.min(await page.evaluate(() => document.documentElement.scrollHeight), p.maxH || 9000);
        if (hCss > th) { await page.setViewportSize({ width: vp.width, height: Math.round(hCss * ratio) }); await page.waitForTimeout(400); }
      }
      await page.screenshot({ path: path.join(outDir, `${p.name}-${mode}.png`) });
      console.log(`${p.name}: ok ${m.ms}ms h=${m.size.h} overflowX=${m.overflowX} lowContrast=${m.lowContrastCount} errors=${errors.length}`);
    } catch (e) {
      results[p.name] = { error: String(e.message).slice(0, 300), errors };
      console.log(`${p.name}: FAIL ${String(e.message).slice(0, 160)}`);
    }
    fs.writeFileSync(mfile, JSON.stringify(results, null, 1));
    await ctx.close();
  }
  await browser.close();
})();
