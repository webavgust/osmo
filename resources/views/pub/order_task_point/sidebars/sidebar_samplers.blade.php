@extends('components.sidebar.offcanvas-right')


@section('body')
    <div class="card">
        <div class="card-body">
            <ul class="list-style-none">
                @php
                    $iterator = 1;
                @endphp
                @foreach($users as $user)

                    <li>
                        <strong class="me-2 d-inline-block" style="width: 20px;">{{ $iterator++ }}.</strong>
                        {{ $user->full_name }}
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
@endsection
