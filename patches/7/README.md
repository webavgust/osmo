# Патч v7 — фиксы вёрстки и фильтров

Устанавливается поверх патчей v1–v6.

```bash
cp -R app resources public /путь/к/osmo/
php artisan view:clear && php artisan route:clear
```

**36 файлов.** Патч B (платёжный календарь и сквозная карточка сделки)
идёт следующим — здесь только исправления по вашему списку.

---

## 1. Нечитаемый текст у `badge-light-secondary`

В Metronic `--bs-secondary` почти сливается со светлым фоном бейджа.
Правки в `public/metronic/css/osmo-compat.css`:

```css
:root, [data-bs-theme="light"] {
    --bs-text-secondary: #AAAAAA;   /* как вы просили */
    --bs-secondary-color: #AAAAAA;
}

.badge.badge-light-secondary { color: var(--bs-gray-700) !important; }
.text-secondary { color: var(--bs-gray-600) !important; }
```

Бейджу дал собственный цвет `gray-700`, а не `#AAA`: на фоне
`--bs-secondary-light` серый #AAA всё ещё читается плохо. Подписи
`text-secondary` на белом — `gray-600` по той же причине. Скажите,
если хотите ровно #AAA везде, поменяю в одной строке.

Правка глобальная — работает и в тех шаблонах, которые ещё не переведены.

## 2. ID сделки Битрикс24 в фильтре КП

В фильтре `/proposals` рядом с «Привязана / Не привязана» появилось поле
для ID. Можно указать **несколько ID через запятую** — парсер вытаскивает
все числа из строки, разделители не важны.

Файлы: `pub/proposal/index.blade.php`, `ProposalListFilterService`,
`ListFilterRequest`.

## 3. Фильтр дашборда предлагал менеджеров во всех полях

Копипаста в исходном `bitrix/dashboard/box/filter.blade.php`: у полей
«Вероятность» и «Страна получения средств» стояли `:items="$assigned_by"`
и `name="filter[assigned_by][]"`. Три поля писали в один ключ фильтра,
поэтому выбор затирался.

Исправлено, и оба поля теперь работают по-настоящему:

* **Вероятность** — `crm_deal.probability`;
* **Страна получения средств** — поле компании `uf_crm_1719404976291`
  (то же, по которому строятся отчёты «страна × статус × квартал»).

Списки значений собирает новый `CrmDealRepository::getFilterOptions()`,
фильтрация — новые `case` в `getFiltered()`.

### Нужна одна правка руками

`app/Modules/Bitrix/Dashboard/Controllers/DashboardBoxController.php`,
метод `filter()` — замените тело на:

```php
public function filter()
{
    return View::make('bitrix.dashboard.box.filter', array_merge([
        'title' => 'Фильтр',
        'filter' => DashboardFilterService::getFilter(),
    ], CrmDealRepository::getFilterOptions()));
}
```

и добавьте импорт:

```php
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
```

Файл целиком не заменяю: в нём семь методов разбора матриц дашборда,
точечная правка безопаснее.

## 4. Кнопка «Закрыть» сливалась с фоном при наведении

Причина общая: `btn btn-light-danger` + утилита `text-danger`. При наведении
Metronic заливает кнопку насыщенным цветом и делает текст белым, но
`text-danger` перебивает это по специфичности — красный на красном.

Сделано два уровня защиты:

**CSS** — для всех `btn-light-*` во всех активных состояниях
(`:hover`, `:focus`, `:active`, `.active`, `.show`) возвращён инверсный цвет.
Это чинит и те шаблоны, которых патч не касался.

**Шаблоны** — прошёлся по всем 88 переведённым файлам и убрал `text-*`
из классов, где рядом стоит `btn-light-*`. Исправлено 25 шаблонов.

Четыре общих попапа (`box-large`, `box-static-large`,
`box-static-extralarge`, `box-static-backdrop`) переписаны: кнопка закрытия
стала нейтральной `btn btn-light`, крестик в шапке — на Font Awesome,
диалог получил `modal-dialog-centered modal-dialog-scrollable`.

## 5. Заголовки карточек без отступов

Причина: MaterialPro-разметка `<div class="card-body border-bottom">`
и класс `title-part-padding`, у которого в Metronic нет паддинга.

Переведено на `card-header`:

```blade
<div class="card-header min-h-auto py-5 border-bottom">
    <div class="card-title flex-column align-items-start">
        <h4 class="fw-bold mb-1">Заголовок</h4>
        <span class="text-muted fs-7">Подзаголовок</span>
    </div>
</div>
```

`card-header` в Metronic — это flex с `justify-content: space-between`,
поэтому кнопки справа от заголовка встают на место без дополнительных
классов. Шапки с кнопками (`.row` + `.col`, плашки «Руководители»
и «Подчинённые») сохранили свою разметку — им заменён только внешний класс.

В CSS добавлена страховка: `.title-part-padding` получил паддинг для
шаблонов, до которых патч ещё не дошёл, и `card-header` переносит
содержимое на мобильных вместо сжатия.

---

## Проверка

- [ ] бейджи «В работе», «Нет сделки», «Отпала потребность» читаются
- [ ] кнопка «Закрыть» в попапах не сливается при наведении
- [ ] заголовки карточек (создание сценария, доступа, группы) с отступами
- [ ] заголовок + кнопка в одной шапке не наезжают друг на друга
- [ ] фильтр дашборда: в «Вероятность» проценты, в «Страна» — страны
- [ ] фильтр дашборда сохраняется и применяется
- [ ] фильтр КП по ID сделки находит нужное, несколько ID через запятую работают

---

## Дальше

Патч B — платёжный календарь и сквозная карточка сделки.
