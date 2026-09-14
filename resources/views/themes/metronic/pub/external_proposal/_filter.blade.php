{{--
    Фильтр КП OSMOVIEW CP (patch v27).

    Модалка фильтра. Кнопки «Фильтр» (со счётчиком правил) и «Убрать» стоят в
    тулбаре страницы — секция breadcrumb_right в index.blade.php. Форма
    обычная GET — отбор виден в адресе, переживает F5 и отправляется ссылкой.

    Валюта и тип лицензий отдельных колонок в базе не имеют (лежат в payload),
    поэтому по ним фильтруется уже собранная коллекция — см.
    ExternalProposalService::rows().

    Параметры:
      $params                — текущий отбор (ExternalProposalService::params());
      $currencies, $licenses, $transferred_list — значения списков (::options()).
--}}
<div id="external_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <form method="get" action="{{ route('external_proposal.index') }}" id="external_filter_form">
        {{-- строка поиска живёт в панели таблицы, но уезжает вместе с фильтром --}}
        <input type="hidden" name="q" value="{{ $params['q'] }}"/>

        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h3 class="modal-title fw-bold">Фильтр</h3>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary"
                            data-bs-dismiss="modal" aria-label="Закрыть">
                        <i class="fa-light fa-xmark fs-2"></i>
                    </button>
                </div>

                <div class="modal-body py-8">
                    <div class="fs-6 fw-bold text-gray-800 mb-6">По полям КП источника</div>

                    {{-- Валюта: отдельного справочника нет, список собран из
                         самих записей (у кого загружен detail) --}}
                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Валюта</label>
                        <div class="col-sm-9">
                            <x-ui.select.single name="currency" id="id"
                                                class="external_filter_select"
                                                :items="$currencies" :value="$params['currency']"
                                                blank-name="не важно"/>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Тип лицензии</label>
                        <div class="col-sm-9">
                            <x-ui.select.single name="license" id="id"
                                                class="external_filter_select"
                                                :items="$licenses" :value="$params['license']"
                                                blank-name="не важно"/>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Перенесено</label>
                        <div class="col-sm-9">
                            <x-ui.select.single name="transferred" id="id"
                                                class="external_filter_select"
                                                :items="$transferred_list" :value="$params['transferred']"
                                                blank-ignore="1"/>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="row">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Что показываем</label>
                        <div class="col-sm-9">
                            <div class="form-text mt-3">
                                КП из OSMOVIEW CP: по умолчанию все, и перенесённые в наши КП тоже.
                                Валюта и тип лицензии известны только у записей с загруженным detail —
                                остальные под такой отбор не попадают.
                                Строка поиска справа ищет по названию, заказчику и номеру.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Отменить</button>
                    <button type="submit" class="btn btn-primary">Применить</button>
                </div>
            </div>
        </div>
    </form>
</div>
