@extends('layouts.layout')


@section('styles')
@endsection


@section('content')
    <div class="container-fluid">
        <form method="POST" action="{{ route('constants.update') }}" id="constant">
            @csrf
            <div class="card">
                <div class="card-body">
                        @foreach($consts as $const)
                            <div class="row mb-3">
                                <div class="col-6 text-end">
                                    <div>{{ $const->name }}</div>
                                    <div>
                                        <x-ui.badge.light type="secondary">{{ $const->key }}</x-ui.badge.light>
                                    </div>
                                </div>
                                <div class="col-6 align-items-center">
                                    <div class="mt-1">
                                        <input type="text" class="form-control" name="const[{{ $const->id }}]" value="{{ $const->value }}">
                                    </div>
                                </div>
                            </div>
                        @endforeach

                    <div class="row mt-3">
                        <div class="col-6"></div>
                        <div class="col-6">
                            <x-ui.button.light btn_type="info" onclick="javascript:$('form#constant').submit()">Сохранить</x-ui.button.light>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

@endsection

@section('js')
@endsection
