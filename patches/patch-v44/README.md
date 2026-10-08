# patch v44 — несколько причин проигрыша у КП

Ставится поверх v43. Раньше у КП в статусе «Проиграно» была одна причина (`proposals.status_reason`).
На деле причин часто несколько («дорого» и «ушли к конкуренту»), менеджер выбирал одну, и отчёт
«почему мы теряем деньги» врал. Теперь в попапе статуса можно отметить несколько причин.

## Хранение

- Новая колонка `proposals.status_reasons` — json, массив кодов `ProposalLostReason` в порядке выбора.
- `status_reason` остаётся и всегда равен **первой (основной)** причине — для старых отчётов,
  старой темы и всего, что читает одну причину. Новый код читает список.
- Миграция переносит данные: где `status_reason` заполнен, `status_reasons = [status_reason]`
  (локально — 151 КП из 446).
- Для статусов кроме «Проиграно» обе колонки очищаются.

## Что поменялось

- **Модель `Proposal`**: cast `status_reasons → array`; атрибуты `reasons_enum` (массив enum без неизвестных
  кодов и дублей; у записей без списка — из `status_reason`) и `reasons_decorate`. `reason_enum` /
  `reason_decorate` остались (основная причина).
- **Журнал изменений**: поле «Причины» с подписями через запятую («Дорого, Ушли к конкуренту, Сроки»).
  `status_reason` убран из диффа (`logIgnore`) — он дублирует первую причину, в ленте была бы лишняя
  строка; в слепках он по-прежнему хранится, старые записи «Причина» показываются как были.
- **`ProposalLostReason`**: `fromCodes()` (массив / json / один код → список enum) и `labels()`.
- **`ProposalStatusService::set()`**: `reason` принимает массив причин (enum или коды), одиночная
  причина — как раньше. Для «Проиграно» нужна хотя бы одна, иначе исключение → ошибка в toastr.
- **API** `POST /api/proposal/status/{group}/{iteration}`: `reasons[]` (новое) и `reason` (старое) —
  объединяются, неизвестные коды и повторы отбрасываются. Попап старой темы шлёт `reason` и работает дальше.
- **Попап статуса (Metronic)** — новый файл в теме: плитки причин с чекбоксами, цветная точка причины,
  подсказка; номер у плитки — порядок выбора (первая — основная). При открытии отмечены сохранённые.
  Без причин для «Проиграно» не сохраняется (toastr на клиенте, ошибка сервера — тоже toastr).
- **Плашка статуса** `<x-proposal.status>`: все причины плашками; в `stacked` (узкие колонки) —
  основная и «+N» с полным списком в подсказке. Ячейка статуса в списке КП: в подсказке все причины.
- **Виджет «Карточка КП»**: «причины: Дорого, Ушли к конкуренту, Сроки» (при одной — «причина»).
- **Виджет «Причины проигрыша»** (`lost_reasons`): КП с несколькими причинами входит в каждую свою
  причину — число КП по причине честное, доли в сумме дают больше 100 %. **Сумма КП делится между его
  причинами поровну**: так суммы по причинам складываются ровно в сумму проигранного, а итог в шапке
  («71 КП», сумма) считается по уникальным КП. Полную сумму в каждой причине не брали: при сложении
  строк деньги считались бы дважды. Если у кого-то в отборе несколько причин — в подсказках итога и строк
  пояснение «У N КП несколько причин…».
- Копия КП и выигрыш по спецификации сбрасывают и `status_reasons`.

## Файлы

- `database/migrations/2026_10_08_120000_add_status_reasons_to_proposals_table.php` — новая.
- `app/Modules/Pub/Proposal/Models/Proposal.php` — cast, fillable, атрибуты, журнал.
- `app/Modules/Pub/Proposal/Models/ProposalLostReason.php` — `fromCodes()`, `labels()`.
- `app/Modules/Pub/Proposal/Services/ProposalStatusService.php` — `set()` со списком причин.
- `app/Modules/Pub/Proposal/Controllers/Api/ApiProposalStatusController.php` — `reasons[]` в `status()`
  (в этом же файле правки v43 по сделкам — не путать).
- `app/Modules/Pub/Desktop/Widgets/Proposal/LostReasonsWidget.php`, `ProposalCardWidget.php`.
- `app/Modules/Pub/ProposalTools/Services/ProposalCloneService.php`,
  `app/Modules/Pub/ContractSpecification/Services/SpecProposalService.php` — сброс `status_reasons`.
- `resources/views/themes/metronic/pub/proposal/boxes/status.blade.php` — новый (копия старого попапа под Metronic).
- `resources/views/themes/metronic/pub/desktop/widgets/lost_reasons.blade.php`, `proposal_card.blade.php`.
- `resources/views/components/proposal/status.blade.php`, `components/proposal/table/main/status.blade.php`.

Стилей, JS-файлов и правок `config/modular.php` нет.

## Проверка (локально, 09.10.2026)

- Миграция up → down → up: колонка появляется/убирается, перенесено 151 КП (canceled 89, frozen 52,
  price 5, no_response 2, other 2, functional 1), у всех `status_reasons = [status_reason]`.
- КП AA786 через API: «Проиграно» без причин и только с мусорным кодом — ошибка «нужно указать причину»;
  `reasons[]=price,competitor,zzz,price` + `reason=timeline` → `["price","competitor","timeline"]`,
  `status_reason = price` в обеих редакциях. Попап снова — отмечены 3 плитки с номерами 1–3.
  Смена на «В работе» → обе колонки `NULL`, попап без отметок. Журнал: «Причины: — → Дорого, Ушли к
  конкуренту, Сроки» и обратно. После проверки КП возвращено как было, тестовые записи журнала удалены.
- Попап в браузере: клики по плиткам ставят/снимают отметку и порядок; без причин — toastr
  «Отметьте хотя бы одну причину», запрос не уходит; ошибок в консоли от попапа нет.
- Карточка КП, список КП (`list_table`: подсказка «Дорого, Ушли к конкуренту, Сроки»), рабочий стол,
  рендер виджетов `lost_reasons` (таблица, кольцо) и `proposal_card` — 200; в `laravel.log` от этих запросов ошибок нет.
- `lost_reasons` на живых данных (за всё время): 71 КП, 73 упоминания причин, у 1 КП несколько;
  сумма строк 13 476 737 704,70 = сумма проигранного 13 476 737 704,69 (копейка — округление строк).
- Сетка `widget_grid.php` 2…16 × 2…32: `lost_reasons` (образец, живые, живые с кольцом) и
  `proposal_card` (живые, КП с тремя причинами) — только `T`, `f`, `·`/`0`; кодов X, C, H, F, G нет.

## Известное

- Первая запись журнала у КП, проигранного до v44, покажет «Причины: — → …»: в старых слепках
  колонки ещё нет. Один раз на КП, при следующем его изменении.

## Выкатка

1. `git pull`.
2. Копия базы: `php patches/deploy-v21-v32/backup_db.php` (файл в `/root/backup`, метка времени в имени).
3. `su www-root -s /bin/sh -c "php artisan migrate --path=database/migrations/2026_10_08_120000_add_status_reasons_to_proposals_table.php --force"`.
4. Сверка: `select count(*) from proposals where status_reason is not null and status_reason <> ''`
   = `select count(*) from proposals where status_reasons is not null`.
5. `su www-root -s /bin/sh -c "php artisan view:clear"`; `find storage bootstrap/cache -user root | wc -l` = 0.

## Чек-лист на проде

- [ ] Попап статуса: у «Проиграно» плитки причин, можно отметить несколько, номера порядка.
- [ ] Без причин «Проиграно» не сохраняется — toastr.
- [ ] Сохранить 2 причины → на карточке КП две плашки; открыть попап — обе отмечены.
- [ ] Сменить на «В работе» → плашки причин пропали.
- [ ] Рабочий стол, «Причины проигрыша»: КП учтён в обеих причинах, итог КП в шапке не вырос.
- [ ] Журнал изменений КП: строка «Причины» с подписями через запятую.
