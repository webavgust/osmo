
<style>
    #spec_scenarios .select2 {
        max-width: none;
    }
    #spec_scenarios select.manual + .select2 {
        width: 100px!important;
        flex-grow: 0;
    }
    #spec_scenarios select:not(.manual) + .select2 {
        width: 100px!important;
        flex-grow: 1!important;
    }
    #spec_scenarios .select2.select2-container--open {
        width: 100%!important;
        flex-grow: 1!important;
    }
    #spec_scenarios select:not(.manual) + * + input,
    #spec_scenarios .select2.select2-container--open + input
    {
        display: none;
    }

    #spec_scenarios .del:hover {
        cursor: pointer;
        color: #ff4b4b!important;
    }
    #spec_scenarios div.once:not(:hover) .del {
        display: none;
    }
    #spec_scenarios div.once:hover .del {
        right: -25px;
        top: 5px;
    }
</style>

<div id="spec_scenarios">
    @forelse($spec_scenarios as $ss)
        <x-contract_specification.scenarios_table_row :num="$loop->iteration" :selected="$ss" :scenarios="$scenarios"></x-contract_specification.scenarios_table_row>
    @empty
    @endforelse
</div>

<div class="text-start mt-1">
    <x-ui.a.ajax
        url="{{ route('contract_specification_scenario.component.scenario_table_row') }}"
        method="get"
        dataType="html"
        callback="output"
        pre="pre"
        btn_type="light-info"
        class="text-info fs-6"
        :data="['num' => 1]"
    >
        <x-ui.icon.regular icon="fa-plus"/>
        Добавить сценарий
    </x-ui.a.ajax>
</div>

<script>
    function pre(data) {
        data['num'] = $("#spec_scenarios").find(".once").length + 1;

        return data;

    }

    function output(html) {
        $("#spec_scenarios").append(html);
        spec_rebind_select2();
    }

    function spec_rebind_select2() {
        $("#spec_scenarios .once:not(.binded)").each(function() {
            row = $(this);
            row.addClass("binded");

            row.find("select").select2({
                dropdownParent: $("#staticBackdrop"),
            }).on('change', function() {
                box_check_form();
                if ($(this).val() == -1) {
                    $(this).addClass('manual');
                } else {
                    $(this).removeClass('manual');
                }
            });

            row.find("input").on("keyup change", function() {
                window.clearTimeout(window.to);
                window.to = setTimeout(() => {
                    box_check_form();
                }, 300);
            });
        });

        box_check_form();
    }

    function scenario_row_delete(obj) {
        obj.parents('.once').remove();

        $("#spec_scenarios").find(".once").each(function(index, row) {
            $(this).find(".count").html(index + 1);
        });
        box_check_form();
    }

    $(document).ready(function() {
        spec_rebind_select2();
    })
</script>
