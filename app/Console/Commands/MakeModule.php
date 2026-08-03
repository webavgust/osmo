<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeModule extends Command
{
    private $files;
    private $module_name;
    private $module_scope;
    private $module_path;
    private $modules_dir;
    private $replace;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:module {name}
           {scope?}
           {--all}
           {--migration}
           {--controller}
           {--model}
           {--api}
           {--service}
           {--repo}
       ';

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->files = $filesystem;
        $this->module_scope = 'Pub';   // по умолчанию
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if(!empty($this->argument('scope'))) {
            $this->module_scope = $this->argument('scope');
        }

        # User/a/b/c/Test -> Test
        $name = $this->argument('name');

//        dd($this->module_scope, $name);

        // запомним пути
        # \User\Aaaa\Bbbb\Ccc\Test
        $this->module_path = collect(explode('/', ltrim($name, '/')))->map(function ($part) {return Str::studly(str_replace(['_', '-'], '', $part));})->implode('/');

        # Test
        $this->module_name = Str::singular(Str::studly(class_basename($name)));    # Единственное число, StudlyString  #Test
        # \app\Modules\{Pub}
        $this->modules_dir = app_path("Modules/{$this->module_scope}"); # \app\Modules\Pub\Test
        $this->replace = collect([
            "#SCOPE#" => $this->module_scope,
            "#SCOPE_LOWERCASE#" => Str::lower($this->module_scope),
            "#MODULE_PATH#" => $this->module_path,
            "#MODULE_NAME#" => $this->module_name,
            "#MODULE_NAME_LOWERCASE#" => Str::lower($this->module_name),
            "#MODULE_NAME_PL_SNAKE#" => Str::plural(Str::snake(lcfirst($this->module_name), '-')),
            "#MODULE_PATH_ROUTE#" => Str::replace('\\', '/', Str::lower(Str::plural(lcfirst($this->module_path)))),
            "#MODULE_PATH_ROUTE_DOTS#" => (($this->module_scope == config('modular.groupWithoutPrefix') ? '' : Str::lower($this->module_scope) . '.') . Str::replace('/', '.', Str::replace('\\', '/', Str::lower(Str::plural(lcfirst($this->module_path)))))),
        ]);

        if ($this->option('all')) {
            $this->input->setOption('model', true);
            $this->input->setOption('service', true);
            $this->input->setOption('repo', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('api', true);
            $this->input->setOption('migration', true);
//            $this->input->setOption('view', true);
        }

        if ($this->option('model')) $this->createModel();
        if ($this->option('service')) $this->createService();
        if ($this->option('repo')) $this->createRepository();
        if ($this->option('controller')) $this->createController();
        if ($this->option('api')) $this->createApiController();
        if ($this->option('migration')) $this->createMigration();
    }

    private function createModel()
    {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/model.stub'));
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);

        $modelDir = $this->modules_dir . '/' . $this->module_path . '/Models/';
        $this->createDirectory($modelDir);
        $path = $modelDir . $this->module_name . '.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] Модель: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        if ($this->files->exists($path)) {
            $this->info('[V] Модель: успешно создана');
            return true;
        } else {
            $this->error('[X] Модель: Не удалось создать');
            return false;
        }
    }

    private function createService()
    {
        if($this->option('repo')) {
            $stub = $this->files->get(base_path('resources/stubs/MakeModule/service_with_repo.stub'));
        } else {
            $stub = $this->files->get(base_path('resources/stubs/MakeModule/service.stub'));
        }
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);

        $serviceDir = $this->modules_dir . '/' . $this->module_path . '/Services/';
        $this->createDirectory($serviceDir);
        $path = $serviceDir . $this->module_name . 'Service.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] Сервис: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        if ($this->files->exists($path)) {
            $this->info('[V] Сервис: успешно создан');
            return true;
        } else {
            $this->error('[X] Сервис: Не удалось создать');
            return false;
        }
    }

    private function createRepository()
    {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/repository.stub'));
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);

        $repoDir = $this->modules_dir . '/' . $this->module_path . '/Repositories/';
        $this->createDirectory($repoDir);
        $path = $repoDir . $this->module_name . 'Repository.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] Репозиторий: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        if ($this->files->exists($path)) {
            $this->info('[V] Репозиторий: успешно создан');
            return true;
        } else {
            $this->error('[X] Репозиторий: Не удалось создать');
            return false;
        }
    }

    private function createController() {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/controller.stub'));
        $arUse = $arConstruct = [];
        if($this->option('repo')) {
            $arUse[] = 'use App\Modules\#SCOPE#\#MODULE_PATH#\Repositories\#MODULE_NAME#Repository;';
            $arConstruct[] = '        protected $repo = new #MODULE_NAME#Repository(),';
        }
        if($this->option('service')) {
            $arUse[] = 'use App\Modules\#SCOPE#\#MODULE_PATH#\Services\#MODULE_NAME#Service;';
            $arConstruct[] = '        protected $service = new #MODULE_NAME#Service(),';
        }
        $stub = Str::replace("#USE#", !count($arUse) ? null : implode("\n", $arUse) . PHP_EOL, $stub);
        $stub = Str::replace("#CONSTRUCT#", !count($arConstruct) ? null : implode("\n", $arConstruct), $stub);
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);

        $conrollerDir = $this->modules_dir . '/' . $this->module_path . '/Controllers/';
        $this->createDirectory($conrollerDir);
        $path = $conrollerDir . $this->module_name . 'Controller.php';


        if ($this->files->exists($path)) {
            $this->warn('[!] Контроллер: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        $this->updateModularConfig();
        $this->createRoutes();

        if ($this->files->exists($path)) {
            $this->info('[V] Контроллер: успешно создан');
            return true;
        } else {
            $this->error('[X] Контроллер: Не удалось создать');
            return false;
        }

    }
    private function createApiController() {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/api_controller.stub'));
        $arUse = $arConstruct = [];
        if($this->option('repo')) {
            $arUse[] = 'use App\Modules\#SCOPE#\#MODULE_PATH#\Repositories\#MODULE_NAME#Repository;';
            $arConstruct[] = '        protected $repo = new #MODULE_NAME#Repository(),';
        }
        if($this->option('service')) {
            $arUse[] = 'use App\Modules\#SCOPE#\#MODULE_PATH#\Services\#MODULE_NAME#Service;';
            $arConstruct[] = '        protected $service = new #MODULE_NAME#Service(),';
        }
        $stub = Str::replace("#USE#", !count($arUse) ? null : implode("\n", $arUse) . PHP_EOL, $stub);
        $stub = Str::replace("#CONSTRUCT#", !count($arConstruct) ? null : implode("\n", $arConstruct), $stub);
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);

        $conrollerDir = $this->modules_dir . '/' . $this->module_path . '/Controllers/Api/';
        $this->createDirectory($conrollerDir);
        $path = $conrollerDir . 'Api' . $this->module_name . 'Controller.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] API Контроллер: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        $this->updateModularConfig();
        $this->createApiRoutes();

        if ($this->files->exists($path)) {
            $this->info('[V] API Контроллер: успешно создан');
            return true;
        } else {
            $this->error('[X] API Контроллер: Не удалось создать');
            return false;
        }

    }

    private function createRoutes(): void
    {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/routes.stub'));
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);
        $repoDir = $this->modules_dir . '/' . $this->module_path . '/Routes/';
        $this->createDirectory($repoDir);
        $path = $repoDir . 'web.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] Файл маршрутов: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        if ($this->files->exists($path)) {
            $this->info('[V] Файл маршрутов: успешно создан');
        } else {
            $this->error('[X] Файл маршрутов: Не удалось создать');
        }
    }

    private function createApiRoutes(): void
    {
        $stub = $this->files->get(base_path('resources/stubs/MakeModule/api_routes.stub'));
        $stub = Str::replace($this->replace->keys(), $this->replace->values(), $stub);
        $repoDir = $this->modules_dir . '/' . $this->module_path . '/Routes/';
        $this->createDirectory($repoDir);
        $path = $repoDir . 'api.php';

        if ($this->files->exists($path)) {
            $this->warn('[!] Файл API маршрутов: Уже существует');
            return;
        }

        $this->files->put($path, $stub);
        if ($this->files->exists($path)) {
            $this->info('[V] Файл API маршрутов: успешно создан');
        } else {
            $this->error('[X] Файл API маршрутов: Не удалось создать');
        }

    }

    private function createMigration(): void {
        $table = $this->replace['#MODULE_NAME_PL_SNAKE#'];

        try {
            $this->call('make:migration', [
                'name' => "create_{$table}_table",
                '--create' => $table,
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }




    private function createDirectory(string $modelDir): void
    {
        if (!$this->files->exists($modelDir)) {
            if (!$this->files->makeDirectory($modelDir, 0755, true)) {
                $this->error('[X] Ошибка: Не удалось создать папку ' . $modelDir);
                abort();
            }
        }
    }

    private function updateModularConfig()
    {
        $config = config('modular');

        if(empty($config['modules'][$this->module_scope])) {
            $config['groupMiddleware'][$this->module_scope] = ['web' => ['auth'], 'api' => ['auth:api']];
            $config['modules'][$this->module_scope] = [];
        }

        if (!in_array($this->module_path, $config['modules'][$this->module_scope])) {
            $config['modules'][$this->module_scope][] = $this->module_path;
            $configPath = config_path('modular.php');

            // Красивое форматирование с синтаксисом []
            $content = "<?php\n\nreturn [\n";

            foreach ($config as $key => $value) {
                $content .= "    '{$key}' => " . $this->formatValue($value, 1) . ",\n";
            }

            $content .= "];\n";
            file_put_contents($configPath, $content);
        }
    }

    private function formatValue($value, $indentLevel = 0)
    {
        $indent = str_repeat('    ', $indentLevel);

        if (is_array($value)) {
            $result = "[\n";
            foreach ($value as $k => $v) {
                if(is_int($k)) {
                    $result .= $indent . "    " . $this->formatValue($v, $indentLevel + 1) . ",\n";
                } else {
                    $result .= $indent . "    '{$k}' => " . $this->formatValue($v, $indentLevel + 1) . ",\n";
                }
            }
            $result .= $indent . ']';
            return $result;
        } elseif (is_string($value)) {
            return "'{$value}'";
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } else {
            return $value;
        }
    }
}
