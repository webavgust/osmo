@extends('components.sidebar.offcanvas-right')


@section('body')
    <form method="post" id="calendar_add">
        <div class="card">
            <div class="card-body p-0">
                <h4>Просмотр сценариев для нейросервиса "{{ $neuroservice->name }}"</h4>

                <div>

                </div>
            </div>
        </div>
    </form>
@endsection
