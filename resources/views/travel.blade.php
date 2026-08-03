@extends('layouts.layout_short')
@section('title', 'Секретный уголок')
@section('body')
    @php
        \Illuminate\Support\Facades\Log::info('TICK: ' . ($step ?? 1));
    @endphp
    <div class="main-wrapper">
        <!-- -------------------------------------------------------------- -->
        <!-- Preloader - style you can find in spinners.css -->
        <!-- -------------------------------------------------------------- -->

        <div class=" auth-wrapper d-flex no-block justify-content-center align-items-center "  style="
          background: url(/assets/images/big/auth-bg2.jpg) no-repeat center
            center;
        ">
            <div class="card mx-3">
                <div class="card-body">
                    @switch($step)
                        @case('2')
                            <h2>
                                Ничего не замечаете
                                <x-ui.icon.regular icon="fa-question" class="text-dark"/>
                                <x-ui.icon.regular icon="fa-question" class="text-success"/>
                                <x-ui.icon.regular icon="fa-question" class="text-info"/>
                                <x-ui.icon.regular icon="fa-question" class="text-orange"/>
                            </h2>
                            <div class="mt-4">
                                Согласен... на первый взгляд обычный, ничем не примечательный вечерний вид. Но как же тоскливо на это смотреть, не находишь?
                            </div>
                            <x-ui.a.default btn_type="info" href="/travel?step=3" class="mt-3">Да, чёртова осень 😿</x-ui.a.default>
                            @break
                        @case('3')
                            <h2>
                                Да, я знаю, кот....
                            </h2>
                            <div class="mt-4">
                                <p>Но ведь сердечко греет любовь!</p>
                                <p>А ещё через каких-то жалких семь месяцев мы снова увидим вечернее солнце!!!</p>
                                <x-ui.a.default btn_type="info" href="/travel?step=4" class="mt-3">Звучит так себе....</x-ui.a.default>
                            </div>
                            @break
                        @case('4')
                            <h2>
                                А у меня есть решение!
                            </h2>
                            <div class="mt-4">
                                <x-ui.a.default btn_type="info" href="/travel?step=5" class="mt-3 w-100">Удиви меня!</x-ui.a.default>
                            </div>
                            @break
                        @case('5')
                            <h2>
                                А у меня есть решение!
                            </h2>
                            <div class="mt-4">
                                <p>Нравится?</p>
                                <p>
                                    <img src="/temp/2.webp" class="w-100">
                                </p>


                                <x-ui.a.default btn_type="info" href="/travel?step=6" class="mt-3 w-100">ХОЧУ!</x-ui.a.default>
                            </div>
                            @break
                        @case('6')
                            <h2>
                                Ну так полетели!
                            </h2>
                            <div class="mt-4">
                                <p>
                                    <img src="/temp/1.png" class="w-100">
                                </p>
                            </div>
                            @break
                        @default
                            <h2>Привет!</h2>
                            <div class="mt-4">
                                <p>
                                    Давай я сыграю с тобой в игру!
                                </p>
                                <p>Оставь все свои делишки. А теперь подойди и открой шторы.</p>
                                <p>Выгляни в окошко и оглядись...</p>
                            </div>
                        <x-ui.a.default btn_type="info" href="/travel?step=2">Дальше</x-ui.a.default>
                        @endswitch
                </div>
            </div>


        </div>
@endsection
