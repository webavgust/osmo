# Выкатка патчей v21–v32 на продакшн

Прод (`/var/www/www-root/data/www/osmo-avg.ru`) стоит на коммите `b0fb1af` — ни один из патчей
v21–v32 на нём не выполнен. На проде работали: после локального дампа 09.09 там 5 новых КП и
1 новая компания. **База прода не переносится и не перезаливается** — только миграции и
идемпотентный SQL поверх живых данных.

## Что проверено на проде заранее (15.09.2026, только чтение)

- `git status` чистый: код на сервере не правили; неотслеживаемые `!patches/`, `Databases.db`,
  `index.html` — **не трогать, `git clean` не делать**.
- Все старые миграции выполнены, новых 16 — ни одной их таблицы и колонки на проде ещё нет.
- Дублей ключей в `consts` нет — миграция `add_note_to_consts` с уникальным индексом пройдёт.
- Разделы меню `1 Настройки`, `10 Работа`, `15 Отчёты`, `21 Bitrix` на месте; пунктов
  `/bitrix/deal`, `/external-proposals`, `/desktop` ещё нет.
- Права `general_access` (1), `super_user` (6), `payment_calendar_view` (8), `deal_card_view` (9) есть.
- Пользователей 7, новых нет. **v28 оставит админом только Анну** (сейчас `is_admin` у 1, 4, 5, 6, 7,
  `super_user` у всех) — остальным выдаются права публичной части.
- **v27 переведёт 12 КП** («Отменено» 11, «Заморожено» 1) в «Проиграно» с причиной.
- Подключение портала видит `avgbitrix` (383 компании) — SQL v23 сопоставит 37 партнёров.
- База `avgmom` — 11 МБ, место на диске 9,7 ГБ, `mysqldump` есть; PHP 8.2.10.
- В `.env` прода нет `OSMOVIEW_CP_URL` / `OSMOVIEW_CP_KEY`. `.env` на проде с переводами строк CRLF.
- composer-пакеты не менялись — `composer install` не нужен, только `dump-autoload`.

## 0. Локально (по команде владельца)

```
git add -A
git commit            # патчи v27–v32 + deploy-v21-v32
git push origin master
```

## 1. Прод: резервная копия — до любых изменений

Скрипта на сервере до `git pull` ещё нет, поэтому он передаётся по SSH:

```
ssh osmo 'cd /var/www/www-root/data/www/osmo-avg.ru && php' < patches/deploy-v21-v32/backup_db.php
```

Ждём строку «Резервная копия: /root/backup/avgmom-….sql.gz (… МБ)». Нет строки — **дальше не идём**.

## 2. Прод: выкатка

```
cd /var/www/www-root/data/www/osmo-avg.ru
php artisan down                         # страница «на обслуживании» на время миграций
git pull                                 # без git clean
git status --porcelain                   # только !patches/, Databases.db, index.html
composer dump-autoload
php artisan migrate --pretend            # посмотреть SQL 16 миграций
php artisan migrate --force
```

В `.env` дописать (значения — из локального `.env`, в git не класть):

```
OSMOVIEW_CP_URL=…
OSMOVIEW_CP_KEY=…
```

SQL патчей — строго в этом порядке. `--dry-run` выполняет запросы в транзакции и откатывает —
видно, сколько строк изменит каждый запрос на данных прода (в файлах только UPDATE / INSERT /
DELETE, без DDL). Затем без `--dry-run` — каждый файл в своей транзакции, при ошибке файл
откатывается и выполнение останавливается:

```
F="patches/patch-v21/database/sql/patch_v21_menu.sql
patches/patch-v22/database/sql/patch_v22_menu.sql
patches/patch-v23/database/sql/patch_v23_partner_match.sql
patches/patch-v27/database/sql/patch_v27_menu.sql
patches/patch-v27/database/sql/patch_v27_proposal_status.sql
patches/patch-v28/database/sql/patch_v28_admin.sql
patches/patch-v28/database/sql/patch_v28_consts.sql
patches/patch-v30/database/sql/patch_v30_menu.sql
patches/patch-v30/database/sql/patch_v30_consts.sql"
php patches/deploy-v21-v32/run_sql.php --dry-run $F
php patches/deploy-v21-v32/run_sql.php $F
```

Кэши и команды заполнения:

```
php artisan optimize:clear
php artisan cache:clear                  # права (can_do_*) и меню (menu_tree_*) кэшируются навсегда
php artisan deal-project:seed --dry-run  # посмотреть, какие проекты создадутся из сделок
php artisan deal-project:seed
php artisan entity-log:baseline          # первые слепки КП, партнёров, компаний, проектов
php artisan up
```

`entity-log:rediff` на проде не нужен — старых событий журнала там нет.

## 3. Проверка после выкатки

- [ ] Вход → «Рабочий стол»; меню: «Работа» (Рабочий стол, КП, Внешние КП, Реестр сделок
      Битрикс24, Воронка продаж), «Отчёты» с разделителями, раздела «Bitrix» нет
- [ ] КП: правка количества → «Журнал изменений» показывает событие; `?at=` открывается
- [ ] Реестр сделок Битрикс24: вкладки «Проекты» и «Архив проектов», карточка проекта
- [ ] Карточка партнёра: блок «Битрикс24», вкладки сделок; скоринг партнёров считается
- [ ] «Внешние КП»: список грузится из OSMOVIEW CP (ключ в `.env`)
- [ ] Админ-панель (Анна): пользователи, константы; у обычного пользователя меню не пустое
- [ ] Рабочий стол: «Редактировать» → библиотека снизу, добавить виджет, сохранить, перезагрузить
- [ ] Новые КП, созданные на проде после 09.09, на месте и открываются

## 4. Руками после выкатки

- Сопоставить с Битрикс24 партнёров, не найденных по названию (`/partners/edit/{id}` → «Компании в
  Битрикс24»), — список в `patches/patch-v23/README.md`.
- Выдать галочку «Видит журнал изменений» тем, кому нужен журнал (админ-панель → пользователь).
- По желанию: системный пресет рабочего стола «по умолчанию»; константа `desktop_embed_domains`.

## Откат

```
php artisan down
git checkout b0fb1af                     # код как до выкатки (неотслеживаемые файлы не трогаются)
# база: восстановить из /root/backup/avgmom-….sql.gz (mysql с теми же учётными данными)
php artisan optimize:clear && php artisan cache:clear
php artisan up
```

## Известное

- Прогон виджетов стола по сетке не закончен для 14 виджетов категории «Общие»
  (`patches/patch-v30/grid/TODO.md`) — это вёрстка на нестандартных размерах, ошибок отрисовки
  нет (`render_widgets.php`: 1172 проверки, 0 ошибок).
