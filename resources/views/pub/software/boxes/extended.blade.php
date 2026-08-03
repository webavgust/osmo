@extends('components.box.box-static-backdrop')


@section('body')
    <style>
        #extended_preview ul, #extended_preview p:last-of-type {
            margin: 0;
        }
    </style>

    <div class="card">
        <h5>Описание</h5>
        <div class="card-body ps-3 py-0 border-start border-5 border-light-secondary" id="extended_preview">
            {!! $software->description !!}
        </div>

        @if(!empty($software->notice))
            <h5 class="mt-4">Примечание</h5>
            <div class="card-body ps-3 py-0 border-start border-5 border-light-secondary" id="extended_preview">
                {!! $software->description !!}
            </div>
        @endif
    </div>
@endsection
