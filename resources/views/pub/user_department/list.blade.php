@extends('layouts.layout')

@section('styles')
@endsection

@section('content')
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-10">
                                    <h4 class="card-title">Список поздразделений пользователей</h4>
                                </div>
                                <div class="col-2">
                                    <input type="text" class="form-control product-search" id="input-search" placeholder="Поиск по названию">
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table customize-table mb-0 v-middle search-table">
                                <thead class="table-light">
                                <tr class="header-item">
                                    <th class="border-bottom border-top">Активность</th>
                                    <th class="border-bottom border-top" width="10000">Название</th>
                                    <th class="border-bottom border-top">Пользователей</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groups as $group)
                                    <tr class="search-items">
                                        <td>
                                            @if($group->active)
                                                <span class="mb-1 badge bg-success">Активная</span>
                                            @else
                                                <span class="mb-1 badge bg-danger">Неактивная</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('user_department.detail', $group) }}">
                                                {{ $group->name }}
                                            </a>
                                        </td>
                                        <td align="center">
                                            <strong>{{ $group->users_count }}</strong>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>
        </div>



@endsection

@section('js')
    @parent
    <script>
        $("#input-search").on("keyup", function () {
            var rex = new RegExp($(this).val(), "i");
            $(".search-table .search-items:not(.header-item)").hide();
            $(".search-table .search-items:not(.header-item)")
                .filter(function () {
                    return rex.test($(this).text());
                })
                .show();
        });
    </script>
@endsection
