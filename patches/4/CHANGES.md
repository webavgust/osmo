# Патч v4 — только изменённые файлы

Накатывается поверх v3: скопируйте `resources` и `public` в корень проекта с заменой, затем

    php artisan view:clear

## Изменённые файлы (4)

| Файл | Что изменено |
|---|---|
| `resources/views/themes/metronic/layouts/sidebar.blade.php` | логотип по вашей разметке; панель иконок убрана — всё в подменю профиля |
| `resources/views/themes/metronic/components/sidebar/menu-item.blade.php` | подзаголовки без иконки |
| `resources/views/themes/metronic/components/ui/theme_switch.blade.php` | «Оформление» стало пунктом подменю профиля |
| `public/metronic/css/osmo-sidebar.css` | margin-top меню, единые отступы toolbar/content, вид подзаголовков |

## По пунктам

1. **Логотип** — разметка как в вашем сниппете: `h-100 d-flex align-items-center` на ссылке,
   `h-75` / `h-50` на картинках.
2. **`#kt_app_sidebar`** — `margin-top: 0 !important`.
3. **`#kt_app_toolbar`** — горизонтальные отступы взяты из общей переменной
   `--osmo-content-pad-x`, той же, что у `#kt_app_content` и `#kt_app_footer`:
   заголовок и контент выровнены по одной линии. На мобильных переменная = 1rem.
4. **`#kt_app_content`** — `margin-top: 20px`, свой `padding-top` убран, чтобы отступ
   был ровно 20px.
5. **Иконки → в профиль.** Полоса иконок удалена. В подменю профиля теперь:
   профиль, напоминания, календарь, уведомления (список грузится в `.notices_shell`
   как раньше), история уведомлений, цветовая схема, оформление, обновить доступы
   (для админов), выход. Индикатор новых уведомлений переехал на аватар —
   `spider_tick` продолжает работать: `#notifies` и `.notify .heartbit` на месте.
6. **Подзаголовки разделов** — три точки убраны, иконки нет, прижаты к левому краю
   (`padding-left: 0`), крупнее (15px вместо 13px) и темнее — цвет основного текста
   вместо серого.

## Подстройка

    --osmo-sidebar-font-heading: .9375rem;   /* размер подзаголовков */
    --osmo-content-pad-x: 0px;               /* отступы toolbar + content */
    --osmo-sidebar-gap: 16px;                /* отступ вокруг меню */
