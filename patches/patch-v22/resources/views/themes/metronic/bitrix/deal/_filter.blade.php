{{--
    Фильтр реестра сделок Битрикса (patch v22, вёрстка v22.1 — как в списке КП).

    Повторяет приёмы списка КП (pub/proposal/index.blade.php): кнопка «Фильтр»
    со счётчиком правил, кнопка «Убрать» и модальное окно с полями. Отличие
    одно: у КП фильтр живёт в сессии и уезжает ajax'ом, здесь — обычная
    GET-форма, поэтому отбор виден в адресе и его можно отправить ссылкой.

    Вкладок над таблицей нет: место оставлено под «Проекты» и «Архив проектов»
    (итерация 4), а наличие КП — обычное поле фильтра.

    Блок самодостаточный: включается одним @include и сам поднимает select2,
    поэтому его же можно вставить во вкладку «Сделки Битрикс» на карточке
    партнёра (patch v23), в том числе ajax'ом.

    Параметры:
      $params            — текущий отбор (CrmDealRegistryService::params());
      $stages, $managers, $countries, $has_proposal_list — значения списков;
      $action            — куда отправлять форму (по умолчанию страница реестра);
      $partner           — область партнёра, если фильтр живёт во вкладке;
      $prefix            — префикс id, если на странице два таких блока.
--}}
@php
    $action = $action ?? route('crm-deal.index');
    $partner = $partner ?? null;
    $prefix = $prefix ?? 'deal';
    $service = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::class;

    // «Фильтр (n)»: правила модалки (наличие КП считается, когда отличается
    // от значения по умолчанию) и строка поиска
    $rules_count = collect(['stage', 'manager', 'country'])
            ->filter(fn($key) => !empty($params[$key]))
            ->count()
        + ($params['q'] !== '' ? 1 : 0)
        + ($params['has_proposal'] !== $service::DEFAULTS['has_proposal'] ? 1 : 0);

@endphp


{{-- Тулбар таблицы: bootstrap-table сам перенесёт его в свою панель --}}
<div id="{{ $prefix }}_toolbar" class="d-flex flex-wrap gap-2">
    <button type="button" class="btn btn-light-primary"
            data-bs-toggle="modal" data-bs-target="#{{ $prefix }}_filter_modal">
        <i class="fa-light fa-filter fs-5 me-2"></i>
        Фильтр <span class="count @unless($rules_count) d-none @endunless">({{ $rules_count }})</span>
    </button>

    <a href="{{ $action }}" class="btn btn-light-danger @unless($service::filtered($params)) d-none @endunless"
       id="{{ $prefix }}_filter_clear" data-bs-toggle="tooltip" title="Сбросить фильтр">
        <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
    </a>
</div>

<div id="{{ $prefix }}_filter_modal" class="modal fade" tabindex="-1" aria-hidden="true">
    <form method="get" action="{{ $action }}" id="{{ $prefix }}_filter_form">
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
                    <div class="fs-6 fw-bold text-gray-800 mb-6">По полям сделки</div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Стадия</label>
                        <div class="col-sm-9">
                            <x-ui.select.multiple name="stage[]" id="id"
                                                  class="{{ $prefix }}_select"
                                                  data-placeholder="любая"
                                                  :items="$stages" :selected="$params['stage']" blank-ignore="1"/>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Менеджер</label>
                        <div class="col-sm-9">
                            <x-ui.select.multiple name="manager[]" id="id"
                                                  class="{{ $prefix }}_select"
                                                  data-placeholder="любой"
                                                  :items="$managers" :selected="$params['manager']" blank-ignore="1"/>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Привязано КП</label>
                        <div class="col-sm-9">
                            <x-ui.select.single name="has_proposal" id="id"
                                                class="{{ $prefix }}_select"
                                                :items="$has_proposal_list" :value="$params['has_proposal']"
                                                blank-ignore="1"/>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Страна</label>
                        <div class="col-sm-9">
                            <x-ui.select.multiple name="country[]" id="id"
                                                  class="{{ $prefix }}_select"
                                                  data-placeholder="любая"
                                                  :items="$countries" :selected="$params['country']" blank-ignore="1"/>
                        </div>
                    </div>

                    <div class="separator separator-dashed my-8"></div>

                    <div class="row">
                        <label class="col-sm-3 col-form-label fw-semibold text-sm-end">Что показываем</label>
                        <div class="col-sm-9">
                            <div class="form-text mt-3">
                                Сделки с
                                {{ \Carbon\Carbon::parse($service::SINCE)->format('d.m.Y') }};
                                по умолчанию — те, к которым ещё не привязано КП.
                                Строка поиска справа ищет по названию, партнёру, заказчику и ID.
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

<script>
    // Блок приезжает и вместе со страницей, и ajax'ом во вкладку партнёра,
    // поэтому select2 поднимаем сами и ровно один раз
    (function () {
        var tries = 0;

        var init = function () {
            if (typeof jQuery === 'undefined' || !jQuery.fn.select2) {
                return (++tries < 50) ? setTimeout(init, 100) : null;
            }

            jQuery(function ($) {
                var $modal = $('#{{ $prefix }}_filter_modal');
                if (!$modal.length) return;

                // модалку уводим в body: внутри карточки её мог бы обрезать
                // контекст наложения, а select2 нужен стабильный родитель
                if (!$modal.parent().is('body')) $modal.appendTo('body');

                $modal.find('select.{{ $prefix }}_select').each(function () {
                    if ($(this).data('select2')) return;

                    $(this).select2({
                        width: '100%',
                        dropdownParent: $modal,
                        placeholder: $(this).data('placeholder') || 'не важно',
                        allowClear: true
                    });
                });
            });
        };

        init();
    })();
</script>
