// Тёмная тема: какие блоки остались светлыми (жёстко заданный белый/серый фон).
// node darkwhite.js [--only=...] → out/darkwhite.json
const fs = require('fs');
const path = require('path');
const playwright = require('playwright'); // запуск: NODE_PATH=<каталог shots>/node_modules (см. README)
const BASE = 'http://127.0.0.1:8123';
const args = process.argv.slice(2);
const only = (args.find(a => a.startsWith('--only=')) || '').slice(7).split(',').filter(Boolean);
const pages = JSON.parse(fs.readFileSync(path.join(__dirname, 'pages.json'), 'utf8'))
  .filter(p => !p.guest && (!only.length || only.includes(p.name)));

function detect() {
  const sel = el => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + ([...el.classList].slice(0, 3).map(c => '.' + c).join(''));
  const lum = s => { const m = s.match(/rgba?\(([^)]+)\)/); if (!m) return null; const [r, g, b, a = 1] = m[1].split(',').map(Number); if (a < 0.5) return null; return (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255; };
  const hits = {};
  for (const el of document.querySelectorAll('body *')) {
    const cs = getComputedStyle(el);
    const L = lum(cs.backgroundColor);
    if (L === null || L < 0.8) continue;
    const r = el.getBoundingClientRect();
    if (r.width * r.height < 600 || cs.visibility === 'hidden' || cs.display === 'none') continue;
    // путь до ближайшего узнаваемого предка
    let p = el.parentElement, ctx = '';
    while (p && p !== document.body && !ctx) { if (p.id || p.classList.length) ctx = sel(p); p = p.parentElement; }
    const k = sel(el) + '  <' + ctx + '>  ' + cs.backgroundColor + (el.getAttribute('style') ? '  style=' + el.getAttribute('style').slice(0, 60) : '');
    hits[k] = (hits[k] || 0) + 1;
  }
  return Object.entries(hits).sort((a, b) => b[1] - a[1]).slice(0, 12);
}

(async () => {
  const browser = await playwright.webkit.launch();
  const probe = await browser.newContext({ viewport: { width: 1000, height: 1000 } });
  const pp = await probe.newPage(); await pp.goto('about:blank');
  const ratio = 1000 / await pp.evaluate(() => window.innerWidth); await probe.close();
  const ctx = await browser.newContext({ viewport: { width: Math.round(1440 * ratio), height: Math.round(900 * ratio) }, locale: 'ru-RU' });
  await ctx.addInitScript(() => { try { localStorage.setItem('data-bs-theme', 'dark'); } catch (e) {} });
  const page = await ctx.newPage(); page.setDefaultTimeout(25000);
  await page.goto(BASE + '/ui-theme/metronic', { waitUntil: 'domcontentloaded' });
  const res = {};
  for (const p of pages) {
    try {
      await page.goto(BASE + p.url, { waitUntil: 'load' });
      await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
      await page.waitForTimeout(p.wait || 600);
      res[p.name] = await page.evaluate(detect);
      console.log(p.name, res[p.name].length);
    } catch (e) { console.log(p.name, 'FAIL', e.message.slice(0, 120)); }
  }
  const outDir = process.env.AUDIT_OUT || path.join(require('os').tmpdir(), 'osmo-audit');
  fs.mkdirSync(outDir, { recursive: true });
  fs.writeFileSync(path.join(outDir, 'darkwhite.json'), JSON.stringify(res, null, 1));
  await browser.close();
})();
