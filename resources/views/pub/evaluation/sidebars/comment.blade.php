@extends('components.sidebar.offcanvas-right')

@section('body')
    {!! nl2br($evaluation->comment) !!}
@endsection
