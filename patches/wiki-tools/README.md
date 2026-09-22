# Инструменты WIKI: съёмка скриншотов

`shot.js` снимает страницы локального портала по спецификациям из
`docs/wiki/img/shots/*.json` и складывает PNG в `docs/wiki/img/`. Формат кадра
описан в шапке скрипта, соглашение о кадрах — `docs/wiki/_GUIDE.md`.

## Установка (один раз, в любом каталоге вне проекта)

```bash
mkdir shots && cd shots && npm init -y && npm i playwright@1.55.0 && npx playwright install webkit
```

Браузер — WebKit: на рабочей машине владельца Edge и Chromium запрещают
отладочный канал политикой, а Firefox из Playwright не запускается
(ошибка side-by-side с `mozglue`). WebKit работает без ограничений.

## Съёмка

Сервер портала должен работать локально (`http://127.0.0.1:8123`, автологин под Анной).

```bash
node <каталог shots>/shot.js W:/SOURCE/avg.mom/docs/wiki/img/shots W:/SOURCE/avg.mom/docs/wiki/img
```

Один файл или отдельные кадры:

```bash
node shot.js docs/wiki/img/shots/proposals.json docs/wiki/img --only=proposals-01,proposals-02
```

Переменные окружения: `BASE_URL` (по умолчанию `http://127.0.0.1:8123`),
`BROWSER` (`webkit` по умолчанию; `firefox`, `chromium`).

## Что делает скрипт перед кадром

- открывает `/ui-theme/metronic`, чтобы cookie темы указывала на Metronic
  (в новом контексте браузера иначе отрисуется старая тема);
- прячет панель debugbar, выключает анимации;
- выставляет светлую цветовую схему и состояние левого меню (`sidebar`);
- выполняет `actions`, ждёт шрифты и `settle` мс, обводит `highlight`, прячет `hide`.

Плейсхолдеры `{PROPOSAL}`, `{PARTNER}`, `{COMPANY}`, `{DEAL}`, `{KEY}` в `url`
подставляются перед съёмкой — см. `docs/wiki/img/shots/README.md`.

## Архив для передачи

`export/build.py` собирает zip для специалиста, который разворачивает вики: страницы `docs/wiki/`
(без `_GUIDE.md` и `_TODO.md`), использованные кадры, просмотрщик `index.html`, `export/_sidebar.md`
и инструкцию по переносу `export/DEPLOY.md`. Перед сборкой обновите в `DEPLOY.md` дату версии,
а если менялся состав страниц — таблицу страниц. Битые ссылки или кадры — архив не соберётся.

```bash
python patches/wiki-tools/export/build.py storage/app/wiki-export/osmo-wiki-2026-09-15.zip
```

Каталог `storage/app/` в git не попадает.
