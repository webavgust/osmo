@extends('components.box.box-static-large')

@section('title')
    <div>
        <h3 class="m-0">{{ $log->company->name }}</h3>

        @if(!empty($log->proposal))
            <div class="fs-3 mt-1 text-info">
                <a href="{{ route('proposal.detail', [$log->proposal, $log->proposal->iteration]) }}">{{ $log->proposal->name }}</a>
            </div>
        @endif
    </div>
@endsection


@section('body')

        <div class="fs-2 text-secondary px-1 fw-bold">
            {{ $log->created_at->isToday() ? $log->created_at->format("H:i") : $log->created_at->format("d.m.Y") }}
        </div>
        <div class="p-3 border border-light-secondary border-1 rounded-3">

            <div>
                {!! nl2br($log->text) !!}
            </div>
        </div>
@endsection


