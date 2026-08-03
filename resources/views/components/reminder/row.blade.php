<li class="timeline-inverted timeline-item" id="{{ $remind->group }}">
        @if(!empty($remind->target_type) && !empty($link))
                <div class="timeline-badge bg-light text-secondary">
                    @if($sidebar)
                        <x-ui.a.sidebar href="{{ $link }}">
                            <x-ui.icon.light icon="{{ ($remind->target_type)::$module_icon }}"></x-ui.icon.light>
                        </x-ui.a.sidebar>
                    @else
                        <a href="{{ $link }}" target="_blank" title="">
                            <x-ui.icon.light icon="{{ ($remind->target_type)::$module_icon }}"></x-ui.icon.light>
                        </a>
                    @endif
                </div>
        @else
            <div class="timeline-badge bg-light text-secondary">
                 <x-ui.icon.light icon="fa-bell"></x-ui.icon.light>
            </div>
        @endif
    <div class="timeline-panel ps-0 pe-0">
        <div class="timeline-heading">
            <div class="container ps-3 pe-3">
                <div class="row">
                    <div class="col-12 col-sm-6 order-1 order-sm-0 mt-3 mt-sm-0">
                        <h4 class="timeline-title">
                            {{ $remind->title }}
                        </h4>
                    </div>
                    <div class="col-12 col-sm-6 order-0 order-sm-1">
                        <div class="d-flex flex-column align-items-sm-end align-items-start">
                            <div class="d-flex align-items-center">
                                @if(!empty($remind->target_type))
                                    @if($sidebar)
                                        <x-ui.a.sidebar href="{{ $link }}">
                                            <x-ui.badge.default type="info" class="fs-3">
                                                {{ ($remind->target_type)::$module_name }}
                                            </x-ui.badge.default>
                                        </x-ui.a.sidebar>
                                    @else
                                        <a href="{{ $link }}" target="_blank" title="">
                                            <x-ui.badge.default type="info" class="fs-3">
                                                {{ ($remind->target_type)::$module_name }}
                                            </x-ui.badge.default>
                                        </a>
                                    @endif


                                @else
                                    <x-ui.badge.light type="secondary" class="fs-3">
                                        Ручное напоминание
                                    </x-ui.badge.light>
                                @endif
                                <div class="dropdown dropstart">
                                    <x-ui.a.outline class="link" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <x-ui.icon.regular icon="fa-ellipsis-vertical"></x-ui.icon.regular>
                                    </x-ui.a.outline>
                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                        <li>
                                            @if(empty($filtered) && !empty($remind->target_type))
                                                <a class="dropdown-item" href="{{ route('reminder.filter', ['module' => $module, 'id' => $remind->target_id]) }}">
                                                    <x-ui.icon.regular icon="fa-filter" class="text-secondary me-2"></x-ui.icon.regular> Отфильтровать
                                                </a>
                                            @endif

                                            @if($remind->canEdit())
                                                <a class="dropdown-item" href="javascript:void(0);" onclick="javascript:sidebar({href: '{{ route('reminder.sidebar_edit', $remind) }}', method: 'GET'})">
                                                    <x-ui.icon.regular icon="fa-edit" class="text-secondary w-24px text-center"></x-ui.icon.regular> Редактировать
                                                </a>
                                            @endif

                                            @if($remind->canDelete())
                                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="javascript:remind_delete('{{$remind->group}}')">
                                                    <x-ui.icon.regular icon="fa-xmark" class="w-24px text-center"></x-ui.icon.regular> Удалить
                                                </a>
                                            @endif
                                        </li>
                                    </ul>
                                </div>
                            </div>
                            @if(empty($remind->creator))
                                <span class="text-muted fs-2 me-4 pe-2">Создано автоматически</span>
                            @else
                                <span class="text-muted fs-2 me-4 pe-2">Автор:
                            @if($remind->creator->id === auth()->id())
                                        ВЫ
                                    @else
                                        {{ $remind->creator->fullName }}
                                    @endif
                        </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-12 order-2">
                        <div class="timeline-body">
                            <p>
                                {!! nl2br($remind->message) !!}
                            </p>
                        </div>

                        <div class="users mt-3 fs-2">
                            @if(!$remind->hide)
                                @if($users->contains(auth()->id()))
                                    {{ $remind->trashed() ? 'Получили' : 'Получите' }}
                                    @if(count($users) == 1)
                                        <x-ui.badge.light type="primary" class="ps-1 pe-1">только ВЫ</x-ui.badge.light>
                                    @else
                                        <x-ui.badge.light type="primary" class="ps-1 pe-1">ВЫ</x-ui.badge.light>
                                        и
                                        <x-ui.badge.light type="secondary" class="ps-1 pe-1">{{ tools()->num_rus($users->count() - 1, ['человека', 'человек', 'людей'], true) }}</x-ui.badge.light>
                                    @endif
                                @else
                                    {{ tools()->num_rus($users->count(), $remind->trashed() ? ['Получило', 'Получил', 'Получили'] : ['Получат', 'Получит', 'Получат']) }}
                                    <x-ui.badge.light type="secondary" class="ps-1 pe-1">{{ tools()->num_rus($users->count(), ['человека', 'человек', 'людей'], true) }}</x-ui.badge.light>
                                @endif
                            @endif
                        </div>
                        <div class="times">
                            @foreach($remind->reminder_times as $time)
                                <div>
                                    @if($time->notified)
                                        <span class="fs-2 text-muted">
                            <x-ui.icon.solid icon="fa-circle-check"></x-ui.icon.solid> <span class="cursor-help" title="{{ _datetime($time->notify_at) }}">{{  _time_human($time->notify_at) }}</span>
                            через
                        </span>

                                        @foreach($time->notificators as $notificator)
                                            <x-ui.badge.light type="secondary" class="mb-0 fs-2 pb-1">{{ \Str::lower(($notificator)::$info['name']) }}</x-ui.badge.light>
                                        @endforeach
                                    @else
                                        <span class="fs-2 text-primary">
                            <x-ui.icon.regular icon="fa-clock"></x-ui.icon.regular> <span class="cursor-help" title="{{ _datetime($time->notify_at) }}">{{  _time_human($time->notify_at) }}</span>
                            через
                        </span>

                                        @foreach($time->notificators as $notificator)
                                            <x-ui.badge.light type="primary" class="mb-0 fs-2 pb-1">{{ \Str::lower(($notificator)::$info['name']) }}</x-ui.badge.light>
                                        @endforeach
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</li>
