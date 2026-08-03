<div class="col-md-12 col-lg-12 position-relative user-row">
    <div class="card text-center alert-dismissible fade show alert p-0 mb-0" role="alert">
        <div class="p-3 pt-2 pb-2 d-flex">
            <div><img src="{{ $person->avatar() }}" width="60" class="rounded-circle img-fluid"></div>
            <div class="ms-3 mt-1 align-content-start text-start ">
                @can('users_view_profile') <a href="{{ route('users.view', $person) }}" class="person_link"> @endcan
                    <h5 class="card-title mb-1">{{ $person->fullname }}</h5>
                    @can('users_view_profile') </a> @endcan

                <div class="d-flex justify-content-start mt-2 mb-3">
                    <x-ui.badge.light_rounded type="{{$color}}" text="{{$color}}">{{$badge}}</x-ui.badge.light_rounded>

                </div>

                <div>
                    @if($instance->canDoAgreementDecision())
                        <div class="d-flex">
                            <x-ui.button.default btn_type="danger flex-grow-1 me-1" id="order_back"
                                                 onclick="javascript:methodist_cancel()">
                                <x-ui.icon.regular icon="fa-ban" class="me-1"></x-ui.icon.regular>
                                Вернуть
                            </x-ui.button.default>

                            <x-ui.a.default btn_type="success flex-grow-1 ms-1" id="order_accept"
                                            onclick="javascript:methodist_accept()">
                                <x-ui.icon.regular icon="fa-circle-check" class="me-1"></x-ui.icon.regular>
                                Принять
                            </x-ui.a.default>
                        </div>
                    @elseif($instance->isRefused())
                        <x-ui.notification.light class="p-1 fs-1"
                                                 type="danger">{!! nl2br($instance->decline_message) !!}</x-ui.notification.light>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if($instance->canAcceptWork())
    <div id="modal-methodist-cancel" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Вернуть на доработку</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                </div>
                <div class="modal-body">
                    <form id="methodist_cancel">
                        <div class="row">
                            <div class="col-12">
                                <div class="mb-1">
                                    <label>
                                        Комментарий для автора приложения
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="comment" class="form-control" rows="5"></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Отменить
                    </button>
                    <button
                        type="button"
                        id="btn_methodst_cancel_confirm"
                        class="
                            btn btn-danger
                            font-weight-medium
                          "
                    >
                        ВЕРНУТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <div id="modal-methodist-accept" class="modal fade" tabindex="-1" aria-labelledby="danger-header-modalLab el"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-success text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Одобрить приложение</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Отменить"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-12">Вы уверены, что хотите согласовать это приложение?
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Отменить
                    </button>
                    <button
                        type="button"
                        id="btn_methodst_accept_confirm"
                        data-bs-dismiss="modal"
                        class="
                            btn btn-success
                            font-weight-medium
                          "
                    >
                        СОГЛАСОВАТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endif
