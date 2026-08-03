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
                                    <h4 class="card-title">Список групп пользователей</h4>
                                    <h6 class="card-subtitle lh-base">
                                        Для назначения доступа для группы перейдите на детальную страницу группы
                                    </h6>
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
                                    <th class="border-bottom border-top">ID</th>
                                    <th class="border-bottom border-top">Пользователей</th>
                                    <th class="border-bottom border-top" width="10000">Название</th>
                                    <th class="border-bottom border-top">Доступов</th>
                                    <th class="border-bottom border-top">Активность</th>
                                    <th class="border-bottom border-top" width="1"></th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($groups as $group)
                                    <tr class="search-items">
                                        <td>{{ $group->id }}</td>
                                        <td align="center">
                                            <strong>{{ $group->users_count }}</strong>
                                        </td>
                                        <td>{{ $group->name }}</td>
                                        <td align="center">
                                            @if($group->accesses_count > 0)
                                                <strong>{{ $group->accesses_count }}</strong>
                                            @else
                                                <i class="fa-light fa-dash"></i>
                                            @endif

                                        </td>
                                        <td>
                                            @if($group->active)
                                                <span class="mb-1 badge bg-success">Активная</span>
                                            @else
                                                <span class="mb-1 badge bg-danger">Неактивная</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="dropdown dropstart">
                                                <a href="#" class="link" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal feather-sm"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
                                                </a>
                                                <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                    <li>
                                                        <a class="dropdown-item" href="{{ route('access_set.group', $group) }}"><i class="fas fa-edit text-info me-1"></i> Назначить доступы</a>
                                                    </li>
                                                </ul>
                                            </div>
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
