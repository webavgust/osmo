# patch v41 — фильтр по партнёру в реестре сделок Битрикс24

Ставится поверх v40. Просьба владельца 28.09.2026: в фильтре реестра (`/bitrix/deal`) — поле «Партнёр».

Партнёр сделки — её компания в Битриксе (`crm_deal.company_id` / `company_name`, в таблице — фиолетовая
плашка слева от стрелки). Значение фильтра — id компании (название может смениться), подпись — название.
Сделки без компании — пункт «партнёр не указан» (`PARTNER_EMPTY = 'none'`). Выбор множественный.

## Файлы

- `app/Modules/Bitrix/CrmDeal/Services/CrmDealRegistryService.php` — `partner` в `DEFAULTS` и `params()`,
  отбор в `rows()`, список `partners()` в `options()`; на карточке партнёра список пустой — поле не рисуется.
- `resources/views/themes/metronic/bitrix/deal/_filter.blade.php` — поле «Партнёр» между «Страной» и «Заказчиком».
- `resources/views/themes/metronic/bitrix/deal/_filter_buttons.blade.php` — поле входит в счётчик «Фильтр (n)».
- `resources/views/themes/metronic/bitrix/deal/box/export.blade.php` — отбор по партнёру уходит в выгрузку Excel.

Миграций, стилей и правок `config/modular.php` нет.

## Проверка (локально, 28.09.2026)

- `partners()` — 61 компания + «партнёр не указан», как на проде.
- Все сделки (КП — не важно) 303; партнёр 425 — 31 (чужих 0); «не указан» — 5; вместе — 36.
- `/bitrix/deal?has_proposal=all&partner[]=425` — 200, 31 строка, партнёр выбран в списке, счётчик 2;
  поиск без перезагрузки (`partial=1`) — адрес выгрузки с `partner`; попап выгрузки — «попадут 31 сделка».
- Вкладка сделок на карточке партнёра — 200, поля «Партнёр» нет.

## Выкатка

1. `git pull`; `su www-root -s /bin/sh -c "php artisan view:clear"`; `find storage bootstrap/cache -user root | wc -l` = 0.

## Чек-лист на проде

- [ ] В фильтре реестра есть «Партнёр», выбор сужает таблицу; «Убрать» сбрасывает.
- [ ] Выгрузка в Excel при выбранном партнёре — только его сделки.
