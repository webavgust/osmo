@if(Auth::user()->sub_users->count() > 0)
    <div class="d-flex flex-column flex-grow-1 flex-lg-row flex-lg-grow-1 justify-content-end">
        <x-ui.button.sidebar_light btn_type="secondary" href="{{ route('dashboard.sidebar_sub_user_select') }}"  class="mt-2 mt-md-0 me-lg-3 me-0 mb-1 mb-lg-0">
            <strong>{{ $sub_user->id == Auth::id() ? 'Свой профиль' : $sub_user->full_name }}</strong>
        </x-ui.button.sidebar_light>
    </div>
@endif

@section('js')
    @parent
@endsection
