<div class="modal fade" id="staticBackdrop" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            @if(empty($ignore_title))
                <div class="modal-header py-4">
                    <h4 class="modal-title fw-bold mb-0" id="myLargeModalLabel">
                        @hasSection('title')
                            @yield('title')
                        @else
                            {!! $title ?? '?' !!}
                        @endif
                    </h4>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-primary ms-2" data-bs-dismiss="modal" aria-label="Закрыть">
                        <i class="fa-light fa-xmark fs-3"></i>
                    </button>
                </div>
            @endif
            <div class="modal-body">
                @yield('body')
            </div>
            <div class="modal-footer">
                @hasSection('footer')
                    @yield('footer')
                @else
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
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
