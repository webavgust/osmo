@extends('layouts.layout')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-xl-8">
            <div class="card card-flush">
                <div class="card-header border-bottom border-gray-200 min-h-auto py-5">
                    <div class="card-title flex-column align-items-start">
                        <h3 class="fw-bold mb-1">Редактирование группы</h3>
                        <span class="text-muted fs-7">{{ $group->name }}</span>
                    </div>
                </div>

                <form action="{{ route('neuroservice_group.update', $group) }}" method="POST" class="needs-validation novalidate">
                    @csrf
                    @method('PUT')
                    <div class="card-body py-8">

                        <div class="row mb-6">
                            <label for="tb-fname" class="col-lg-4 col-form-label fw-semibold text-lg-end required">Название</label>
                            <div class="col-lg-5">
                                <input name="name" type="text" class="form-control form-control-solid" id="tb-fname"
                                       required value="{{ old('name') ?? $group->name }}" />
                                @error('name')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="row mb-6">
                            <label for="tb-sort" class="col-lg-4 col-form-label fw-semibold text-lg-end required">Сортировка</label>
                            <div class="col-lg-2">
                                <input name="sort" type="text" class="form-control form-control-solid" id="tb-sort"
                                       required value="{{ old('sort') ?? $group->sort }}" />
                                @error('sort')
                                    <div class="text-danger fs-7 mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="card-footer d-flex justify-content-between py-6">
                        <div>
                            @if(!$group->neuroservices()->count())
                                <button type="button" class="btn btn-light-danger" data-bs-toggle="modal" data-bs-target="#delete-modal">
                                    <i class="fa-light fa-trash fs-5 me-2"></i>
                                    {{ __('button.delete') }}
                                </button>
                            @endif
                        </div>

                        <div class="d-flex">
                            <a href="{{ url()->previous() }}" class="btn btn-light me-3">Отмена</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-light fa-floppy-disk fs-4 me-2"></i>
                                @lang('button.save')
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="delete-modal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light-danger">
                    <h4 class="modal-title text-danger">Удаление группы</h4>
                    <button type="button" class="btn btn-icon btn-sm btn-active-light-danger" data-bs-dismiss="modal" aria-label="Не удалять">
                        <i class="fa-light fa-xmark fs-2"></i>
                    </button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p class="mb-0">
                        Удаление группы необратимо.
                        <br />
                        Вы можете удалить эту группу, потому что в ней нет записей.
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Не удалять</button>

                    <form method="POST" action="{{ route('neuroservice_group.delete', $group) }}">
                        @method('DELETE')
                        @csrf
                        <button type="submit" class="btn btn-danger">УДАЛИТЬ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
