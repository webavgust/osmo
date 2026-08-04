@extends('layouts.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-9">
            <div class="card card-flush">
                <div class="card-header border-bottom border-gray-200 min-h-auto py-5">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold mb-1">Создание нейросервиса</h3>
                        <span class="text-muted fs-7">Группа «{{ $group->name }}»</span>
                    </div>
                </div>

                <form action="{{ route('neuroservice.store', $group) }}" method="POST" class="needs-validation novalidate">
                    @csrf
                    <div class="card-body py-8">

                        <div class="row mb-6">
                            <label for="tb-fname" class="col-lg-4 col-form-label fw-semibold text-lg-end required">Название</label>
                            <div class="col-lg-5">
                                <input name="name" type="text" class="form-control form-control-solid" id="tb-fname"
                                       required value="{{ old('name') }}" />
                                @error('name')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label for="tb-tech" class="col-lg-4 col-form-label fw-semibold text-lg-end">Техническое название</label>
                            <div class="col-lg-5">
                                <input name="tech_name" type="text" class="form-control form-control-solid" id="tb-tech"
                                       value="{{ old('tech_name') }}" />
                                @error('tech_name')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label class="col-lg-4 col-form-label fw-semibold text-lg-end required">Стоимость</label>
                            <div class="col-lg-5">
                                <div class="input-group">
                                    <span class="input-group-text">год:</span>
                                    <input type="number" min="0" class="form-control form-control-solid" name="cost[year]" value="0" />
                                    <span class="input-group-text">бессрок:</span>
                                    <input type="number" min="0" class="form-control form-control-solid" name="cost[unlimited]" value="0" />
                                </div>
                                <div class="form-text">
                                    Если стоимость бессрочной лицензии не указана, она будет высчитываться
                                    по общему коэффициенту на момент заключения сделки (сейчас: {{ $unlimit_rate }}%)
                                </div>
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label for="tb-class" class="col-lg-4 col-form-label fw-semibold text-lg-end required">Сортировка</label>
                            <div class="col-lg-2">
                                <input name="sort" type="text" class="form-control form-control-solid" id="tb-class"
                                       required value="{{ old('sort') ?? $group->neuroservices()->max('sort') + 100 }}" />
                                @error('sort')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex justify-content-end py-6">
                        <a href="{{ url()->previous() }}" class="btn btn-light me-3">Отмена</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fa-light fa-floppy-disk fs-4 me-2"></i>
                            @lang('button.create')
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
