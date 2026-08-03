
<div class="card mb-0 h-100">
    <div class="border-bottom title-part-padding d-flex justify-content-between align-items-center">
        <h4>Воронка в разрезе сферы деятельности заказчика</h4>
    </div>
    <div class="card-body p-0 text-center text-dark fw-bolder py-4 pt-4">
        <div id="chart"></div>
    </div>
</div>

<script>
    var options = {
        series: [{{ $graph->values()->join(", ") }}],
        labels: [{!! $graph->keys()->map(function($item) {
                    return '"' . $item . '"';
                })->join(",") !!}],
        chart: {
            type: 'donut',
        },
        stroke: {
            colors: ['#fff']
        },
        fill: {
            opacity: 0.8
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: '100%'
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var chart = new ApexCharts(document.querySelector("#chart"), options);
    chart.render();

</script>
