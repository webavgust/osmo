@php
    $arObjectTypes = ['water' => 0, 'earth' => 0, 'air' => 0, 'physical' => 0];
    $arObjectTypesCount = 1;
    foreach($evaluation->objects as $obj) {
        if($obj->lab_object->isWater) { $arObjectTypes['water']++; continue; }
        if($obj->lab_object->isEarth) { $arObjectTypes['earth']++; continue; }
        if($obj->lab_object->isAir) { $arObjectTypes['air']++; continue; }
        if($obj->lab_object->isPhysical) { $arObjectTypes['physical']++; continue; }
    }



@endphp
<style>
    @font-face {
        font-family: "Times New Roman";
    }

    @page {
        size: A4 landscape;
        margin: 1.25cm 2cm 1.5cm 2cm;
        margin: 30px;
        font-size: 12px;
        font-size: 14px;
        font-family: "Times New Roman";
    }

    * {
        font-family: "Times New Roman";
    }

    .ta-right {
        text-align: right;
    }

    .ta-center {
        text-align: center;
    }

    .valign {
        vertical-align: middle;
    }

    .fs10 {
        font-size: 10px;
        font-family: "Times New Roman";
    }

    .fs11 {
        font-size: 11px;
        font-family: "Times New Roman";
    }

    .fs12 {
        font-size: 12px;
        font-family: "Times New Roman";
    }

    .fs13 {
        font-size: 13px;
        font-family: "Times New Roman";
    }

    .fs14 {
        font-size: 14px;
        font-family: "Times New Roman";
    }

    .fs15 {
        font-size: 15px;
        font-family: "Times New Roman";
    }

    .fs16 {
        font-size: 16px;
        font-family: "Times New Roman";
    }

    .fs20 {
        font-size: 20px;
        font-family: "Times New Roman";
    }

    .fs21 {
        font-size: 21px;
        font-family: "Times New Roman";
    }

    .fs22 {
        font-size: 22px;
        font-family: "Times New Roman";
    }

    .fs23 {
        font-size: 23px;
        font-family: "Times New Roman";
    }

    .fs24 {
        font-size: 24px;
        font-family: "Times New Roman";
    }

    .fs25 {
        font-size: 25px;
        font-family: "Times New Roman";
    }

    .fs26 {
        font-size: 26px;
        font-family: "Times New Roman";
    }

    .fw {
        font-weight: bold;
        font-family: "Times New Roman";
    }

    table {
        border: 0;
        border-collapse: collapse;
        font-family: "Times New Roman";
    }

    table td {
        border: 1px solid black;
        padding: 5px 10px;
        font-family: "Times New Roman";
    }

    table th {
        border: 1px solid black;
        font-size: 11px;
        font-family: "Times New Roman";
    }

    p {
        font-size: 16px;
        margin-top: 0;
        padding-top: 0;
        font-family: "Times New Roman";
    }

    table {
        font-size: 14.3px;
        font-family: "Times New Roman";
    }

    h1 {
        font-size: 18.5px;
        text-align: center;
        font-family: "Times New Roman";
    }

    h2 {
        font-size: 16px;
        text-align: left;
        margin-bottom: 0;
        padding-bottom: 0;
        font-family: "Times New Roman";
    }

    td {
        padding: 20px;
    }
</style>


<div class="ta-right" style="padding-top: 30px">
    <strong class="fs12">
        <div class="fw">{{ $evaluation->portal->annex_name ?? 'Приложение № ?' }}</div>
        <div class="fw">к Договору №{{ $evaluation->portal->contract_name ?? '?' }}</div>
        @if(empty($date))
            <div class="fw">от " " ________ 202 г.</div>
        @else
            <div class="fw">от "{{ $date->format('d') }}
                " {{ \Illuminate\Support\Str::lower(\App\Facades\Tools::MONTH_NAME_D[$date->format('n')]) }} {{ $date->format('Y') }}
                г.
            </div>
        @endif
    </strong>
</div>

<br/>
<br/>

<div class="ta-center" style="margin-top: 30px">
    <table border="1">
        <tr>
            <td colspan="7">
                <div class="ta-center" style="margin-bottom: 10px">
                    <strong class="fs12">
                        <div class="fw" style="text-decoration: underline">Техническое задание на выполнение
                            лабораторных исследований по объекту:
                        </div>
                    </strong>
                </div>

                @if($arObjectTypes['water'] > 0)
                    <div class="fs20">{{ $arObjectTypesCount++ }}. Вода питьевая/вода природная (поверхностная, подземная)/вода сточная/вода
                        ливневая/воды талой/вода питьевая, расфасованная в ёмкости/вода техническая/вода бассейнов и
                        аквапарков/вода горячего водоснабжения/вода теплоэнергетической/снежный покров/лед/атмосферные
                        осадки;
                    </div>
                @endif

                @if($arObjectTypes['air'] > 0)
                    <div class="fs20">{{ $arObjectTypesCount++ }}. Воздух атмосферный/воздух рабочей зоны/воздух непроизводственных
                        помещений/промышленные выбросы/газопылевые потоки;
                    </div>
                @endif


                @if($arObjectTypes['earth'] > 0)
                    <div class="fs20">{{ $arObjectTypesCount++ }}. Почва/донные отложения/компост/кеки/осадки очистных сооружений/горные породы/пробы
                        растительного происхождения/отходы/твердые пробы/осадки/шламы/активный ил/грунты;
                    </div>
                @endif

                 @if($arObjectTypes['physical'] > 0)
                    <div class="fs20">{{ $arObjectTypesCount++ }}. Шум/вибрация/освещенность/тепловое излучение/радиационные факторы/тяжесть трудового
                        процесса/микроклимат/электромагнитные полея/лазерное излучение.
                    </div>
                @endif
            </td>
        </tr>
        <tr>
            <td width="5%">
                <div class="fs20"><span class="fw">№ п/п</span></div>
            </td>
            <td width="15%">
                <div class="fs20"><span class="fw">Точный адрес и место точки отбора/измерения</span></div>
            </td>
            <td width="10%">
                <div class="fs20"><span class="fw">Наименование точки отбора /измерения</span></div>
            </td>
            <td width="20%">
                <div class="fs20"><span class="fw">Наименование измеряемого показателя</span></div>
            </td>
            <td width="5%">
                <div class="fs20"><span class="fw">Кол-во</span></div>
            </td>
            <td width="25%">
                <div class="fs20"><span class="fw">Стоимость измерения (руб.)</span></div>
            </td>
            <td width="20%">
                <div class="fs20"><span class="fw">Примечание</span></div>
            </td>
        </tr>
        @php
            $i = 1;
            $rowspan = $evaluation->objects->flatMap->addresses->flatMap->points->flatMap->measures->count();
        @endphp
            @foreach($evaluation->objects as $object)
            @foreach($object->addresses as $address)
                @foreach($address->points as $point_i => $point)
                    @foreach($point->measures as $measure)
                        <tr>
                            <td>
                                <div class="fs20">{{ $i++ }}.</div>
                            </td>
                            @if($i == 2)
                                <td rowspan="{{ $rowspan }}">
                                    <div class="fs20">{{ $address->address }}</div>
                                </td>
                            @endif
                            @if($loop->first)
                                <td rowspan="{{ $point->measures->count() }}">
                                    @if(!empty($point->number))
                                        <div class="fs20">
                                            <span class="fw">№ {{ $point->number }}</span>
                                        </div>
                                    @endif
                                    <div class="fs20">{{ $point->name }}</div>
                                </td>
                            @endif
                            <td>
                                <div class="fs20">{{ $measure->measure->name }}</div>
                            </td>
                            <td>
                                <div class="fs20">{{ $measure->count }}</div>
                            </td>
                            <td>
                                <div class="fs20">{{ $measure->measure->cost }}</div>
                            </td>
                            <td>
                                <div class="fs20">{{ $measure->comment }}</div>
                            </td>
                        </tr>
                    @endforeach
                @endforeach
            @endforeach
        @endforeach


        @if(!empty($address->expanses))
            <tr>
                <td colspan="5">
                    <div class="fs20"><span class="fw">Командировочные расходы</span></div>
                </td>
                <td colspan="2">
                    <div class="fs20"><span class="fw">{{ tools()->cost_normalize($address->expanses) }}</span></div>
                </td>
            </tr>
        @endif

        @if(!empty($address->transport))
            <tr>
                <td colspan="5">
                    <div class="fs20"><span class="fw">Транспортные расходы</span></div>
                </td>
                <td colspan="2">
                    <div class="fs20"><span class="fw">{{ tools()->cost_normalize($address->transport) }}</span></div>
                </td>
            </tr>
        @endif

        @if(!empty($address->specialist))
            <tr>
                <td colspan="5">
                    <div class="fs20"><span class="fw">Выезд специалиста (x{{ $address->specialist['count'] }})</span></div>
                </td>
                <td colspan="2">
                    <div class="fs20"><span class="fw">{{ tools()->cost_normalize($address->specialist['total']) }}</span></div>
                </td>
            </tr>
        @endif


        <tr>
            <td colspan="5">
                <div class="fs20"><span class="fw">Итоговая стоимость работ (руб.)</span></div>
            </td>
            <td colspan="2">
                <div class="fs20"><span class="fw">{{ tools()->cost_normalize($evaluation->cost_total) }}</span></div>
            </td>
        </tr>


        <tr>
            <td colspan="7">
                <div class="fs20"><span class="fw">Официальные контактные данные заказчика:</span></div>
                <div class="fs20">юридический адрес: {{ $evaluation->portal->address_legal ?? '' }}</div>
                <div class="fs20">почтовый адрес: {{ $evaluation->portal->address_post ?? '' }}</div>
                <div class="fs20">электронная почта: {{ $evaluation->portal->email ?? '' }}</div>
                <div class="fs20">телефон: {{ $evaluation->portal->phone     ?? '' }}</div>
            </td>
        </tr>
        <tr>
            <td colspan="7">
                <div class="fs20"><span class="fw">Уполномоченный представитель Заказчика: </span> ФИО, должность,
                    телефон, адрес электронной почты.
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="7">
                <div class="ta-center">
                    <div class="fs20">
                        <span class="fw" style="text-decoration: underline">Порядок и сроки (календарный план) выполнения работ*</span>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="5">
                <div class="ta-center">
                    <div class="fs20"><span class="fw">Вид работ</span></div>
                </div>
            </td>
            <td colspan="2">
                <div class="ta-center">
                    <div class="fs20"><span class="fw">Срок выполнения (р/дней)</span></div>
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="fs20">1</div>
            </td>
            <td colspan="4">
                <div class="fs20">Проведение отбора/приема проб</div>
            </td>
            <td colspan="2" rowspan="3">
                <div class="fs20">
                    {{ $evaluation->period }}
                </div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="fs20">2</div>
            </td>
            <td colspan="3">
                <div class="fs20">Проведение анализа</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="fs20">3</div>
            </td>
            <td colspan="4">
                <div class="fs20">Оформление протокола анализа</div>
            </td>
        </tr>
        <tr>
            <td colspan="7">
                <div class="ta-center">
                    <div class="fs20"><span class="fw" style="text-decoration: underline">Результаты работ:</span></div>
                </div>
                <div class="fs20">1. Акт (-ы) отбора/приема проб;</div>
                <div class="fs20">2. Протоколы лабораторных анализов.</div>
            </td>
    </table>
</div>

<div style="margin-top: 20px">
    <div class="fs20">
        * - все работы по договору выполняются последовательно, в соответствии с календарным планом.
    </div>
</div>

<br/><br/>

<table width="100%" class="" style="border: 0; border-color: white" border="0">
    <tr>
        <td width="50%" style="border: 0; vertical-align: top; border-color: white" border="0">
            <div class="fw">
                <span class="fs20">Генеральный директор<br/>&nbsp;</span>
            </div>
            <br/>
            <br/>
            ______________________
            <div style="margin-left: 100px" class="fs16">м.п.</div>
        </td>
        <td width="50%" style="border: 0; vertical-align: top; border-color: white" border="0">
            <div class="fw">
                <span class="fs20">
                    Генеральный директор<br/>
                    ООО ХАЛ «РПН-Сфера»
                </span>
            </div>
            <br/>
            <br/>
            ______________________ Ю.А. Кортунов
            <div style="margin-left: 100px" class="fs16">м.п.</div>
        </td>
    </tr>
</table>
