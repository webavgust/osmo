{{--
    Вкладка «Сделки Битрикс» карточки партнёра (patch v23).

    Тот же фильтр и та же таблица, что и на странице реестра (patch v22),
    только выборка ограничена компаниями Битрикса этого партнёра. Кусок
    приезжает ajax'ом (PartnerController::deals) и им же заменяется после
    смены фильтра или поиска, поэтому макета страницы здесь нет.

    Параметры — как у _filter и _table, плюс:
      $partner — партнёр, чьи сделки показываем;
      $ajax    — фильтр и поиск не перезагружают страницу, а перерисовывают вкладку;
      $mode    — режим вкладки: all | projects | archive (patch v24).
--}}
@php
    $service = \App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService::class;
    $linked = $service::partnerCompanyIds($partner);
    $mode = $mode ?? $service::MODE_ALL;

    // пустая вкладка проектов объясняется словами: фильтр тут ни при чём
    $empty_project_tab = $rows->isEmpty()
        && $mode !== $service::MODE_ALL
        && !$service::filtered($params, $service::modeDefaults($mode));
@endphp

@if(empty($linked))
    {{-- без сопоставления сделок у партнёра просто нет: объясняем, а не показываем пустую таблицу --}}
    <div class="card">
        <div class="card-body text-center py-15">
            <i class="fa-light fa-link-slash fs-2x text-muted mb-4 d-block"></i>
            <div class="fs-5 fw-semibold text-gray-800 mb-2">Партнёр не сопоставлен с Битрикс24</div>
            <div class="text-muted mb-5">
                Чтобы увидеть сделки, укажите компании Битрикс24 этого партнёра
                в <a href="{{ route('partner.edit', $partner) }}">редактировании</a>.
            </div>
        </div>
    </div>
@elseif($empty_project_tab)
    <div class="card">
        <div class="card-body text-center py-15">
            <i class="fa-light fa-diagram-project fs-2x text-muted mb-4 d-block"></i>
            @if($mode === $service::MODE_ARCHIVE)
                <div class="fs-5 fw-semibold text-gray-800 mb-2">В архиве пусто</div>
                <div class="text-muted">
                    Сюда попадают проекты, отправленные в архив из карточки проекта.
                </div>
            @else
                <div class="fs-5 fw-semibold text-gray-800 mb-2">Проектов пока нет</div>
                <div class="text-muted">
                    Проект заводится от сделки — на вкладке «Сделки Битрикс24»,
                    кнопкой «проект» в колонке «Проект».
                </div>
            @endif
        </div>
    </div>
@else
    <div class="card">
        <div class="card-header pt-4 min-h-auto">
            <div class="card-toolbar m-0">
                @include('bitrix.deal._filter')
            </div>
        </div>

        <div class="card-body pt-2">
            @include('bitrix.deal._table')
        </div>
    </div>
@endif
