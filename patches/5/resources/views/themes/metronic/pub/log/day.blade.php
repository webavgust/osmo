@extends('layouts.layout')

@section('styles')
    <style>
        #remind_add {
            transition: all .5s;
            cursor: pointer;
        }
        #remind_add:hover {
            box-shadow: 0px 10px 6px 0px rgba(0, 0, 0, 0.1);
            transform: translateY(-5px);
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="timeline timeline-left">
                            <li class="timeline-inverted timeline-item pb-5 mb-5">
                                <a class="timeline-badge primary" id="remind_add" href="{{ route('log.index', _date($pointer)) }}">
                                    <x-ui.icon.light icon="fa-plus" class="text-white"></x-ui.icon.light>
                                </a>
                            </li>
                            @foreach($logs as $log)
                                <li class="timeline-inverted timeline-item">
                                    <div class="timeline-badge bg-light text-secondary fw-bold">
                                        {{ count($logs) - $loop->iteration + 1 }}
                                    </div>

                                    <div class="timeline-panel ps-0 pe-0">
                                        <div class="timeline-heading py-0 px-3 border-bottom pb-2">
                                            <div class="d-flex justify-content-between">
                                                <h4>{{ $log->company->name }}</h4>

                                                <span>{{ $log->created_at->isToday() ? $log->created_at->format("H:i") : $log->created_at->format("d.m.Y") }}</span>
                                            </div>

                                            @if(!empty($log->proposal))
                                                <div class="fs-3 mt-1 text-info">
                                                    <a href="{{ route('proposal.detail', [$log->proposal, $log->proposal->iteration]) }}">{{ $log->proposal->name }}</a>
                                                </div>
                                            @endif
                                        </div>

                                        <div class="p-3 inner">
                                            {!! nl2br($log->text) !!}
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
