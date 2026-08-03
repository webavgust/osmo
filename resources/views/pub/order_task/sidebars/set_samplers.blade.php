@extends('components.sidebar.offcanvas-right')


@section('body')
    <form method="post" id="samplers">
        <div class="samplers">
            @foreach($users as $user)
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="{{ $user->id }}" id="cb_{{ $user->id }}" @checked(in_array($user->id, $selected))>
                    <label class="form-check-label" for="cb_{{ $user->id }}">
                        {{ $user->full_name }}
                    </label>
                </div>
            @endforeach
        </div>
        <div class="mt-4">

            <h4>Что делать с вложенными сущностями</h4>

            <div class="form-check form-check-inline">
                <input class="form-check-input secondary" type="radio" name="add-action" id="cb_nothing" value="nothing" checked>
                <label class="form-check-label text-secondary font-12 fw-normal" for="cb_nothing"><strong>НИЧЕГО</strong></label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input warning" type="radio" name="add-action" id="cb_annulate" value="annulate">
                <label class="form-check-label text-warning font-12 fw-normal" for="cb_annulate"><strong>ОБНУЛИТЬ</strong> пробоотборщиков на вложенных уровнях</label>
            </div>

            <div class="form-check form-check-inline">
                <input class="form-check-input danger" type="radio" name="add-action" id="cb_replace" value="replace">
                <label class="form-check-label text-danger font-12 fw-normal" for="cb_replace"><strong>ЗАМЕНИТЬ</strong> пробоотборщиков на вложенных уровнях</label>
            </div>



        </div>
        <div class="mt-4">
            <x-ui.button.default btn_type="info" onclick="javascript:samplers_set();">Применить</x-ui.button.default>
        </div>
    </form>

    <script>
        function num_rus(cnt, f1, f2, f3, noOut = false) {
            const l = cnt.toString().slice(-1) -0 ;
            const l2 = cnt.toString().length > 1 ? cnt.toString().slice(-2) - 0 : 0;
            let str = f1; // 22
            if (l === 1 && (cnt < 11 || cnt > 20)) str = f2; // 21
            if ((l2 >= 11 && l2 <= 20) || (l >= 5 && l <= 9) || l === 0) str = f3; // 20
            if (!noOut) {
                return cnt + ' ' + str;
            } else {
                return str;
            }
        }



        function samplers_set() {
            if(!confirm('Вы действительно хотите закрепить пробоотборщиков?'))
                return false;

            samplers = [];
            $(".samplers input:checked").each(function () {
                samplers.push($(this).val());
            });

            add_action = $("[name='add-action']:checked").val();


            if(add_action == 'replace') {
                $("li[id='{{ $selector }}'] input").val(samplers.join(','));
                $("li[id='{{ $selector }}'] a span").html(samplers.length ? num_rus(samplers.length, 'человека', 'человек', 'людей') : 'Выбрать');

                $("li[id='{{ $selector }}'] .text-danger").removeClass("text-danger");
            }
            else
            {
                if(add_action == 'annulate') {
                    $("li[id='{{ $selector }}'] input").val('');
                    $("li[id='{{ $selector }}'] a span").html('Выбрать');
                }

                $("li[id='{{ $selector }}'] > div > div > input").val(samplers.join(','));
                $("li[id='{{ $selector }}'] > div > div > a span").html(samplers.length ? num_rus(samplers.length, 'человека', 'человек', 'людей') : 'Выбрать');


                $("li[id='{{ $selector }}'] > div .text-danger").removeClass("text-danger");

            }


            sidebar_close();
        }
    </script>
@endsection
