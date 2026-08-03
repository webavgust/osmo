
<div class="card card-body">
    <div class="card m-0">
        <div class="card-body border-bottom pt-0 pb-2">
            <div class="fs-8 mb-1">Редактирование объекта:</div>
            <h4 class="card-title">
                <span>
                    <x-ui.icon.light icon="fa-chevrons-left"/>
                    {{ $object->chain_name }}
                    <x-ui.icon.light icon="fa-chevrons-right"/>
                </span>
            </h4>
        </div>
        <form id="form_menu" data-link="{{ route('api.lab-object.update') }}/{{ old('id') }}" action="" method="POST"
              class="needs-validation novalidate">
            @csrf
            <div class="card-body">

                <div id="tree_measures" data-url="{{ route('api.lab-measure.index') }}"></div>

                <x-ui.button.default btn_type="info" class="mt-4 w-100" onclick="javascript:bind_save()">Сохранить привязки</x-ui.button.default>
            </div>
        </form>
    </div>
</div>

<script>
    function bind_save() {
        if(!confirm("Вы действительно хотите пересохранить привязки? Это действие необратимо!"))
            return false;

        selectedNodes = $('#tree_measures').jstree('get_selected'); // Получаем ID всех выделенных узлов


        $.ajax({
            url: "{{ route('api.lab-measure.bind_object', $object) }}",
            global: false,
            type: "POST",
            data: ({
                nodes: selectedNodes,
                _token: '{{ auth()->user()->ajax_token }}'
            }),
            dataType: "json",
            success: function (result) {
                if (result.status == 'success') {
                    toastr.success("Привязка сохранена", "Это успех!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                } else {
                    toastr.error("Не получилось сохранить привязку", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                }
                // $(".menu3_msg").html("Дерево успешно сохранено!").show().parents("tr").fadeIn(500);
                // setTimeout("hide_msg();", 5000);
                // var instance = $('#html').jstree(true);
                // instance.refresh();
                // $("#panel_pad").html("");
            }
        });
    }

    $(document).ready(function () {



        var tree = $('#tree_measures').jstree({
            "core": {
                "icon": "/images/tree/save.png",
                "check_callback": true,
                "data": {
                    "url": $('#tree_measures').data("url") + '?_token={{ auth()->user()->ajax_token }}',
                    "dataType": "json" // needed only if you do not supply JSON headers
                },
                "close_all": true,
                "multiple": true
            },
            "plugins": ["dnd", "contextmenu", "state", "checkbox"]
        }).on("changed.jstree", function (e, data) {
            // if (data.selected.length) {
            //     if (!window.lock_panel) show_panel(data.instance.get_node(data.selected[0]).id);
            // }
        }).on("ready.jstree", function() {
            $('#tree_measures').jstree('close_all');
            $('#tree_measures').jstree('deselect_all');
            @if(!empty($object->measures))
                var selectedNodes = [{{ implode(",", $object->measures) }}]; // Пример массива с ID выбранных узлов

                selectedNodes.forEach(function(nodeId) {
                    $('#tree_measures').jstree('select_node', nodeId); // Выделяем узел по ID
                });
            @endif
        });
        //
        // $('#html li').each(function () {
        //     $("#html").jstree().disable_node(this.id);
        // })
        window.tree = tree;



        $(".select2").select2();

    });


    function tree_recursion(id) {
        var list = [];
        var is_exist = false;
        $("#" + id + ">ul>li").filter(".jstree-node").each(function (index, element) {
            is_exist = true;
            childs = tree_recursion($(element).attr('id'));
            if (childs == false) list.push({
                "id": $(element).attr('id'),
                "name": $(element).find("a").html().replace(/<\/?[^>]+>/gi, '')
            });
            else list.push({
                "id": $(element).attr('id'),
                "name": $(element).find("a").html().replace(/<\/?[^>]+>/gi, ''),
                "children": childs
            });
        });

        if (is_exist == false) return false;
        return list;
    }

</script>
