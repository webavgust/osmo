@extends('layouts.layout')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header min-h-auto py-5 border-bottom">
                        <div class="card-title flex-column align-items-start">
                            <h4 class="fw-bold mb-0">Редактирование группы</h4>
                        </div>
                    </div>
                    <form action="{{ route('scenario_group.update', $group) }}" method="POST"
                          class="needs-validation novalidate">
                        @csrf
                        @method('PUT')
                        <div class="card-body">
                            <div class="mb-3 row">
                                <label for="tb-fname"
                                       class="col-sm-4 col-form-label fw-semibold text-lg-end">Название<span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-4">
                                    <input name="name" type="text" class="form-control " id="tb-fname"
                                           placeholder="" required="" value="{{ old('name') ?? $group->name }}">

                                    @error('name')
                                    <div
                                        class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="mb-3 row">
                                <label for="tb-sort"
                                       class="col-sm-4 col-form-label fw-semibold text-lg-end">Сортировка <span
                                        class="text-danger">*</span></label>
                                <div class="col-sm-1">
                                    <input name="sort" type="text" class="form-control" id="tb-sort"
                                           placeholder="" required=""
                                           value="{{ old('sort') ?? $group->sort }}">

                                    @error('sort')
                                    <div
                                        class=" alert alert-danger d-flex align-items-center p-2 mt-1"
                                        role="alert">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="row justify-content-center">
                                <div class="col-sm-4 col-ml">
                                    <button type="submit" class=" btn btn-primary fw-semibold rounded-pill px-4">
                                        <div class="d-flex align-items-center">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24"
                                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                                 stroke-linecap="round" stroke-linejoin="round"
                                                 class="feather feather-send feather-sm fill-white me-2">
                                                <line x1="22" y1="2" x2="11" y2="13"></line>
                                                <polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
                                            </svg>
                                            @lang('button.save')
                                        </div>
                                    </button>

                                    @if(!$group->scenarios()->count())
                                        <button type="button" class=" btn btn-outline-danger ms-2"
                                                data-bs-toggle="modal" data-bs-target="#delete-modal"
                                        >
                                            <div class="d-flex align-items-center">
                                                {{ __('button.delete') }}
                                            </div>
                                        </button>

                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div
        id="delete-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Удаление группы</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не удалять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Удаление группы необратимо!
                        <br/>
                        Вы можете удалить эту группу, потому что в ней нет записей
                    </p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Не удалять
                    </button>

                    <form method="POST" action="{{ route('scenario_group.delete', $group   ) }}">
                        @method('DELETE')
                        @csrf
                        <button
                            type="submit"
                            class="btn btn-light-danger fw-semibold"
                        >
                            УДАЛИТЬ
                        </button>
                    </form>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>


@endsection

