@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body border-bottom">
                        <h4 class="card-title">Создание нейросервиса в группе "{{ $group->name }}"</h4>
                        <h6 class="card-subtitle mb-0">
                            Здесь описание
                        </h6>
                    </div>
                    <form action="{{ route('neuroservice.store', $group) }}" method="POST"
                          class="needs-validation novalidate">
                        @csrf
                        <div class="card-body">
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Название<span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input name="name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" required="" value="{{ old('name') }}">

                                    @error('name')
                                    <div
                                        class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Техническое название</label>
                                <div class="col-sm-4">
                                    <input name="tech_name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" required="" value="{{ old('tech_name') }}">

                                    @error('tech_name')
                                    <div
                                        class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 text-end control-label col-form-label">Стоимость<span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <div class="input-group">
{{--                                        <span class="input-group-text">мес:</span>--}}
{{--                                        <input type="number" min="0" class="form-control" name="cost[month]" value="0">--}}
                                        <span class="input-group-text">год:</span>
                                        <input type="number" min="0" class="form-control" name="cost[year]" value="0">
                                        <span class="input-group-text">бессрок:</span>
                                        <input type="number" min="0" class="form-control" name="cost[unlimited]" value="0">
                                    </div>

                                    <div class="mt-1 fs-1 text-secondary">Если стоимость бессрочной лицензии не указана, то она будет высчитываться по общему коэффициенту на момент заключения сделки (сейчас: {{ $unlimit_rate }}%)</div>
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-class"
                                       class="col-sm-4 text-end control-label col-form-label">Сортировка <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-1">
                                    <input name="sort" type="text" class="form-control" id="tb-class"
                                           placeholder="" required="" value="{{$group->neuroservices()->max('sort') + 100}}"
                                           value="{{ old('sort') }}">

                                    @error('sort')
                                    <div
                                        class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="row justify-content-center">
                                <div class="col-sm-4 col-ml">
                                    <button type="submit" class=" btn btn-info font-weight-medium rounded-pill px-4">
                                        <div class="d-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-send feather-sm fill-white me-2">
                                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                            </svg>
                                            @lang('button.create')
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-12">
                <div class="d-md-flex align-items-center mt-3">

                    <div class="ms-auto mt-3 mt-md-0">
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
