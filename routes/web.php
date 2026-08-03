<?php

use App\Modules\Pub\Calendar\Repositories\CalendarRepository;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\DocumentNumber\Services\DocumentNumberService;
use App\Modules\Pub\Evaluation\Events\EvaluationChangeStatus;
use App\Modules\Pub\Evaluation\Events\EvaluationUpdateEvent;
use App\Modules\Pub\OrderTask\Events\OrderTaskStartWorking;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Report\Services\ReportSpecService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\Visit\Jobs\VisitCheckExpiredJob;
use App\Modules\Pub\Visit\Models\Visit;
use App\Modules\Pub\Visit\Repository\VisitRepository;
use App\Modules\Pub\Visit\Services\VisitService;
use App\Services\Portal\Events;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use niklasravnsborg\LaravelPdf\Facades\Pdf;
use PDFShift\PDFShift;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
| ы
*/
//
Route::get('/scenario_unique', function() {
    $arRating = Cache::get('scenarion_unique1');
    if(empty($arRating)) {
        $groups = Proposal::orderBy('id', 'asc')->pluck('group');
        $arID = collect();
        foreach ($groups as $group) {
            $proposal = Proposal::where('group', $group)->orderBy('id', 'desc')->first();
            $arID[] = $proposal->id;
        }
        $arProposalID = $arID->unique()->sort();


        $scenarios = \App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario::whereHas('proposal_variant.proposal', function ($builder) use ($arProposalID) {
            $builder->whereIn('id', $arProposalID);
        })->whereHas('scenario')
            ->get();

        // получим


        // очистим дубли
        $arProcessed = [];
        $arData = collect();
        $arStat = ['mnemonic_name' => [], 'scenario' => []];
        foreach ($scenarios as $scenario) {
            $proposal = $scenario->proposal_variant->proposal;
            $key = $proposal->id . '_' . $scenario->scenario_id . ' ' . $scenario->mnemonic_name;
            if (!empty($arProcessed[$key])) continue;

            $arProcessed[$key] = 1;
            $is_mnemonic = !empty($scenario->mnemonic_name);

            $line = [
                'id' => $scenario->scenario->id ?? 0,
                'is_mnemonic' => $is_mnemonic,
                'scenario_id' => $scenario->scenario->id,
                'proposal_id' => $proposal->id,
                'name' => $scenario->scenario->name ?? '',
                'mnemonic_name' => $scenario->mnemonic_name,
                'manager' => $proposal->manager->full_name,// . ' (' . $proposal->group . ')',
            ];
            $arData[] = $line;
        }

        $scenarios = $arData->pluck('scenario_id')->unique();
        foreach ($scenarios as $scenario_id) {
            $scenario_name = \App\Modules\Pub\Scenario\Models\Scenario::find($scenario_id)->first()['name'];
            $no_mnemonic = $arData->where('is_mnemonic', 0)->where('scenario_id', $scenario_id);
            if ($no_mnemonic->isNotEmpty()) {
                $ref = $no_mnemonic->first();
                $arRating[] = [
                    'name' => $ref['name'] . ' (' . $scenario_id . ')',
                    'count' => $no_mnemonic->count()
                ];
            }

            $mnemonic = $arData->where('is_mnemonic', 1)->where('scenario_id', $scenario_id);
            if ($mnemonic->isNotEmpty()) {

                $unique_names = $mnemonic->pluck('mnemonic_name')->unique();
                foreach($unique_names as $uname) {
                    $ref = $mnemonic->where('mnemonic_name', $uname);
                    $arRating[] = [
                        'name' => $uname,
                        'base_name' => $mnemonic->first()['name'] . ' (' . $scenario_id . ')',
                        'count' => $ref->count(),
                        'users' => implode(" / ", $ref->pluck('manager')->unique()->toArray()),
                    ];
                }
            }
        }
        Cache::set('scenarion_unique', $arRating, 3600);
    }
    $arRating = collect($arRating)->sortByDesc(fn($item) => $item['count'])->toArray();
    return view('scenario_unique', [
        'rating' => $arRating
    ]);



    dd($arData, $arData->where('is_mnemonic', 1));

});

Route::get('/mp_pdf', function(\Illuminate\Http\Request $request) {
    $data = $request->all();
    $url = "http://www.mp-mp.ru/tools/upd_pdf.php?number={$data['number']}&crc={$data['crc']}&firm={$data['firm']}";
    PDFShift::setApiKey('sk_836afa4b994e7f5754076b110a44c5aa84168d5d');
    $pdfContent = PDFShift::convertTo($url, [
        'landscape' => 1,
    ]);

    print $pdfContent;
})->middleware('no-debugbar');

Route::get('/mp_qr', function(\Illuminate\Http\Request $request) {
    $data = base64_decode($request->input('content'));

    $renderer = new \BaconQrCode\Renderer\ImageRenderer(
        new \BaconQrCode\Renderer\RendererStyle\RendererStyle(400),
        new \BaconQrCode\Renderer\Image\SvgImageBackEnd()
    );
    $writer = new \BaconQrCode\Writer($renderer);
    $qr = $writer->writeString($data);

    return $qr;
})->middleware('no-debugbar');

Route::get('/graph', function(\Illuminate\Http\Request $request) {
    $n = $request->get('n') ?? 0;
    return view('graph', [
        'n' => $n,
    ]);
});

Route::get('/tbl', function(\Illuminate\Http\Request $request) {
    return view('tbl');
});



if (config('app.env') == 'development') {
    Route::get('/travel', action: function(\Illuminate\Http\Request $request) {
        return view('travel', [
            'step' => $request->input('step') ?? null
        ]);
    });


    Route::get('/temp/scenarios', function() {
        return view('pub.temp.scenarios');
    });

    Route::any('/wb', function(\Illuminate\Http\Request $request) {

        if($request->exists('a')) {
            // save
            $data = json_encode($request->post());
            Cache::set('wb', $data);
        }


        $cache = Cache::get('wb');
        $data = json_decode($cache, 1);


        return view('wb', ['data' => $data]);
    });


    Route::get('/valentine/{mode}', function($mode) {

        $img = match($mode) {
            "a" => "/images/valentine/1.png",
            "a2" => "/images/valentine/3.png",
            "m" => "/images/valentine/2.png",
            default => abort(404)
        };

       return view('valentine', ['img' => $img]);
    });
    Route::get('/test', function() {
        $service = new \App\Modules\Bitrix\Dashboard\Services\DashboardDataService();
        dd($service->anna_text());


        \App\Modules\Pub\Currency\Services\CurrencyService::updateRates();
        dd("!");

        try {
            $snappy = app('snappy.pdf'); // Knp\Snappy\Pdf
            $snappy->setBinary(config('snappy.pdf.binary'));
            $snappy->setTemporaryFolder(\Illuminate\Support\Facades\Storage::path('temp')); // убедитесь в правах
            $snappy->setOption('enable-local-file-access', true);

            $html = '<!doctype html><html><body><h1>OK</h1></body></html>';
            $out = $snappy->getOutputFromHtml($html); // бросит исключение, если бинарь ругнется
            \Storage::put('temp/snappy-test.pdf', $out);

            return response('saved to storage/temp/snappy-test.pdf');
        } catch (\Throwable $e) {
            logger()->error('wkhtmltopdf failed: '.$e->getMessage());
            return 'ERROR: '.$e->getMessage();
        }

        dd("!!");
        $data = ReportSpecService::specs();

        $view = view('pub.report.specs', [
            'companies'     => CompanyRepository::getAll(),
            'data'          => $data,
            'ignore_layout' => true, // если используете в шаблоне
        ]);

        $sections = $view->renderSections();          // получаем все секции
        $html = $sections['content'] ?? $view->render(); // берем 'content' или весь html как fallback
        $pdf = Pdf::loadHTML($html);
        return $pdf->stream('report.pdf');

        dd("!!");
        foreach(\App\Modules\Pub\Scenario\Models\Scenario::whereNot('id', 92)->get() as $s) {
            $json = [1 => ['y' => (int)$s->cost_year, 'u' => (int)$s->cost_unlimited]];
            $s->update(['cost_rules' => $json]);
        }
        dd(\App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository::getDealWithIssues());
        $deal = \App\Modules\Bitrix\CrmDeal\Models\CrmDeal::find(429);
        dd($deal->customer);

        dd(\App\Modules\Bitrix\CrmCompany\Models\CrmCompany::where('id', 11)->first()->deals);

        dd(tools()->parseNumberFromString("Итого: 123 123р."));
        $deals = DB::connection('bitrix')
            ->table('crm_deal')
            ->join('crm_deal_uf', 'crm_deal.id', '=', 'crm_deal_uf.deal_id')
            ->where('crm_deal_uf.uf_crm_1718977752420', '!=', '')
            ->where('crm_deal.currency_id', '!=', 'RUB')
            ->first();

        dd($deals);

        dd();

//        $ar = [
//            1 => 'detection-transport|classification-quality-transport|classification-car-angle|classification-car-model',
//            2 => 'detection-transport',
//            3 => 'detection-transport',
//            4 => 'detection-transport|detection-license-plate|recognition-license-plate',
//            5 => 'detection-transport|classification-transport-special',
//            6 => 'detection-transport|classification-transport-special',
//            7 => 'detection-transport-construction|detection-transport',
//            8 => 'detection-wagon|detection-wagon-number|recognition-wagon-number',
//            102 => 'segmentation-under-wagon-part',
//            9 => 'detection-transport|classification-car-color',
//            98 => 'detection-transport|classification-quality-transport|classification-car-angle',
//            12 => 'detection-person-head|detection-weapon',
//            13 => 'detection-person-part|classification-head-wear|classification-body-wear|classification-foot-wear|classification-hand-wear|detection-neuralcode',
//            22 => 'recognition-pipeline-face',
//            23 => 'recognition-pipeline-face',
//            104 => 'detection-person-head|detection-ppe-safety-rope',
//            25 => 'detection-person-head|classification-hand-gesture',
//            26 => 'detection-person-head',
//            27 => 'detection-person-head',
//            28 => 'detection-person-head',
//            29 => '',
//            30 => 'detection-person-head|classification-person-pose',
//            31 => 'detection-person-head|regression-person-keypoint',
//            32 => 'recognition-pipeline-face|script nodes',
//            33 => 'recognition-pipeline-face|script nodes',
//            34 => 'detection-face|classification-face-age-gender-race',
//            35 => 'recognition-pipeline-face',
//            36 => 'detection-face|classification-face-emotion',
//            37 => 'detection-person-head|detection-face|classification-quality-face|recognition-pipeline-face|detection-weapon',
//            38 => 'detection-person-head|classification-head-angle|detection-weapon',
//            39 => 'recognition-pipeline-face|classification-quality-face',
//            40 => 'recognition-pipeline-face|facedetsimple',
//            106 => 'detection-person-head',
//            41 => 'detection-neuralcode',
//            42 => 'detection-apriltag',
//            43 => 'detection-object',
//            44 => 'detection-object',
//            45 => 'detection-bottle',
//            46 => 'detection-weapon',
//            47 => 'detection-fire-smoke',
//            48 => 'detection-fire-smoke',
//            49 => 'detection-sack-on-person',
//            50 => 'detection-spool',
//            51 => 'detection-rzd-objects-railway',
//            53 => 'detection-plastic-pipe',
//            54 => 'detection-plastic-pipe',
//            56 => 'detection-seat-belt',
//            57 => 'detection-electical-station-element',
//            58 => 'segmentation-conveyor-belt-defect',
//            60 => 'segmentation-obstacles',
//            61 => 'detection-fire-smoke|detection-person-head',
//            62 => 'segmentation-cow',
//            63 => 'segmentation-cow',
//            64 => 'detection-pig',
//            65 => 'detection-pig|detection-pig-carcass|classification-pig-gender|classification-pig-castration',
//            112 => 'detection-pig',
//            66 => 'detection-image-artefact',
//            67 => 'estimation-lighting-changing',
//            68 => 'detection-motion',
//            71 => 'recognition-pipeline-face|classification-face-age-gender-race',
//            73 => 'embedding-image',
//            76 => 'estimation-temperature',
//        ];
//
//        foreach($ar as $scenario_id => $str) {
//            $arNeuroCodes = explode("|", $str);
//
//            $scenario = \App\Modules\Pub\Scenario\Models\Scenario::findOrFail($scenario_id);
//            $arNeuro = \App\Modules\Pub\Neuroservice\Models\Neuroservice::whereIn('name', $arNeuroCodes)->get();
//
//            $scenario->neuroservices()->sync($arNeuro);
//            $scenario->update(['active' => 1]);
//        }
//
        $ar = [
            37 => 'Прокторинг для видеоконференций (лицо, взгляд, персона, телефон)',
            1 => 'Распознавание марки модели машин',
            102 => 'Локализация частей под вагоном',
            103 => 'Распознавание номера судна',
            104 => 'Детекция страховочного тросса',
            106 => 'Контроль социальной дистанции',
            107 => 'Подсчет загрузки коробок в автотранспорт',
            109 => 'Контроль сроков постройки корабля',
            11 => 'Локализация самолетов со спутника',
            110 => 'Определение государства в составе агломерации',
            111 => 'Определение состава зерна',
            112 => 'Распознавания номеров на свиняьх для подсчета партий',
            12 => 'Открытое ношение оружия',
            13 => 'Чистые комнаты (SmartMirror)',
            2 => 'Подсчет машин, пересекающих линию / в зоне (нарушение периметра)',
            22 => 'Открыть дверь по лицу',
            23 => 'Вход определенного лица / группы',
            25 => 'Распознавание жестов о помощи',
            26 => 'Подсчет людей, пересекающих линию / в зоне',
            27 => 'Подсчет очередей',
            28 => 'Контроль учета времени человека за рабочим местом',
            29 => 'Локализация присутствия / отсутствия человека в зоне на ИК кадре',
            3 => 'Контроль парковки автомобилей',
            30 => 'Подозрительная активность в зоне, основанная на позе (длительное стояние, лежание, сидение человека, бег)',
            31 => 'Оценка осанки человека (по ключевым точкам)',
            32 => 'Кластеризация лиц',
            33 => 'Динамическое создание профилей людей',
            34 => 'Распознавание возвраста пола расы человека по лицу',
            35 => 'Рабочее время по лицу',
            36 => 'Распознавание эмоций человека по лицу',
            38 => 'Прокторинг для аудиторий (поворот головы, лицо, телефон)',
            39 => 'Подсчет людей на вход/выход по лицу',
            4 => 'Распознавание ГРЗ',
            40 => 'Использование распознавания лиц без платформы (только нейросервис)',
            41 => 'Локализация и распознавание цифровых маркировок (нейрокоды)',
            42 => 'Локализация и распознавание цифровых маркировок (1D, 2D, Apriltag)',
            43 => 'Обнаружение оставленных объектов',
            44 => 'Детекция оставленных сумок',
            45 => 'Детекция бутылок',
            46 => 'Детекция мобильных телефонов',
            47 => 'Детекция огня и дыма',
            48 => 'Детекция огня и дыма на ИК кадрах',
            49 => 'Подсчет загрузки мешков в вагоны',
            5 => 'Спецтранспорт (скорая, полиция, пожарные)',
            50 => 'Детекция железных бабин при погрузке на платформу',
            51 => 'Контроль погрузки и разгрузки рельсоукладчиков: пакет шпал + рельсовый узел',
            52 => 'Детекция зубьев ковша ',
            53 => 'Подсчет пластиковых труб на палете',
            54 => 'Определения прокатных труб',
            55 => 'Детекция осей грузовика ',
            56 => 'Детекция ремня безопасноти на водителе',
            57 => 'Детекция электрооборудования на электроподстанции',
            58 => 'Дефекты конвейерной ленты',
            59 => 'Обнаружение элементов капитального строительства на спутниковых снимках',
            6 => 'Открыть шлагбаум и пропускать спецтранспорт',
            60 => 'Контроль чистоты проходов',
            61 => 'Обнаружение факта курения в помещении',
            62 => 'Подсчет коров с дрона на пастбищах',
            63 => 'Уровень откорма коров',
            64 => 'Подсчет свиней',
            65 => 'Определение пола свиней и типа кастрации',
            7 => 'Строительная техника',
            71 => 'Время просмотра рекламы или витрины',
            72 => 'Подсчет рекламы в спортивных трансляциях',
            73 => 'Оценка сходства изображений',
            74 => 'Проверка цен по распознанным ценникам',
            75 => 'Детекция пиццы и ее дефектов',
            76 => 'Распознавание температуры объекта',
            77 => 'Распознавание документов',
            78 => 'Распознавание порнографических материалов',
            8 => 'Номер контейнер, платформы, цистерны и т.п.',
            86 => 'Медицинские маски',
            9 => 'Определение цвета авто транспорта',
            98 => 'Определение скорости движения авто транспорта',
        ];

        foreach($ar as $scenario_id => $name) {
            $scenario = \App\Modules\Pub\Scenario\Models\Scenario::findOrFail($scenario_id);
            $scenario->update(['name' => $name]);
        }
        dd("...");
    });
}


Route::prefix('api')->group(function () {
    Route::post('/hook', [\App\Services\Portal\Hook::class, 'hook'])->name('portal.hook');
});

Route::middleware('auth')->group(function () {
    Route::get('/', function () {
        return redirect(route('dashboard.index'));
    });
    Route::get('/logout', [\App\Modules\Pub\User\Controllers\UserController::class, 'logout'])->name('logout');
    Route::get('/user/list', '\App\Modules\Pub\User\Controllers\UserController@view')->name('users.list');
});

Route::middleware('guest')->name('auth.')->group(function () {
    Route::post('/auth', '\App\Modules\Pub\User\Controllers\UserController@auth');
    Route::get('/auth', '\App\Modules\Pub\User\Controllers\UserController@form')->name('form');
});


