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
            <div class="card">
                <div class="card-body">
                    <div id="chart"></div>
                </div>

                <div>
                    <x-ui.button.default btn_type="info" onclick="javascript:exportTransparentChart();">Скачать файл</x-ui.button.default>
                </div>
            </div>
        </div>
    </div>
@endsection


@section('js')
    @parent
    @php
        $arSources = config('graph.sources');
        $source = !empty($arSources[$n]) ? $arSources[$n] : $arSources[0];



         $data = collect($source)
         ->map(function($item) {
             $item[3] = $item[1] > 0 ? round($item[2] / $item[1], 2) : 0;
             return $item;
         });
         if($n == 1) {
             $data_reference = collect($arSources[0])->sort(function($a, $b) {
                 return $b[2] <=> $a[2];
            });
            $referenceOrder = $data_reference->pluck(0)->all();

             $data = $data->sortBy(function($item) use ($referenceOrder) {
                // Сортируем по позиции в referenceOrder (чем раньше в referenceOrder, тем выше приоритет)
                $index = array_search($item[0], $referenceOrder);
                return $index !== false ? $index : 999;
             });
         } elseif(!in_array($n, ['m', 'k', 'd', 'a'])) {
            $data = $data->sort(function($a, $b) {
                 return $b[2] <=> $a[2];
            });
         }
    @endphp
    <script>
        var options = {
            dataLabels: {
                enabled: true, // отключаем по умолчанию для линий
                style: {
                    fontSize: '20px', // Увеличиваем размер шрифта
                    fontWeight: 'bold',
                }
            },
            series: [
                {
                    name: 'Leeds',
                    type: 'column',
                    data: [{{ $data->pluck(1)->join(", ") }}],
                    color: '#6da9fc',
                },
                {
                    name: 'Amount',
                    type: 'column',
                    data: [{{ $data->pluck(2)->join(", ") }}],
                    color: '#7d0404',
                },
                {
                    name: 'Average cost',
                    type: 'line',
                    data: [{{ $data->pluck(3)->join(", ") }}],
                }
            ],
            legend: {
                show: true, // Полностью отключает легенду
                markers: {
                    hover: {
                        size: 0 // Убирает изменение размера маркера при наведении
                    }
                },
                onItemHover: {
                    highlightDataSeries: false
                },
                fontSize: '20px', // Увеличиваем шрифт в легенде
                labels: {
                    style: {
                        fontWeight: 'bold', // Жирный шрифт
                        fontFamily: 'Arial, sans-serif', // Шрифт
                    }
                }
            },
            tooltip: {
                enabled: false, // Оставляем подсказку при наведении на точки данных
                shared: false
            },
            chart: {
                animations: {
                    enabled: false // Иногда помогает отключить анимации
                },
                background: 'transparent',
                zoom: {
                    enabled: false, // Полностью отключает масштабирование
                },
                height: 700,
                type: 'line',
                stacked: false,
                foreColor: '#333',
                toolbar: {
                    show: false,
                    tools: {
                        download: true, // Включает кнопку скачивания
                        selection: true,
                        zoom: true,
                        zoomin: true,
                        zoomout: true,
                        pan: true,
                        reset: true
                    },
                },
                // Эффекты для 3D
                sparkline: {
                    enabled: false
                },
                dropShadow: {
                    enabled: true,
                    top: 3,
                    left: 2,
                    blur: 4,
                    opacity: 0.2
                }
            },
            stroke: {
                width: [1, 1, 4],
            },
            plotOptions: {
                bar: {
                    borderRadius: 5,
                    borderRadiusApplication: 'top',
                    columnWidth: '70%',
                    // 3D эффект для колонок
                },
            },
            grid: {
                borderColor: '#f1f1f1'
            },
            xaxis: {
                categories: [{!! $data->pluck(0)->map(function($item) {
                    return '"' . $item . '"';
                })->join(",") !!}],
                labels: {
                    rotate: -24, // Наклон на 30 градусов против часовой стрелки
                    rotateAlways: true, // Гарантирует, что подписи всегда повёрнуты
                    style: {
                        fontSize: '16px', // Размер шрифта
                        fontWeight: 'bold', // Жирность (normal, bold, 600, 700...)
                        fontFamily: 'Arial, sans-serif', // Шрифт (опционально)
                    },
                    html: true,
                    useHTML: true,
                    formatter: function(value) {
                        ret = value ? value.replace(/BR/g, '<br/>') : '';
                        console.log(value);
                        console.log(ret);
                        return ret;
                    }
                }
            },
            yaxis: [
                {
                    show: false,
                    seriesName: 'Leeds (pcs)',
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#008FFB'
                    },
                    labels: {
                        style: {
                            colors: '#008FFB',
                        }
                    },
                    title: {
                        text: "Leeds (pcs)",
                        style: {
                            color: '#008FFB',
                        }
                    },
                    tooltip: {
                        enabled: true
                    }
                },
                {
                    show: false,
                    seriesName: 'Amount (RUB)',
                    opposite: true,
                    axisBorder: {
                        show: true,
                        color: '#00E396'
                    },
                    labels: {
                        style: {
                            colors: '#00E396',
                            fontSize: '24px', // Увеличиваем размер шрифта для оси Y
                        }
                    },
                    title: {
                        text: "Amount (RUB)",
                        style: {
                            color: '#00E396',
                        }
                    },
                },
                {
                    show: false,
                    seriesName: 'Average cost',
                    opposite: true,
                    axisTicks: {
                        show: true,
                    },
                    axisBorder: {
                        show: true,
                        color: '#FEB019'
                    },
                    labels: {
                        style: {
                            colors: '#FEB019',
                        },
                    },
                    title: {
                        text: "Average cost (RUB)",
                        style: {
                            color: '#FEB019',
                        }
                    }
                },
            ],
        };

        var chart = new ApexCharts(document.querySelector("#chart"), options);
        chart.render();

        function exportTransparentChart() {
            html2canvas(document.querySelector("#chart"), {
                backgroundColor: null, // transparent background
            }).then(function(canvas) {
                // Convert canvas to image and trigger download
                var link = document.createElement('a');
                link.href = canvas.toDataURL('image/png');
                link.download = 'chart.png';
                link.click();
            });
        }


    </script>
@endsection
