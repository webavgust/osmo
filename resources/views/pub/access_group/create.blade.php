@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body border-bottom">
                        <h4 class="card-title">Создание группы доступов</h4>
                        <h6 class="card-subtitle mb-0">
                            Здесь описание
                        </h6>
                    </div>
                    <form action="{{ route('access_group.store') }}" method="POST"
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
                                <label for="tb-code" class="col-sm-4 text-end control-label col-form-label">Префиск <span class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input name="prefix" type="text" class="form-control " id="tb-code"
                                           placeholder="" required="" value="{{ old('prefix') }}">

                                    @error('prefix')
                                    <div
                                        class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-icon"
                                       class="col-sm-4 text-end control-label col-form-label">Иконка (css class)</label>
                                <div class="col-sm-1">
                                    <input name="sort" type="text" class="form-control" id="tb-icon"
                                           placeholder="" value="{{ old('icon') }}"
                                           value="{{ old('icon') }}">

                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-sort"
                                       class="col-sm-4 text-end control-label col-form-label">Сортировка <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-1">
                                    <input name="sort" type="text" class="form-control" id="tb-sort"
                                           placeholder="" required="" value="{{\App\Modules\Pub\AccessGroup\Models\AccessGroup::max('sort') + 100}}"
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
