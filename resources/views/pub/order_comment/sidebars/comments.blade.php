@extends('components.sidebar.offcanvas-right')


@section('body')
    <x-order.comments :comments="$comments" mode="sidebar"></x-order.comments>
@endsection
