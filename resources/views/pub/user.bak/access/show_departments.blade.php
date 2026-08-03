@extends('components.sidebar.offcanvas-right')

@section('body')
    <div class="card">
        <div class="card-body">
            @foreach($rows as $row)
                <a href="{{ route('access_set.department', $row) }}" type="button" class="
                          btn
                          d-flex
                          align-items-center
                          btn-light-danger
                          w-100
                          d-block
                          text-danger
                          font-weight-medium
                          mb-1
                        ">
                    {{ $row->name }}
                </a>

            @endforeach
        </div>
    </div>
@endsection
