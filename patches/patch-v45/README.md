# patch v45 — мягкое удаление КП (Soft Delete)

Ставится поверх v44. Требование владельца: удалять КП может только Анна Августиновская; только она
в фильтре списка видит удалённые КП и возвращает их.

## Решения

- **Кто может** — `config/proposal.php` → `deleters => [1]` (users.id). Одна точка правды —
  `Proposal::canDelete(?$user = null)`. Проверка на сервере (403) и в UI: у остальных нет ни пункта
  «Удалить», ни галочки «Показать удалённые»; параметр `trashed` фильтра для них отбрасывается.
- **Удаляется КП целиком** — все редакции группы одной операцией; восстановление — тоже всей группой.
  Отдельные редакции больше не удаляются (раньше `api.proposal.delete` жёстко удалял одну редакцию).
- **Связи не трогаются**: сделки Битрикс24 (`proposal_crm_deals`), связки КП, спецификации, договоры,
  варианты/ПО/работы остаются как были — после восстановления всё на месте.
- `deleted_at` ставится запросом без событий моделей и **без смены `updated_at`** — дата изменения КП
  не «прыгает» после удаления и восстановления.
- **Связка «главное / второстепенное» (v33)**: второстепенное и главное со второстепенными не удаляются —
  403 с текстом «Сначала разъедините их — попап «Связка КП»».

## Что поменялось

- **Миграция** `2026_10_09_120000_add_deleted_at_to_proposals_table` — `proposals.deleted_at` + индекс.
- **Модель `Proposal`**: `SoftDeletes` — удалённые КП пропадают везде, где запрос идёт через модель
  (списки, реестр сделок, виджеты, отчёты, поиск КП для привязки к сделке и связки). `canDelete()`.
  Слушатель `deleting` (каскад вариантов/ПО/работ) срабатывает только при `forceDelete()`.
- **`ProposalRepository::delete()`** — мягкое удаление группы + проверки права и связки;
  **`restore(string $group)`** — восстановление группы.
- **API**: `DELETE /api/proposal/delete/{group}/{iteration}` — мягко удаляет всю группу;
  новый `POST /api/proposal/restore/{group}` (`api.proposal.restore`), ищет только среди удалённых.
- **`ProposalController::destroy`** — маршрута нет (заготовка), приведён к той же логике.
- **Номер нового КП** (`max_number` в create/edit) считается с учётом удалённых — у восстановленного
  КП номер не совпадёт с новым.
- **Список КП** (`pub/proposal/index` в теме): в фильтре галочка «Показать удалённые» (только у Анны) —
  в списке остаются только удалённые; в ячейке статуса плашка «Удалено dd.mm.yyyy»; в меню строки
  вместо «Редактировать / Удалить» — «Восстановить». Подтверждение удаления: «Удалить КП со всеми
  редакциями? Его можно будет вернуть из фильтра «Удалённые»». Ошибки сервера (связка КП) — в toastr.
  Ячейки списка рисует API без темы, поэтому логика меню — в обоих `components/proposal/table/main/actions`.
- **Карточка КП**: удалённое открывается только Анне (маршрут `proposal.detail` с `withTrashed()`),
  с плашкой «КП удалено» и кнопкой «Восстановить»; остальным — 404. Редактирование удалённого — 404 всем.
- **Журнал изменений**: удаление — событие «Удаление» у последней редакции; восстановление — новое
  событие «Восстановление» (`EntityLog::EVENT_RESTORED`, зелёная иконка в ленте).
- **Сырые запросы мимо модели** — добавлено `deleted_at IS NULL`: `DiscountAnalysisService::years()`,
  `PartnerScoringService` (выборка КП скоринга), `PriceHistoryWidget::group()`,
  `ScenariosTopWidget::proposalsQuery()`, поиск по КП в `PaymentCalendarService`.
  Остальные места (Bitrix-сервисы, `CrmMismatchService`, `DealChainService`, `DealProjectService`,
  `MetricRegistry`, реестр сделок) берут КП через модель — фильтр работает сам.

## Файлы

- `config/proposal.php` (новый)
- `database/migrations/2026_10_09_120000_add_deleted_at_to_proposals_table.php` (новый)
- `app/Modules/Pub/Proposal/Models/Proposal.php`
- `app/Modules/Pub/Proposal/Repositories/ProposalRepository.php`
- `app/Modules/Pub/Proposal/Controllers/Api/ApiProposalController.php`
- `app/Modules/Pub/Proposal/Controllers/ProposalController.php`
- `app/Modules/Pub/Proposal/Routes/api.php`, `app/Modules/Pub/Proposal/Routes/web.php`
- `app/Modules/Pub/Proposal/Services/ProposalListFilterService.php`
- `app/Modules/Pub/Proposal/Requests/ListFilterRequest.php`
- `app/Modules/Pub/EntityLog/Models/EntityLog.php`, `app/Modules/Pub/EntityLog/Services/EntityLogService.php`
- `app/Modules/Pub/Analytics/Services/DiscountAnalysisService.php`, `PartnerScoringService.php`
- `app/Modules/Pub/Desktop/Widgets/Proposal/PriceHistoryWidget.php`
- `app/Modules/Pub/Desktop/Widgets/Analytics/ScenariosTopWidget.php`
- `app/Modules/Pub/PaymentCalendar/Services/PaymentCalendarService.php`
- `resources/views/components/proposal/table/main/actions.blade.php`, `status.blade.php`
- `resources/views/themes/metronic/components/proposal/table/main/actions.blade.php`
- `resources/views/themes/metronic/pub/proposal/index.blade.php`, `detail.blade.php`
- `resources/views/themes/metronic/pub/entity_log/index.blade.php`

`config/modular.php` не меняется — новых модулей нет.

## Руками на проде

1. Полная копия БД в `/root/backup` с меткой времени в имени.
2. `git pull`
3. `php artisan migrate --force` (добавляет одну колонку и индекс, данные не меняет).
4. `php artisan optimize:clear` (новый конфиг `config/proposal.php`, маршруты).

## Чек-лист

- [ ] Под Анной список КП открывается; в меню строки есть «Удалить», в фильтре — «Показать удалённые».
- [ ] Удаление КП (с несколькими редакциями и сделкой): toastr «КП удалено», строка пропала из списка,
      из реестра сделок `/bitrix/deal`, из карточки сделки, из виджетов.
- [ ] Галочка «Показать удалённые»: в списке только удалённые, плашка «Удалено dd.mm.yyyy», в меню —
      «Восстановить». Карточка удалённого открывается с плашкой «КП удалено».
- [ ] «Восстановить»: toastr «КП восстановлено», КП снова в списке и реестре со всеми редакциями и
      сделками; дата изменения прежняя. В журнале КП — «Удаление» и «Восстановление».
- [ ] Главное со второстепенным и само второстепенное не удаляются — toastr с текстом про «Связку КП».
- [ ] Под другим пользователем: нет «Удалить» и галочки; `DELETE /api/proposal/delete/...` и
      `POST /api/proposal/restore/...` — 403; карточка удалённого КП — 404.
- [ ] Новое КП получает номер больше, чем у удалённых.
