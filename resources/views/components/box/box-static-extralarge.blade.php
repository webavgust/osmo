<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content">
            @if(empty($ignore_title))
                <div class="modal-header d-flex align-items-center">
                    <h4 class="modal-title" id="myLargeModalLabel">
                        @hasSection('title')
                            @yield('title')
                        @else
                            {!! $title !!}
                        @endif
                    </h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                </div>
            @endif
            <div class="modal-body">
                @yield('body')
            </div>
            <div class="modal-footer">
                @hasSection('footer')
                    @yield('footer')
                @else
                    <button type="button" class="
                                btn btn-light-danger
                                text-danger
                                font-weight-medium
                                waves-effect
                                text-start
                              " data-bs-dismiss="modal">
                        Закрыть
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>


@hasSection('modal')
    @yield('modal')
@endif
