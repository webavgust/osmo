@extends('layouts.layout')

@section('content')
    @php
        $group_hl = session('show_id') ?? $_GET['hl'] ?? null;
    @endphp
    <!-- -------------------------------------------------------------- -->
    <!-- Page wrapper  -->
    <!-- -------------------------------------------------------------- -->

    <div class="email-app todo-box-container">
        <!-- -------------------------------------------------------------- -->
        <!-- Left Part -->
        <!-- -------------------------------------------------------------- -->
        <div class="left-part list-of-tasks">
            <a
                class="
                fa-light fa-bars
                btn btn-success
                show-left-part
                d-block d-md-none
              "
                href="javascript:void(0)"
            ></a>
            <div class="scrollable" style="height: 100%">
                @can('access_create')
                    <div class="p-3">
                        <a class="btn btn-primary d-block" href="{{ route('access_group.create') }}" id="add-group">Добавить группу</a>
                    </div>
                @endcan

                <div class="divider"></div>
                <ul class="list-group">
                    @foreach($groups as $i => $group)
                        @php $active = 0; @endphp
                        @if((!empty($group_hl) && $group_hl == $group->id) || (empty($group_hl) && $i == 0)) @php $group_out = $group->id; $active = 1; @endphp  @endif
                        <x-access.group_list_item :group="$group" :active="$active"></x-access.group_list_item>
                    @endforeach
                </ul>
            </div>
        </div>
        <!-- -------------------------------------------------------------- -->
        <!-- Right Part -->
        <!-- -------------------------------------------------------------- -->
        <div class="right-part mail-list bg-white overflow-auto">
            <div id="todo-list-container">

                <div class="p-3 border-bottom">
                            <div class="input-group searchbar">
                                <span class="input-group-text" id="search">
                                    <i class="fa-light fa-magnifying-glass text-muted"></i>
                                </span>
                                <input
                                    type="text"
                                    class="form-control"
                                    placeholder="Поиск доступа"
                                    aria-describedby="search"
                                />
                                @can('access_create')
                                    <a
                                        class="btn btn-primary"
                                        data-href="{{ route('access.create') }}"
                                        id="add-access"
                                    >
                                        <i class="fa-light fa-plus me-1"></i>
                                        Создать доступ
                                    </a>
                                @endcan
                                @can('access_create')
                                    <a
                                        class="btn btn-light"
                                        data-href="{{ route('access_group.edit') }}"
                                        id="edit-access-group"
                                    >
                                        Редактировать группу
                                    </a>
                                @endcan
                            </div>
                </div>

                <!-- Todo list-->
                <div class="todo-listing">
                    <div id="all-todo-container" class="p-3">
                        @foreach($groups as $group)
                            @foreach($group->accesses()->get() as $access)
                                <x-access.list_item groupOut="{{$group_out}}" groupId="{{$group->id}}" :access="$access" ></x-access.list_item>
                            @endforeach
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>



    </form>
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
                    <h4 class="modal-title" id="danger-header-modalLabel">Удаление доступа</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Не удалять"></button>
                </div>
                <div class="modal-body">
                    <h5 class="mt-0">Внимание!</h5>
                    <p>
                        Удаление доступа необратимо!
                        <br/>
                        Также удаление доступа удалит данные о назначенных доступах для пользователей и групп.
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
                    <button
                        type="button"
                        onclick="javascript:delete_confirm();"
                        class="btn btn-light-danger fw-semibold"
                    >
                        УДАЛИТЬ
                    </button>
                </div>
            </div>
            <!-- /.modal-content -->
        </div>
        <!-- /.modal-dialog -->
    </div>

    <!-- -------------------------------------------------------------- -->
    <!-- End Wrapper -->


@endsection

@section('js')
    @parent
    <script src="/assets/libs/quill/dist/quill.min.js"></script>
    <script src="/dist/js/pages/todo/todo.js"></script>
    <script>
        function delete_process(access_id)
        {
            window.access_id = access_id;
        }
        function delete_confirm()
        {
            access_id = window.access_id;
            $("[type='access'][id=" + access_id + "]").find("form").submit();
        }
    </script>
@endsection
