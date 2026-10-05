<?php
// Отрисовать PDF одного КП в файл: php render.php {id} {default|client_discount} {файл.html}
use App\Modules\Pub\Proposal\Models\Proposal;
use Illuminate\Http\Request;

require getcwd() . '/vendor/autoload.php';
$app = require getcwd() . '/bootstrap/app.php';
$http = $app->make(Illuminate\Contracts\Http\Kernel::class);
$app->instance('request', Request::create('/', 'GET', [], ['ui_theme' => 'metronic']));
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
auth()->loginUsingId(1);
$app->instance(\App\Http\Middleware\VerifyCsrfToken::class, new class {
    public function handle($request, $next) { return $next($request); }
});
$p = Proposal::find((int) $argv[1]);
$vdata = [];
foreach ($p->variants as $v) $vdata[$v->id] = ['name' => '', 'cameras' => '10', 'period_po' => '', 'period_pk' => ''];
$req = Request::create(route('proposal.report', [$p, $p->iteration], false), 'POST', [
    'active' => $p->variants->pluck('id')->all(), 'language' => $argv[4] ?? 'ru', 'template' => $argv[2] ?? 'default',
    'show_unprocessed' => 1, 'variant' => $vdata, 'form' => ['contact' => ''],
], ['ui_theme' => 'metronic']);
$res = $http->handle($req);
file_put_contents($argv[3], $res->getContent());
echo 'HTTP ', $res->getStatusCode(), ', ', strlen($res->getContent()), " байт, вариантов ", $p->variants->count(), ': ', $p->variants->pluck('id')->implode(','), "\n";
