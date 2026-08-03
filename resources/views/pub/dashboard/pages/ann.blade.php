@extends('pub.dashboard.layout')

@section('styles')
@endsection

@section('dashboard_content')
    <div>
        <h3>Графики</h3>
        @php
            $arSources = config('graph.sources');
        @endphp
        @foreach($arSources as $i => $source)
            <div>
                <a href="/graph?n={{ $i }}">График {{ $i }}</a>
            </div>
        @endforeach
    </div>
@endsection

@section('js')
    @parent
@endsection
