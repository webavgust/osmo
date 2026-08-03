<style>
    @page { margin:10px; font-size: 12px; }

    .ta-right { text-align: right; }
    .ta-center { text-align: center; }
    .valign { vertical-align: middle; }

    .fs10 { font-size: 10px; }
    .fs11 { font-size: 11px; }
    .fs12 { font-size: 12px; }
    .fs13 { font-size: 13px; }
    .fs14 { font-size: 14px; }
    .fs15 { font-size: 15px; }
    .fs16 { font-size: 16px; }
    .fs20 { font-size: 20px; }
    .fs21 { font-size: 21px; }
    .fs22 { font-size: 22px; }
    .fs23 { font-size: 23px; }
    .fs24 { font-size: 24px; }
    .fs25 { font-size: 25px; }
    .fs26 { font-size: 26px; }

    table { border: 0; border-collapse: collapse; }
    table td { border: 1px solid black; padding: 5px 10px; }
    table th { border: 1px solid black;  font-size: 11px; }

</style>


<div class="ta-right" style="padding-top: 30px">
    <strong class="fs12">Приложение №{{$order_task->number->number}}<br/>от {{ _date($order_task->created_at) }}г.</strong>
</div>

@if(!empty($order_task->order->id))
    <div class="ta-right" style="padding-top: 10px;">
        <span class="fs20">к Договору №{{$order_task->order->contract_id}}<br/>от {{ _date($order_task->order->contract_conclusion) }}г.</span>
    </div>
@endif

<div class="ta-center" style="margin-top: 30px">
    <strong class="fs22">Техническое задание на выполнение лабораторных исследований</strong>
</div>

<table style="margin-top: 30px; width: 100%; border: 1px; ">
<tr>
    <th>№<br/>п/п</th>
    <th>Точный адрес и место точки отбора/измерения;<br/>Координаты метрические/географические;</th>
    <th>Наименование точки<br/>отбора/измерений</th>
    <th>Наименование измеряемого<br/>показателя</th>
    <th>Стоимость<br/>измерения<br/>(руб.)</th>
    <th>Примечание</th>
</tr>
    @foreach($order_task->objects as $object_i => $object)
        <tr>
            <td colspan="6" style="height: 40px" class="ta-center valign fs18">
                <strong>{{ $object_i + 1 }}. {{ $object->name }}</strong>
            </td>
        </tr>
        @foreach($object->addresses as $a_i => $address)
            @foreach($address->points as $a_p => $point)
                @foreach($point->measures as $a_m => $measure)

                   <tr>
                        @if($a_m == 0 && $a_p == 0)
                            <td class="ta-center" rowspan="{{ count($address->getMeasuresAllAttribute()) }}">{{ $a_i + 1 }}</td>
                            <td rowspan="{{ count($address->getMeasuresAllAttribute()) }}">{{ $address->address }}</td>
                        @endif

                        @if($a_m == 0)
                                <td rowspan="{{ count($point->measures) }}">{{ $point->name }}</td>
                        @endif

                        <td>{{ $measure->measure->name }}</td>
                        <td class="ta-right" >{{ \App\Facades\Tools::cost_normalize($measure->cost_total) }}</td>


                            @if($a_m == 0)
                                <td rowspan="{{ count($point->measures) }}">{{ $measure->comment }}</td>
                        @endif
                   </tr>
                @endforeach
            @endforeach
        @endforeach

        @foreach($object->services as $s_i => $service)
            <tr>
                <td>{{ count($object->addresses) + $s_i + 1 }}</td>
                <td>{{ $service->name }}</td>
                <td/>
                <td/>
                <td>{{ \App\Facades\Tools::cost_normalize($service->pivot->cost_total) }}</td>
                <td/>
            </tr>
        @endforeach
    @endforeach
        <tr>
            <td colspan="4">Итоговая стоимость работ (руб.)</td>
            <td colspan="2">
                {{ \App\Facades\Tools::cost_full_string($order_task->cost_total) }} <br/>
                НДС не облагается согласно п. 1 ст. 346.12 гл. 26.2 Налогового Кодекса Российской Федерации.
            </td>
        </tr>

        <tr>
            <td colspan="6">
                <p>Официальные контактные данные заказчика: </p>
                <p>Юридический адрес: ...</p>
                <p>Почтовый адрес: ...</p>
                <p>Электронная почта: ...</p>
                <p>Телефон: ...</p>
            </td>
        </tr>

        <tr>
            <td colspan="6" style="height: 40px" class="valign">
                Уполномоченный представитель заказчика: <br/> ...
            </td>
        </tr>

        <tr>
            <td colspan="6" style="height: 40px" class="ta-center valign fs18">
                <strong>Порядок и сроки (календарный план) выполнения работ*</strong>
            </td>
        </tr>

        <tr>
            <td colspan="4" style="height: 40px" class="ta-center valign fs18">
                Вид работ
            </td>
            <td colspan="2" style="height: 40px" class="ta-center valign fs18">
                Срок выполнения (р/дней)
            </td>
        </tr>

        <tr>
            <td>1</td>
            <td colspan="3">Проведение отбора/приема проб</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>2</td>
            <td colspan="3">Проведение анализа</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td>3</td>
            <td colspan="3">Оформление протокола анализа</td>
            <td colspan="2"></td>
        </tr>
        <tr>
            <td colspan="6">
                <div style="width: 100%" class="ta-center">Результаты работ:</div>
                <div>1. Акт (-ы) отбора/приема проб;</div>
                <div>2. Протоколы лабораторных анализов.</div>
            </td>
        </tr>
</table>

<p>* - все работы по договору выполняются последовательно, в соответствии с календарным планом.</p>

<table width="100%" class="" style="margin-top: 50px">
    <tr>
        <td width="50%" style="border: 0">
            <strong>Директор</strong>
            <br/>
            <br/>
            <br/>
            <br/>
            __________________________________ ...<br/>
            м.п.

        </td>
        <td width="50%" style="border: 0">
            <strong>Генеральный директор<br/>
                ООО ХАЛ «РПН-Сфера»</strong>
            <br/>
            <br/>
            <br/>
            <br/>
            __________________________________ Ю.А. Кортунов<br/>
            м.п.

        </td>
    </tr>
</table>


