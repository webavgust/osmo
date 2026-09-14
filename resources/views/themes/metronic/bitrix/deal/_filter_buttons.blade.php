{{--
    Кнопки фильтра реестра сделок Битрикса: «Фильтр (n)» и «Убрать».

    Вынесены из _filter, чтобы на странице реестра стоять в тулбаре страницы
    (секция breadcrumb_right), а во вкладке «Сделки Битрикс» карточки
    партнёра — по-прежнему в панели таблицы (_filter рисует их сам).
    Переменные @include родительскому шаблону не видны, поэтому счётчик
    правил считается здесь же.

    Параметры — те же, что у _filter:
      $params   — текущий отбор (CrmDealRegistryService::params());
      $defaults — значения по умолчанию вкладки (поверх DEFAULTS);
      $action   — адрес страницы (по умолчанию реестр);
      $prefix   — префикс id, тот же, что у модалки фильтра;
      $mode     — вкладка реестра: all | projects | archive.
--}}
@php
    $action = $action ?? route('crm-deal.index');
    $prefix = $prefix ?? 'deal';
    $mode = $mode ?? null;
    $service = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::class;

    // значения по умолчанию: на вкладках проектов они свои (patch v24)
    $defaults = array_merge($service::DEFAULTS, $defaults ?? []);

    // «Фильтр (n)»: правила модалки (наличие КП считается, когда отличается
    // от значения по умолчанию) и строка поиска
    $rules_count = collect(['stage', 'manager', 'country', 'customer'])
            ->filter(fn($key) => !empty($params[$key]))
            ->count()
        + ($params['q'] !== '' ? 1 : 0)
        + ($params['has_proposal'] !== $defaults['has_proposal'] ? 1 : 0);
@endphp

<button type="button" class="btn btn-light-info"
        data-bs-toggle="modal" data-bs-target="#{{ $prefix }}_filter_modal">
    <i class="fa-light fa-filter fs-5 me-2"></i>
    Фильтр <span class="count filter-count @unless($rules_count) d-none @endunless">{{ $rules_count }}</span>
</button>

<a href="{{ $action }}{{ $mode ? '?mode=' . $mode : '' }}"
   class="@unless($service::filtered($params, $defaults)) d-none @endunless me-2 text-dark-500 text-hover-dark"
   id="{{ $prefix }}_filter_clear">
    <i class="fa-light fa-xmark fs-5 me-2" aria-hidden="true"></i> Убрать
</a>
