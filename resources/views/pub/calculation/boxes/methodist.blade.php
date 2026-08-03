@extends('components.box.box-static-large')

@section('body')
    <div class="row">
        <div class="col-12">

            <div class="table-responsive">
                <table class="table customize-table v-middle">
                    <thead class="table-dark">
                    <tr>
                        <th>Работа</th>
                        <th class="text-center">Кол-во</th>
                        <th class="text-right">Стоимость</th>
                        <th class="text-right">Итого</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($works as $work)
                        <tr>
                            <td>
                                {{ $work->work->name }}
                            </td>
                            <td class="text-center">
                                {{ $work->count }}
                            </td>
                            <td class="text-right">
                                {{ tools()->cost_normalize($work->cost) }} ₽
                            </td>
                            <td class="text-right">
                                {{ tools()->cost_normalize($work->total) }} ₽
                            </td>

                        </tr>
                    @endforeach
                    </tbody>
                </table>

                <div class="d-flex justify-content-end">
                    <x-ui.badge.default type="secondary" class="font-14 me-3    ">{{ tools()->cost_normalize($target->methodist_salary)  }} ₽</x-ui.badge.default>
                </div>
            </div>


        </div>
    </div>


@endsection

@section('footer')
@endsection

