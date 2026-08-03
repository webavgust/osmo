@extends('components.sidebar.offcanvas-right')

@section('body')
    <div class="card">
        <div class="card-body">
            @foreach($rows as $row)
                <a href="{{ route('user_department.detail', $row) }}" type="button" class="
                          btn
                          d-flex
                          align-items-center
                          btn-light-primary
                          w-100
                          d-block
                          text-primary
                          font-weight-medium mb-1
                        ">
                    {{ $row->name }}
                </a>
            @endforeach
        </div>
    </div>
@endsection
