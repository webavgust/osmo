<div class="modal fade show" id="bs-example-modal-lg" tabindex="-1" aria-labelledby="bs-example-modal-lg" style="display: block;" aria-modal="true" role="dialog">
    <div class="modal-dialog  modal-lg">
        <div class="modal-content">
            <div class="modal-header d-flex align-items-center">
                <h4 class="modal-title" id="myLargeModalLabel">
                    @hasSection('title')
                        @yield('title')
                    @else
                        {!! $title ?? '?' !!}
                    @endif
                </h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
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
