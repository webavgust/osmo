@extends('components.box.box-static-large')

@section('body')
    <form method="POST" id="generate" >
        <div class="row">
            <div class="col-12">
                <table id="table-summary" class="glue table no-wrap w-100 fs-3">
                    <tbody>
                        <tr class="caption">
                            <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top">Название</th>
                            <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top">Автор</th>
                            <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top" width="30">Дата создания</th>
                            <th class="w-50 text-start text-dark fw-bold fs-5 p-1 ps-2" valign="top"></th>
                        </tr>
                        @foreach($proposal->proposal_pdf_templates as $template)
                            <tr>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->creator->full_name }}</td>
                                <td class="text-nowrap">{{ $template->created_at->format("d.m.Y H:i:s") }}</td>
                                <td class="p-1">
                                    <textarea class="code d-none">{!! $template->html !!}</textarea>
                                    <x-ui.button.default btn_type="info" onclick="javascript:template_paste($(this))">Вставить</x-ui.button.default>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </form>

    <script>
        // function template_paste(obj) {
        //     var code = $(obj).parents('td').find('textarea').val();
        //
        //     var $printDiv = $('#print');
        //     var result = {};
        //     $printDiv.find('[keep]').each(function() {
        //         var keepValue = $(this).attr('keep'); // Get the value of the 'keep' attribute
        //         var innerHTML = $(this).html(); // Get the inner HTML of the element
        //         result[keepValue] = innerHTML; // Assign it to the result object
        //     });
        //
        //     $("#print").html(code);
        //     $.each(result, function(key, value) {
        //         $("#print [keep='" + key + "']").html(value);
        //     });
        //     rebind();
        //     box_close();
        //
        //     toastr.success("Сохраненный шаблон успешно вставлен", "Это успех!", {
        //         progressBar: true,
        //         "timeOut": 3000,
        //     });

         function template_paste(obj) {
            var code = $(obj).parents('td').find('textarea').val();

            var $printDiv = $('#print');
            var result = {};
            $printDiv.find('[keep]').each(function() {
                var keepValue = $(this).attr('keep'); // Get the value of the 'keep' attribute
                var innerHTML = $(this).html(); // Get the inner HTML of the element
                result[keepValue] = innerHTML; // Assign it to the result object
            });

            $("#print").html(``);
            setTimeout(() => {
                $("#print").html(code);

                // $.each(result, function(key, value) {
                //     var str = `${value}`;
                //     $(`#print [keep='${key}']`).html(str);
                // });

                rebind();
                box_close();
            }, 500);






            toastr.success("Сохраненный шаблон успешно вставлен", "Это успех!", {
                progressBar: true,
                "timeOut": 3000,
            });
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        <x-ui.button.default btn_type="danger" onclick="javascript:box_close();">
            <x-ui.icon.solid icon="fa-close"></x-ui.icon.solid>
            <span>Закрыть</span>
        </x-ui.button.default>
    </div>
@endsection
