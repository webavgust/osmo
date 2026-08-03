@extends('layouts.layout')
@section('title', 'Секретный уголок')
@section('body')
    <style>
        .inp_round {
            width: 80px;
            text-align: center;
            border: 0;
            padding: 0 0 0 14px;
            color: #67757c;
            background: #F4F4F4;
        }
    </style>

    <div class="main-wrapper">
        <div class="auth-wrapper d-flex no-block justify-content-center align-items-center" style="
          background: url(/assets/images/big/auth-bg2.jpg) no-repeat center
            center;
        ">
            <form method="POST">
                @csrf
                <div>
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="ms-4">Расчёт комплектации</h2>
                        <div class="me-3">
                            <input type="submit" value="Сохранить" class="py-0 fs-2">
                        </div>
                    </div>

                    <div class="card mx-3">
                        <div class="card-body">
                            <table class="table table-bordered">
                                <tr>
                                    <td width="1" class="fw-bold fs-5">A:</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <input name="a" type="number" min="0" class="form-control inp_round" id="a"
                                                   value="{{ $data['a'] ?? '' }}">
                                            <span class="ms-2" id="a_produced">= 0</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold fs-5">B:</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <input name="b" type="number" min="0" class="form-control inp_round" id="b"
                                               value="{{ $data['b'] ?? '' }}">
                                            <span class="ms-2" id="b_produced">= 0</span>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="px-2">
                            <table class="table table-bordered">
                                <!-- Товар -->
                                <tr>
                                    <th class="p-1 px-2" colspan="2">Товар</th>
                                    <th class="p-1 fs-2 text-center text-danger"><x-ui.badge.default type="danger" class="p-1 fs-1">X3</x-ui.badge.default> MASK1</th>
                                    <th class="p-1 fs-2 text-center text-danger">MASK1</th>
                                    <th class="p-1 fs-2 text-center text-primary">MASK 1 + 2</th>
                                    <th class="p-1 fs-2 text-center text-success">MASK2</th>
                                    <th class="p-1 fs-2 text-center text-success">MASK2 <x-ui.badge.default type="success" class="p-1 fs-1">X3</x-ui.badge.default></th>
                                </tr>
                                <tr>
                                    <td class="p-1 px-2" valign="middle" rowspan="3"
                                        style="border-right: 1px solid #e8eef3">
                                        Продажи
                                    </td>
                                    <td valign="middle" class="p-1 text-info fw-bold text-center">
                                        OZON
                                    </td>
                                    <td class="p-1"><input value="{{ $data['sale']['ozonM1X3'] ?? '' }}"  name="sale[ozonM1X3]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_ozonM1X3"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['ozonM1'] ?? '' }}"  name="sale[ozonM1]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_ozonM1"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['ozonM12'] ?? '' }}"  name="sale[ozonM12]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_ozonM12"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['ozonM2'] ?? '' }}"  name="sale[ozonM2]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_ozonM2"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['ozonM2X3'] ?? '' }}"  name="sale[ozonM2X3]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_ozonM2X3"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 text-primary fw-bold text-center">
                                        WB
                                    </td>
                                    <td class="p-1"><input value="{{ $data['sale']['wbM1X3'] ?? '' }}" name="sale[wbM1X3]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_wbM1X3"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['wbM1'] ?? '' }}" name="sale[wbM1]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_wbM1"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['wbM12'] ?? '' }}" name="sale[wbM12]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_wbM12"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['wbM2'] ?? '' }}" name="sale[wbM2]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_wbM2"></td>
                                    <td class="p-1"><input value="{{ $data['sale']['wbM2X3'] ?? '' }}" name="sale[wbM2X3]" type="number" min="0"
                                                           class="form-control inp_round" id="sale_wbM2X3"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 fs-2 fw-bold text-center">Итого</td>
                                    <td class="p-1 text-center fs-2 fw-bold text-dark">= <span id="sale_totalM1X3"></span></td>
                                    <td class="p-1 text-center fs-2 fw-bold text-dark">= <span id="sale_totalM1"></span></td>
                                    <td class="p-1 text-center fs-2 fw-bold text-dark">= <span id="sale_totalM12"></span></td>
                                    <td class="p-1 text-center fs-2 fw-bold text-dark">= <span id="sale_totalM2"></span></td>
                                    <td class="p-1 text-center fs-2 fw-bold text-dark">= <span id="sale_totalM2X3"></span></td>
                                </tr>

                                <tr>
                                    <td colspan="7" class="py-1 bg-light-secondary"/>
                                </tr>

                                <!-- Товар -->
                                <tr>
                                    <td class="p-1 px-2" valign="middle" rowspan="4"
                                        style="border-right: 1px solid #e8eef3">
                                        Остатки
                                    </td>
                                    <td valign="middle" class="p-1 text-red fw-bold text-center">
                                        СВОЙ
                                    </td>
                                    <td class="p-1"><input value="{{ $data['stock']['selfM1X3'] ?? '' }}" name="stock[selfM1X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_selfM1X3"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['selfM1'] ?? '' }}" name="stock[selfM1]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_selfM1"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['selfM12'] ?? '' }}" name="stock[selfM12]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_selfM12"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['selfM2'] ?? '' }}" name="stock[selfM2]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_selfM2"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['selfM2X3'] ?? '' }}" name="stock[selfM2X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_selfM2X3"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 text-info fw-bold text-center">
                                        OZON
                                    </td>
                                    <td class="p-1"><input value="{{ $data['stock']['ozonM1X3'] ?? '' }}" name="stock[ozonM1X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_ozonM1X3"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['ozonM1'] ?? '' }}" name="stock[ozonM1]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_ozonM1"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['ozonM12'] ?? '' }}" name="stock[ozonM12]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_ozonM12"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['ozonM2'] ?? '' }}" name="stock[ozonM2]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_ozonM2"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['ozonM2X3'] ?? '' }}" name="stock[ozonM2X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_ozonM2X3"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 text-primary fw-bold text-center">
                                        WB
                                    </td>
                                    <td class="p-1"><input value="{{ $data['stock']['wbM1X3'] ?? '' }}" name="stock[wbM1X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_wbM1X3"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['wbM1'] ?? '' }}" name="stock[wbM1]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_wbM1"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['wbM12'] ?? '' }}" name="stock[wbM12]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_wbM12"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['wbM2'] ?? '' }}" name="stock[wbM2]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_wbM2"></td>
                                    <td class="p-1"><input value="{{ $data['stock']['wbM2X3'] ?? '' }}" name="stock[wbM2X3]" type="number" min="0"
                                                           class="form-control inp_round" id="stock_wbM2X3"></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="fs-2 fw-bold p-1 fw-bold text-center">
                                        Итого
                                    </td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="stock_totalM1X3"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="stock_totalM1"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="stock_totalM12"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="stock_totalM2"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="stock_totalM2X3"></span></td>
                                </tr>

                                <tr>
                                    <td colspan="7" class="py-1 bg-light-secondary"/>
                                </tr>

                                <!-- Сборка -->
                                <tr>
                                    <td class="fw-bold p-1 px-2" valign="middle" rowspan="4 "
                                        style="border-right: 1px solid #e8eef3">
                                        Сборка
                                    </td>
                                    <td valign="middle" class="p-1 text-info fw-bold text-center">
                                        OZON
                                    </td>
                                    <td class="p-1 pt-2 text-center fs-2 text-info"><span id="assemble_ozonM1X3"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-info"><span id="assemble_ozonM1"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-info"><span id="assemble_ozonM12"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-info"><span id="assemble_ozonM2"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-info"><span id="assemble_ozonM2X3"></span></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 text-primary fw-bold text-center">
                                        WB
                                    </td>
                                    <td class="p-1 pt-2 text-center fs-2 text-primary"><span id="assemble_wbM1X3"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-primary"><span id="assemble_wbM1"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-primary"><span id="assemble_wbM12"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-primary"><span id="assemble_wbM2"></span></td>
                                    <td class="p-1 pt-2 text-center fs-2 text-primary"><span id="assemble_wbM2X3"></span></td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 fw-bold text-center">
                                        Собрать
                                    </td>
                                    <td class="p-1 text-center fw-bold text-danger">[<span id="assemble_totalM1X3"></span>]</td>
                                    <td class="p-1 text-center fw-bold text-danger bg-light-danger">[<span id="assemble_totalM1"></span>]</td>
                                    <td class="p-1 text-center fw-bold text-primary">[<span id="assemble_totalM12"></span>]</td>
                                    <td class="p-1 text-center fw-bold text-success bg-light-success">[<span id="assemble_totalM2"></span>]</td>
                                    <td class="p-1 text-center fw-bold text-success">[<span id="assemble_totalM2X3"></span>]</td>
                                </tr>
                                <tr>
                                    <td valign="middle" class="p-1 fw-bold text-center">
                                        Остатки 
                                    </td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="assemble_finalM1X3"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="assemble_finalM1"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="assemble_finalM12"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="assemble_finalM2"></span></td>
                                    <td class="p-1 fs-2 fw-bold text-center text-dark">= <span id="assemble_finalM2X3"></span></td>
                                </tr>
                            </table>
                        </div>

                    </div>

                </div>
            </form>

            <script>
                function calculate() {
                    // Получаем доступные компоненты
                    var availableA = parseInt($("#a").val()) || 0;
                    var availableB = parseInt($("#b").val()) || 0;

                    // Получаем данные о продажах
                    var sales = {
                        ozon: {
                            m1: parseInt($("#sale_ozonM1").val()) || 0,
                            m1x3: parseInt($("#sale_ozonM1X3").val()) || 0,
                            m2: parseInt($("#sale_ozonM2").val()) || 0,
                            m2x3: parseInt($("#sale_ozonM2X3").val()) || 0,
                            m12: parseInt($("#sale_ozonM12").val()) || 0,
                        },
                        wb: {
                            m1: parseInt($("#sale_wbM1").val()) || 0,
                            m1x3: parseInt($("#sale_wbM1X3").val()) || 0,
                            m2: parseInt($("#sale_wbM2").val()) || 0,
                            m2x3: parseInt($("#sale_wbM2X3").val()) || 0,
                            m12: parseInt($("#sale_wbM12").val()) || 0,
                        }
                    };

                    // Получаем данные об остатках
                    var stocks = {
                        self: {
                            m1: parseInt($("#stock_selfM1").val()) || 0,
                            m1x3: parseInt($("#stock_selfM1X3").val()) || 0,
                            m2: parseInt($("#stock_selfM2").val()) || 0,
                            m2x3: parseInt($("#stock_selfM2X3").val()) || 0,
                            m12: parseInt($("#stock_selfM12").val()) || 0,
                        },
                        ozon: {
                            m1: parseInt($("#stock_ozonM1").val()) || 0,
                            m1x3: parseInt($("#stock_ozonM1X3").val()) || 0,
                            m2: parseInt($("#stock_ozonM2").val()) || 0,
                            m2x3: parseInt($("#stock_ozonM2X3").val()) || 0,
                            m12: parseInt($("#stock_ozonM12").val()) || 0,
                        },
                        wb: {
                            m1: parseInt($("#stock_wbM1").val()) || 0,
                            m1x3: parseInt($("#stock_wbM1X3").val()) || 0,
                            m2: parseInt($("#stock_wbM2").val()) || 0,
                            m2x3: parseInt($("#stock_wbM2X3").val()) || 0,
                            m12: parseInt($("#stock_wbM12").val()) || 0,
                        }
                    };

                    // Рассчитываем общие суммы для отображения
                    $("#sale_totalM1").html(sales.ozon.m1 + sales.wb.m1);
                    $("#sale_totalM1X3").html(sales.ozon.m1x3 + sales.wb.m1x3);
                    $("#sale_totalM2").html(sales.ozon.m2 + sales.wb.m2);
                    $("#sale_totalM2X3").html(sales.ozon.m2x3 + sales.wb.m2x3);
                    $("#sale_totalM12").html(sales.ozon.m12 + sales.wb.m12);

                    $("#stock_totalM1").html(stocks.self.m1 + stocks.ozon.m1 + stocks.wb.m1);
                    $("#stock_totalM1X3").html(stocks.self.m1x3 + stocks.ozon.m1x3 + stocks.wb.m1x3);
                    $("#stock_totalM2").html(stocks.self.m2 + stocks.ozon.m2 + stocks.wb.m2);
                    $("#stock_totalM2X3").html(stocks.self.m2x3 + stocks.ozon.m2x3 + stocks.wb.m2x3);
                    $("#stock_totalM12").html(stocks.self.m12 + stocks.ozon.m12 + stocks.wb.m12);

                    // Рассчитываем чистый спрос с ограничением на двухмесячные продажи
                    var netDemand = {
                        m1: Math.max(0, Math.min(
                            (sales.ozon.m1 + sales.wb.m1) * 2,
                            ((sales.ozon.m1 + sales.wb.m1) - (stocks.self.m1 + stocks.ozon.m1 + stocks.wb.m1))  * 2
                        )),
                        m1x3: Math.max(0, Math.min(
                            (sales.ozon.m1x3 + sales.wb.m1x3) * 2,
                            ((sales.ozon.m1x3 + sales.wb.m1x3) - (stocks.self.m1x3 + stocks.ozon.m1x3 + stocks.wb.m1x3))  * 2
                        )),
                        m2: Math.max(0, Math.min(
                            (sales.ozon.m2 + sales.wb.m2) * 2,
                            ((sales.ozon.m2 + sales.wb.m2) - (stocks.self.m2 + stocks.ozon.m2 + stocks.wb.m2))  * 2
                        )),
                        m2x3: Math.max(0, Math.min(
                            (sales.ozon.m2x3 + sales.wb.m2x3) * 2,
                            ((sales.ozon.m2x3 + sales.wb.m2x3) - (stocks.self.m2x3 + stocks.ozon.m2x3 + stocks.wb.m2x3))  * 2
                        )),
                        m12: Math.max(0, Math.min(
                            (sales.ozon.m12 + sales.wb.m12) * 2,
                            ((sales.ozon.m12 + sales.wb.m12) - (stocks.self.m12 + stocks.ozon.m12 + stocks.wb.m12))  * 2
                        ))
                    };

                    // Применяем алгоритм распределения с учетом прибыли
                    var assemble = smartDistribution(availableA, availableB, netDemand, sales);

                    // Проверяем использование компонентов
                    const usedA = assemble.ozon.m1 + assemble.wb.m1 +
                        (assemble.ozon.m1x3 + assemble.wb.m1x3) * 3 +
                        (assemble.ozon.m12 + assemble.wb.m12) * 1;

                    const usedB = assemble.ozon.m2 + assemble.wb.m2 +
                        (assemble.ozon.m2x3 + assemble.wb.m2x3) * 3 +
                        (assemble.ozon.m12 + assemble.wb.m12) * 1;

                    console.log("Used A:", usedA, "of", availableA, "(", (usedA/availableA*100).toFixed(1), "%)");
                    console.log("Used B:", usedB, "of", availableB, "(", (usedB/availableB*100).toFixed(1), "%)");
                    console.log("M1: sales - ", sales.wb.m1 + sales.ozon.m1, ", stocks - ", stocks.self.m1 + stocks.wb.m1 + stocks.ozon.m1, ", assemble - ", assemble.wb.m1 + assemble.ozon.m1);
                    console.log("M1X3: sales - ", sales.wb.m1x3 + sales.ozon.m1x3, ", stocks - ", stocks.self.m1x3 + stocks.wb.m1x3 + stocks.ozon.m1x3, ", assemble - ", assemble.wb.m1x3 + assemble.ozon.m1x3);
                    console.log("M2: sales - ", sales.wb.m2 + sales.ozon.m2, ", stocks - ", stocks.self.m2 + stocks.wb.m2 + stocks.ozon.m2, ", assemble - ", assemble.wb.m2 + assemble.ozon.m2);
                    console.log("M2X3: sales - ", sales.wb.m2x3 + sales.ozon.m2x3, ", stocks - ", stocks.self.m2x3 + stocks.wb.m2x3 + stocks.ozon.m2x3, ", assemble - ", assemble.wb.m2x3 + assemble.ozon.m2x3);
                    console.log("M21: sales - ", sales.wb.m12 + sales.ozon.m12, ", stocks - ", stocks.self.m12 + stocks.wb.m12 + stocks.ozon.m12, ", assemble - ", assemble.wb.m12 + assemble.ozon.m12);

                    // Отображаем результаты
                    $("#assemble_ozonM1").html(assemble.ozon.m1 > 0 ? assemble.ozon.m1 : '');
                    $("#assemble_ozonM1X3").html(assemble.ozon.m1x3 > 0 ? assemble.ozon.m1x3 : '');
                    $("#assemble_ozonM2").html(assemble.ozon.m2 > 0 ? assemble.ozon.m2 : '');
                    $("#assemble_ozonM2X3").html(assemble.ozon.m2x3 > 0 ? assemble.ozon.m2x3 : '');
                    $("#assemble_ozonM12").html(assemble.ozon.m12 > 0 ? assemble.ozon.m12 : '');

                    $("#assemble_wbM1").html(assemble.wb.m1 > 0 ? assemble.wb.m1 : '');
                    $("#assemble_wbM1X3").html(assemble.wb.m1x3 > 0 ? assemble.wb.m1x3 : '');
                    $("#assemble_wbM2").html(assemble.wb.m2 > 0 ? assemble.wb.m2 : '');
                    $("#assemble_wbM2X3").html(assemble.wb.m2x3 > 0 ? assemble.wb.m2x3 : '');
                    $("#assemble_wbM12").html(assemble.wb.m12 > 0 ? assemble.wb.m12 : '');

                    var a_m1 = assemble.ozon.m1 + assemble.wb.m1;
                    var a_m1x3 = assemble.ozon.m1x3 + assemble.wb.m1x3;
                    var a_m2 = assemble.ozon.m2 + assemble.wb.m2;
                    var a_m2x3 = assemble.ozon.m2x3 + assemble.wb.m2x3;
                    var a_m12 = assemble.ozon.m12 + assemble.wb.m12;

                    $("#assemble_totalM1").html(a_m1 > 0 ? a_m1 : '');
                    $("#assemble_totalM1X3").html(a_m1x3 > 0 ? a_m1x3 : '');
                    $("#assemble_totalM2").html(a_m2 > 0 ? a_m2 : '');
                    $("#assemble_totalM2X3").html(a_m2x3 > 0 ? a_m2x3 : '');
                    $("#assemble_totalM12").html(a_m12 > 0 ? a_m12 : '');

                    $("#assemble_finalM1").html((stocks.self.m1 + stocks.ozon.m1 + stocks.wb.m1) + (assemble.ozon.m1 + assemble.wb.m1));
                    $("#assemble_finalM1X3").html((stocks.self.m1x3 + stocks.ozon.m1x3 + stocks.wb.m1x3) + (assemble.ozon.m1x3 + assemble.wb.m1x3));
                    $("#assemble_finalM2").html((stocks.self.m2 + stocks.ozon.m2 + stocks.wb.m2) + (assemble.ozon.m2 + assemble.wb.m2));
                    $("#assemble_finalM2X3").html((stocks.self.m2x3 + stocks.ozon.m2x3 + stocks.wb.m2x3) + (assemble.ozon.m2x3 + assemble.wb.m2x3));
                    $("#assemble_finalM12").html((stocks.self.m12 + stocks.ozon.m12 + stocks.wb.m12) + (assemble.ozon.m12 + assemble.wb.m12));

                    $("span#a_produced").html('= ' + usedA);
                    $("span#b_produced").html('= ' + usedB);
                }

                // Новый алгоритм распределения с учетом прибыли
                function smartDistribution(a, b, netDemand, sales) {
                    // Определение продуктов с их характеристиками
                    let products = [
                        { id: 'm1', a: 1, b: 0, profit: 60, max: netDemand.m1, toProduce: 0, profitPerUnit: 60 / 1 },
                        { id: 'm1x3', a: 3, b: 0, profit: 350, max: netDemand.m1x3, toProduce: 0, profitPerUnit: 350 / 3 },
                        { id: 'm2', a: 0, b: 1, profit: 60, max: netDemand.m2, toProduce: 0, profitPerUnit: 60 / 1 },
                        { id: 'm2x3', a: 0, b: 3, profit: 350, max: netDemand.m2x3, toProduce: 0, profitPerUnit: 350 / 3 },
                        { id: 'm12', a: 1, b: 1, profit: 150, max: netDemand.m12, toProduce: 0, profitPerUnit: 150 / 2 }
                    ];

                    // Сортировка по убыванию прибыли на компонент
                    products.sort((a, b) => {
                        if (b.profitPerUnit !== a.profitPerUnit) {
                            return b.profitPerUnit - a.profitPerUnit;
                        }
                        return a.id.localeCompare(b.id);
                    });

                    let remainingA = a;
                    let remainingB = b;

                    // Распределение компонентов по продуктам
                    for (let product of products) {
                        if (product.max <= 0) continue;

                        let maxByA = product.a > 0 ? Math.floor(remainingA / product.a) : Infinity;
                        let maxByB = product.b > 0 ? Math.floor(remainingB / product.b) : Infinity;
                        let maxByDemand = product.max;

                        let toProduce = Math.min(maxByA, maxByB, maxByDemand);

                        if (toProduce > 0) {
                            product.toProduce = toProduce;
                            remainingA -= toProduce * product.a;
                            remainingB -= toProduce * product.b;
                        }
                    }

                    // Распределение по каналам продаж
                    let result = {
                        ozon: { m1: 0, m1x3: 0, m2: 0, m2x3: 0, m12: 0 },
                        wb: { m1: 0, m1x3: 0, m2: 0, m2x3: 0, m12: 0 }
                    };

                    function distributeProduct(productId, totalToProduce) {
                        if (totalToProduce <= 0) return;

                        let totalSales = sales.ozon[productId] + sales.wb[productId];
                        if (totalSales > 0) {
                            const ratioOzon = sales.ozon[productId] / totalSales;
                            const forOzon = Math.floor(totalToProduce * ratioOzon);
                            const forWb = totalToProduce - forOzon;

                            result.ozon[productId] = forOzon;
                            result.wb[productId] = forWb;
                        } else {
                            result.ozon[productId] = Math.floor(totalToProduce / 2);
                            result.wb[productId] = totalToProduce - result.ozon[productId];
                        }
                    }

                    // Распределяем каждый продукт между каналами
                    for (let product of products) {
                        distributeProduct(product.id, product.toProduce);
                    }

                    return result;
                }
            </script>
        </div>
    </div>
@endsection

@section('js')
    <script>
        $(document).ready(function() {
            $("input").on("change keyup", function() {
                calculate();
            }) ;
            calculate();
        });
    </script>
@endsection
