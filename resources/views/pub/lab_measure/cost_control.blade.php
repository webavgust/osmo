@extends('layouts.layout')

@section('styles')
    <link href="/dist/modules/jstree/themes/default/style.min.css" rel="stylesheet"/>
@endsection


@section('content')
    <form method="post" action="{{ route('lab-measure.cost_save') }}" id="save">
        @csrf
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card w-100">
                        <div class="card-body p-0  border-top-0">
                                    <div class="agree-table">
                                        <div class="table-responsive">
                                            <table class="table stylish-table v-middle mb-0 no-wrap">
                                                <thead>
                                                <tr>
                                                    <th class="border-0 text-muted fw-normal">ID</th>
                                                    <th class="border-0 text-muted fw-normal">Название</th>
                                                    <th class="border-0 text-muted fw-normal" style="width: 100px">Стоимость</th>
                                                    <th class="border-0 text-muted fw-normal" style="width: 100px">Бонус</th>
                                                    <th class="border-0 text-muted fw-normal" style="width: 700px; max-width: 50%">Комментарий</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($data as $object)
                                                        <tr class="bg-light-info">
                                                            <td class="fw-bold font-20">{{ $object->id }}</td>
                                                            <td class="fw-bold font-20 ps-1">{{ $object->name }}</td>
                                                            <td></td>
                                                            <td></td>
                                                            <td></td>
                                                        </tr>
                                                        @foreach($object->children as $row_l2)
                                                            <tr class="bg-light-secondary">
                                                                <td class="fw-bold font-16">{{ $row_l2->id }}</td>
                                                                <td class="fw-bold font-16 ps-3">{{ $row_l2->name }}</td>
                                                                <td></td>
                                                                <td></td>
                                                                <td></td>
                                                            </tr>
                                                            @foreach($row_l2->children as $measure)
                                                                <tr class="">
                                                                    <td class="font-14 pt-1 pb-1">{{ $measure->id }}</td>
                                                                    <td class="font-14 p-1 ps-5 text-wrap">{{ $measure->name }}</td>
                                                                    <td class="p-1">
                                                                        <input type="number" class="form-control" name="cost[{{ $measure->id }}]" value="{{ $measure->cost }}">
                                                                    </td>
                                                                    <td class="p-1">
                                                                        <input type="number" class="form-control" name="bonus[{{ $measure->id }}]" value="{{ $measure->bonus }}">
                                                                    </td>
                                                                    <td class="p-1">
                                                                         <input type="text" class="form-control" name="comment[{{ $measure->id }}]" value="{{ $measure->comment }}">
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @endforeach
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                        </div>
                    </div>

                    <x-ui.button.default btn_type="info" class="mt-1 mb-3" onclick="javascript:save_cost();">Сохранить изменения</x-ui.button.default>
                </div>
            </div>
        </div>

    </form>



    <div
        id="save-modal"
        class="modal fade"
        tabindex="-1"
        aria-labelledby="danger-header-modalLabel"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content">
                <div class=" modal-header modal-colored-header bg-danger text-white">
                    <h4 class="modal-title" id="danger-header-modalLabel">Сохранение цен</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не сохранять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Сохранение цен необратимо!
                    </p>
                </div>
                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal"
                    >
                        Не сохранять
                    </button>
                    <button
                        type="button"
                        onclick="javascript:save_confirm();"
                        data-bs-dismiss="modal"
                        class="
                                btn btn-light-danger
                                text-danger
                                font-weight-medium
                              "
                    >
                        СОХРАНИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>
@endsection


@section('js')
    @parent

    <script>

        function save_cost() {
            $("#save-modal").modal('show');
        }

        function save_confirm() {
            $("form#save").submit();
        }

    </script>
@endsection

