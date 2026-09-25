// Что вылезает за свою карточку/колонку (и обрезается .app-main { overflow-x: hidden }).
// node overflow.js <ширина CSS> [--pages=pages-all.json] [--only=...]
const fs = require('fs');
const path = require('path');
const playwright = require('playwright'); // запуск: NODE_PATH=<каталог shots>/node_modules (см. README)
const BASE = 'http://127.0.0.1:8123';
const args = process.argv.slice(2);
const width = parseInt(args.find(a => /^\d+$/.test(a)) || '1440', 10);
const only = (args.find(a => a.startsWith('--only=')) || '').slice(7).split(',').filter(Boolean);
const pages = JSON.parse(fs.readFileSync(path.join(__dirname, (args.find(a => a.startsWith('--pages=')) || '--pages=pages-all.json').slice(8)), 'utf8'))
  .filter(p => !p.pdf && !p.guest && (!only.length || only.includes(p.name)));

function detect() {
  const sel = el => el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + ([...el.classList].slice(0, 3).map(c => '.' + c).join(''));
  const vis = el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.height > 0 && getComputedStyle(el).visibility !== 'hidden'; };
  const out = [];
  const content = document.querySelector('#kt_app_content_container') || document.body;
  const cr = content.getBoundingClientRect();
  // Кандидаты: таблицы, строки flex/grid, крупные блоки внутри карточек
  const cands = [...content.querySelectorAll('table, .card, .row, .d-flex, .card-body, .fixed-table-container')].filter(vis);
  for (const el of cands) {
    const r = el.getBoundingClientRect();
    // ближайший предок, который реально ограничивает (card, col-*, content)
    let box = el.parentElement;
    while (box && box !== content && !(box.classList.contains('card') || [...box.classList].some(c => /^col(-|$)/.test(c)))) box = box.parentElement;
    if (!box) box = content;
    const br = box.getBoundingClientRect();
    // есть ли скролл-контейнер между элементом и box
    let p = el.parentElement, scrolls = false;
    while (p && p !== box) { const o = getComputedStyle(p).overflowX; if (o === 'auto' || o === 'scroll') { scrolls = true; break; } p = p.parentElement; }
    if (scrolls) continue;
    const over = Math.round(r.right - br.right);
    const overContent = Math.round(r.right - cr.right);
    if (over > 4 || overContent > 4) out.push({ el: sel(el), box: sel(box), over, overContent, w: Math.round(r.width) });
  }
  // оставляем самые «внешние» нарушители (не повторяем детей)
  const uniq = []; const seen = new Set();
  for (const o of out.sort((a, b) => b.w - a.w)) { const k = o.box + o.over; if (seen.has(k)) continue; seen.add(k); uniq.push(o); }
  return uniq.slice(0, 8);
}

(async () => {
  const browser = await playwright.webkit.launch();
  const probe = await browser.newContext({ viewport: { width: 1000, height: 1000 } });
  const pp = await probe.newPage(); await pp.goto('about:blank');
  const ratio = 1000 / await pp.evaluate(() => window.innerWidth); await probe.close();
  const ctx = await browser.newContext({ viewport: { width: Math.round(width * ratio), height: Math.round(900 * ratio) }, locale: 'ru-RU' });
  const page = await ctx.newPage(); page.setDefaultTimeout(25000);
  await page.goto(BASE + '/ui-theme/metronic', { waitUntil: 'domcontentloaded' });
  const res = {};
  for (const p of pages) {
    try {
      await page.goto(BASE + p.url, { waitUntil: 'load' });
      await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
      await page.waitForTimeout(p.wait || 600);
      res[p.name] = await page.evaluate(detect);
      console.log(p.name, JSON.stringify(res[p.name]));
    } catch (e) { console.log(p.name, 'FAIL', e.message.slice(0, 120)); }
  }
  const outDir = process.env.AUDIT_OUT || path.join(require('os').tmpdir(), 'osmo-audit');
  fs.mkdirSync(outDir, { recursive: true });
  fs.writeFileSync(path.join(outDir, `overflow-${width}.json`), JSON.stringify(res, null, 1));
  await browser.close();
})();
