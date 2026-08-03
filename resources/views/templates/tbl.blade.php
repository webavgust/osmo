@extends('layouts.layout')

@section('styles')
    @parent
@endsection


@section('content')
    {{--    <link href="assets/plugins/global/plugins.bundle.css" rel="stylesheet" type="text/css"/>--}}
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js" integrity="sha512-BNaRQnYJYiPSqHHDb58B0yaPfCu+Wgds8Gp/gU33kqBtgNS4tSPHuGibyoeqMV/TJlSKda6FXzoEyYGjTe+vXA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>


    <div class="container-fluid" id="proposal">
        <div class="row bg-white">
            <table class="table table-bordered mb-0">
                <tr>
                    <th>Страна</th>
                    <th>Компания</th>
                    <th>Имя</th>
                    <th>Фамилия</th>
                    <th>Отчество</th>
                    <th>Должность</th>
                    <th>Телефон</th>
                    <th>E-mail</th>
                    <th>Доп.данные</th>
                </tr>
                @foreach(\App\Modules\Bitrix\CrmDeal\Models\CrmDeal::all() as $deal)
                    <tr>
                        <td>1</td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div> 
@endsection
