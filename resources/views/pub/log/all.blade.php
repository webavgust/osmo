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

        .inner p:last-of-type {
            margin-bottom: 0;
        }
        .timeline > .timeline-item {
            margin-bottom: 5px;
        }
        .timeline-left > .timeline-item > .timeline-badge {
            font-size: 15px;
            width: 30px;
            height: 30px;
            left: 40px;
            line-height: 32px;
            z-index: 1;
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="timeline timeline-left pt-0">
                            <li class="timeline-inverted timeline-item pb-5">
                                <a class="timeline-badge primary" id="remind_add"  href="{{ route('log.index', _date(now())) }}">
                                    <x-ui.icon.light icon="fa-plus" class="text-white"></x-ui.icon.light>
                                </a>
                            </li>
                            @foreach($logs as $date => $rows)
                                @php
                                    if(empty($i)) $i = 0;
                                @endphp
                                <li class="timeline-inverted timeline-item date" companies=";{{ $rows->pluck('company_id')->unique()->join(';') }};">
                                    <div class="badge bg-success d-flex align-items-center bg-light-info w-100 d-block text-info font-weight-medium p-1 ps-2 ">
                                        <div class="fs-2 fw-bold">{{ _date($date) }}</div>
                                        <span class="badge ms-auto bg-info fs-2">{{ $rows->count() }}</span>
                                    </div>
                                </li>


                                @foreach($rows as $log)
                                    @php
                                        $i++;
                                    @endphp
                                    <li class="timeline-inverted timeline-item" company="{{ $log->company_id }}">

                                        <div class="timeline-badge bg-light text-secondary fw-bold">
                                            {{ $i }}
                                        </div>

                                        <div class="timeline-panel p-1">
                                            <div class="timeline-heading border-bottom p-1">
                                                <div class="d-flex justify-content-between fs-2 ">
                                                    <span class="fw-bold" style="color: darkred">{{ $log->company->name }}</span>
                                                    @if(!empty($log->proposal))
                                                        <div class="text-info">
                                                            <a href="{{ route('proposal.detail', [$log->proposal, $log->proposal->iteration]) }}">{{ $log->proposal->name }}</a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>

                                            <div class="p-1 fs-3 inner">
                                                {!! nl2br($log->text) !!}
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('breadcrumb_right')
    @if($companies->isNotEmpty())
        <div class="d-flex align-items-center">
            <span class="fw-bold me-1">Компания:</span>
            <x-ui.select.single :items="$companies" id="id" value="name" class="company_id"></x-ui.select.single>
        </div>
    @endif
@endsection
@section('js')
    @parent
    <script>
        function company_filter(id) {
            $("li.timeline-item").addClass("d-none");
            if(!id) {
                $("li.timeline-item").removeClass("d-none");
            } else {
                $("li.timeline-item.date[companies*=';" + id + ";']").removeClass("d-none");
                $("li.timeline-item[company='" + id + "']").removeClass("d-none");
            }
        }
        $(document).ready(function() {
            $(".company_id").select2({

            }).on("change", function() {
                company_filter($(this).val());
            });
        });
    </script>
@endsection
