# Прогон по сетке — что осталось (пауза 14.09.2026)

## Статус групп
Готовы полностью (итог в `grid/{группа}.md`, матрицы без X/C/H/F/G): proposal-1, proposal-2,
funnel-1, funnel-2, finance, partner, personal, keys-admin, analytics — 66 виджетов.

Общая категория (Common, 16) — агенты остановлены владельцем на паузу, часть работы не доведена:
- готовы и описаны: `banner`, `button`, `journal`, `link`, `progress`;
- описаны, но не закончены проверки: `changes` (живые данные, без заголовка, render_widgets),
  `currency` (живые данные), `kpi` (правки после описания — перепрогнать всю сетку);
- начаты, не описаны (вьюха могла остаться недописанной — сначала прогнать стенд):
  `chart` (common-1), `rates` (common-3);
- не начаты: `compare`, `embed`, `heading`, `links`, `period`, `team`.
Файлы стилей: `common-1.css` (banner, button, changes, chart), `common-2.css` (journal, kpi, link),
`common-3.css` (progress, rates), `common-4.css` (currency). Задание агентам — в README ниже не
дублируется: см. `WIDGETS.md` «Прогон по сетке» и правила там же; агенту — не больше одной долгой
команды в сообщении.

## Общие правки, уже сделанные
- `.desk-only-*` перебивались `d-flex` (!important) — правило перевёрнуто: прячем, пока места мало
- `title` у заголовка виджета (`partials/widget.blade.php`)
- стенд не считает подпись «ещё N» строкой подгона

## Общие предложения агентов — разобрать
1. `osmo-desktop-fit.js` вешает `desk-fit-out` на подпись «ещё N», даже когда ничего не спрятано
   (стенд уже её не считает; решить, прятать ли подпись другим классом) (personal)
2. Помощник «сумма в две строки» в `Widget.php` — один `preg_match` по `compact()` в 5+ вьюхах
   (finance, proposal-1)
3. Общие приёмы в `osmo-desktop.css` вместо копий по группам: строка итогов с прятанием по ширине
   (`.fin-line`), вертикальный столбик доли (`.fin-thermo`), строки делят высоту, подпись над
   значением в узкой колонке, узкая строка «название над суммой», «число + пояснение рядом»,
   выше ячейки на высоких блоках, нулевые боковые отступы уже 170 px, плитки итогов
   (`an-tiles`) (finance, funnel-1, funnel-2, keys-admin, analytics)
4. `.min-w-0 { min-width: 0 }` — в Metronic нет (analytics)
5. Таблица: спрятана первая колонка — у видимой остаётся левый отступ `:first-child` (funnel-2)
6. Сохранённые столы держат старые лимиты списков (8/12/15) — высокие блоки на них короче; у
   новых умолчание — максимум (funnel-1, finance, keys-admin)
7. manager_quarter: кварталы 5–8 видны только от 900 px (funnel-2)
8. Меняли `data()`: keys_expiring до 30 ключей (было 3), renewals `totals()` до 40 (было 20),
   countdown + поля `progress`, `period`, contracts_unsigned разбивка по давности,
   payments_fact/payment_summary ряд для графиков, `PaymentsFactWidget::total()` необязательный
   параметр
9. `proposal_card` на живых данных не проверен: в `target` нужен `group` КП (uuid из адреса),
   а не числовой id: `--live --settings='{"target":{"type":"proposal","id":"<group>"}}'`
10. Стенд: несколько прогонов разом у разных агентов выходят за 90 с
11. `osmo-desktop-charts.js` не понимает цвета вида `#ffb604` (подставляет синий) — поэтому у
    partners_grades нет кольца грейдов (partner)

## После всех групп
- свести `public/metronic/css/osmo-desktop-widgets/*.css` в `osmo-desktop-widgets.css`
  (скрипт: собрать по разделу на группу), в `index.blade.php` заменить glob одним `<link>`
- прогнать все 80 виджетов стендом, проверить стол в браузере (1920×1080, стол владельца не сохранять)
