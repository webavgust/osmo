// Съёмка страниц локального портала для WIKI (Playwright + Firefox).
//
// Запуск: node shot.js <spec.json> [<outDir>] [--only=name1,name2]
//
// spec.json — массив кадров:
// {
//   "name":      "proposals-list",          // имя файла без расширения
//   "url":       "/proposals",              // путь на портале (база — BASE_URL)
//   "viewport":  [1440, 900],               // необязательно, по умолчанию 1440×900
//   "fullPage":  false,                     // вся страница целиком (прокрутка)
//   "clip":      ".card",                   // снять только этот элемент (первый по селектору)
//   "highlight": ["#kt_app_sidebar", ".btn-primary"], // обвести рамкой
//   "hide":      [".toast"],                // спрятать перед кадром (место остаётся)
//   "remove":    [".menu-item"],            // убрать из страницы перед кадром
//   "actions":   [                          // действия перед кадром, по порядку
//     {"click": "a[href='#tab']"}, {"hover": ".dropdown"}, {"fill": ["input[name=q]", "текст"]},
//     {"wait": 500}, {"waitFor": ".table_data tr"}, {"scroll": ".card"}, {"eval": "document.title"}
//   ],
//   "sidebar":   "open" | "closed"          // состояние левого меню (по умолчанию open)
// }
const fs = require('fs');
const path = require('path');
const playwright = require('playwright');

const BASE_URL = process.env.BASE_URL || 'http://127.0.0.1:8123';
// Браузер: BROWSER=firefox|webkit|chromium (Edge и Chromium на этой машине зарезаны политикой)
const BROWSER = process.env.BROWSER || 'webkit';
const HIGHLIGHT_CSS = `
  .wiki-highlight { outline: 3px solid #f1416c !important; outline-offset: 3px !important; border-radius: 6px; }
  .wiki-hidden { visibility: hidden !important; }
  .phpdebugbar, #debugbar { display: none !important; }
  *, *::before, *::after { transition: none !important; animation: none !important; }
`;

async function main() {
  const args = process.argv.slice(2);
  const specFile = args.find(a => !a.startsWith('--'));
  const outDir = args.filter(a => !a.startsWith('--'))[1] || path.join(path.dirname(specFile), 'out');
  const only = (args.find(a => a.startsWith('--only=')) || '').slice(7).split(',').filter(Boolean);
  // подстановка плейсхолдеров {PARTNER}, {COMPANY}, {PROPOSAL}, {DEAL}, {KEY}: --vars=PARTNER=13,COMPANY=37
  const vars = Object.fromEntries((args.find(a => a.startsWith('--vars=')) || '').slice(7).split(',').filter(Boolean).map(p => p.split('=')));
  const subst = s => typeof s === 'string' ? s.replace(/\{([A-Z_]+)\}/g, (m, k) => vars[k] ?? m) : s;
  if (!specFile) throw new Error('нужен файл спецификации');

  // спецификация — один json или каталог с json-файлами
  const specFiles = fs.statSync(specFile).isDirectory()
    ? fs.readdirSync(specFile).filter(f => f.endsWith('.json')).map(f => path.join(specFile, f))
    : [specFile];
  const shots = specFiles.flatMap(f => JSON.parse(subst(fs.readFileSync(f, 'utf8'))))
    .filter(s => !only.length || only.includes(s.name));
  fs.mkdirSync(outDir, { recursive: true });

  const browser = await playwright[BROWSER].launch();
  const failed = [];
  for (const shot of shots) {
    const [w, h] = shot.viewport || [1440, 900];
    const context = await browser.newContext({ viewport: { width: w, height: h }, locale: 'ru-RU', colorScheme: 'light' });
    const page = await context.newPage();
    page.setDefaultTimeout(20000);
    try {
      // тема Metronic хранится в cookie — выставляем её штатной ссылкой переключения
      await page.goto(BASE_URL + '/ui-theme/metronic', { waitUntil: 'domcontentloaded' });
      // networkidle не всегда наступает (длинные опросы) — тогда довольствуемся load
      await page.goto(BASE_URL + shot.url, { waitUntil: 'load' });
      await page.waitForLoadState('networkidle', { timeout: 8000 }).catch(() => {});
      await page.addStyleTag({ content: HIGHLIGHT_CSS });
      // админские пункты подменю профиля в wiki не показываем
      await page.evaluate(() => {
        document.querySelectorAll('#kt_app_sidebar_user .menu-item').forEach(el => {
          if (/Админ-панель|Обновить доступы|^ID:/.test(el.textContent.trim())) el.remove();
        });
        // раздел «Настройки» в левом меню (заголовок и пункты до следующего заголовка) — служебный
        const heading = [...document.querySelectorAll('#kt_app_sidebar_menu_wrapper .menu-heading')]
          .find(h => h.textContent.trim().toLowerCase() === 'настройки');
        if (heading) {
          let item = heading.closest('.menu-item');
          while (item) {
            const next = item.nextElementSibling;
            item.remove();
            if (!next || next.querySelector('.menu-heading')) break;
            item = next;
          }
        }
      });
      // светлая тема и состояние меню
      await page.evaluate((sidebar) => {
        document.documentElement.setAttribute('data-bs-theme', 'light');
        if (sidebar === 'closed') document.body.setAttribute('data-kt-app-sidebar-minimize', 'on');
        else document.body.removeAttribute('data-kt-app-sidebar-minimize');
      }, shot.sidebar || 'open');
      for (const action of shot.actions || []) {
        if (action.click) await page.locator(action.click).first().click();
        else if (action.hover) await page.locator(action.hover).first().hover();
        else if (action.fill) await page.locator(action.fill[0]).first().fill(action.fill[1]);
        else if (action.select) await page.locator(action.select[0]).first().selectOption(action.select[1]);
        else if (action.press) await page.keyboard.press(action.press);
        else if (action.waitFor) await page.locator(action.waitFor).first().waitFor();
        else if (action.scroll) await page.locator(action.scroll).first().scrollIntoViewIfNeeded();
        else if (action.eval) await page.evaluate(action.eval);
        else if (action.wait) await page.waitForTimeout(action.wait);
      }
      await page.evaluate(() => document.fonts.ready);
      await page.waitForTimeout(shot.settle || 400);
      await page.evaluate(({ highlight, hide, remove }) => {
        for (const sel of highlight || []) document.querySelectorAll(sel).forEach(el => el.classList.add('wiki-highlight'));
        for (const sel of hide || []) document.querySelectorAll(sel).forEach(el => el.classList.add('wiki-hidden'));
        for (const sel of remove || []) document.querySelectorAll(sel).forEach(el => el.remove());
      }, { highlight: shot.highlight, hide: shot.hide, remove: shot.remove });
      const file = path.join(outDir, shot.name + '.png');
      if (shot.clip) {
        const el = page.locator(shot.clip).first();
        await el.scrollIntoViewIfNeeded();
        await el.screenshot({ path: file });
      } else {
        await page.screenshot({ path: file, fullPage: !!shot.fullPage });
      }
      console.log('ok  ', shot.name);
    } catch (e) {
      failed.push(shot.name);
      console.log('FAIL', shot.name, '—', e.message.split('\n')[0]);
    } finally {
      await context.close();
    }
  }
  await browser.close();
  if (failed.length) { console.log('не снято:', failed.join(', ')); process.exitCode = 1; }
}

main().catch(e => { console.error(e); process.exit(1); });
