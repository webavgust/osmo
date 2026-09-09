@extends('layouts.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card card-flush">
                <div class="card-header border-bottom border-gray-200 min-h-auto py-5">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold mb-1">Создание группы</h3>
                        <span class="text-muted fs-7">Группа объединяет нейросервисы в списке</span>
                    </div>
                </div>

                <form action="{{ route('neuroservice_group.store') }}" method="POST" class="needs-validation novalidate">
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
                            <label for="tb-sort" class="col-lg-4 col-form-label fw-semibold text-lg-end required">Сортировка</label>
                            <div class="col-lg-2">
                                <input name="sort" type="text" class="form-control form-control-solid" id="tb-sort"
                                       required value="{{ old('sort') ?? \App\Modules\Pub\NeuroserviceGroup\Models\NeuroserviceGroup::max('sort') + 100 }}" />
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
