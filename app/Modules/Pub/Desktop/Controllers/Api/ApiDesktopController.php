<?php

namespace App\Modules\Pub\Desktop\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Services\DesktopService;
use App\Support\UiTheme;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;

/**
 * AJAX рабочего стола (patch v30).
 *
 * Правила — в DesktopService, здесь разбор запроса и ответ
 * `{result: 'success'|'error', message, …}` со статусом 200.
 */
class ApiDesktopController extends Controller
{
    /** Виджетов в одном запросе render_batch, не больше */
    public const BATCH_MAX = 30;

    /**
     * Отрисовать виджет: вход widget, w, h, settings (JSON или массив), fresh
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function render(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertView($desktop, $user);

            // группа api не проходит ResolveUiTheme, а стол свёрстан только в Metronic
            $this->useMetronic();

            $html = DesktopService::render(
                $desktop,
                (string) $request->input('widget'),
                (int) $request->input('w'),
                (int) $request->input('h'),
                (array) $this->json($request->input('settings')),
                $user,
                $request->boolean('fresh'),
                $request->boolean('free_size')
            );

            return ['message' => '', 'html' => $html];
        });
    }

    /**
     * Отрисовать несколько виджетов за запрос: items (JSON) [{uid, widget, w, h, free_size, settings}] —
     * не больше BATCH_MAX, fresh. Ответ html: {uid: html}; ошибка виджета — плашка на его месте
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function renderBatch(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertView($desktop, $user);

            $items = $this->json($request->input('items'));
            if (!is_array($items)) {
                throw ValidationException::withMessages(['items' => 'Не удалось прочитать список виджетов']);
            }
            if (count($items) > static::BATCH_MAX) {
                throw ValidationException::withMessages(['items' => 'За один запрос — не больше ' . static::BATCH_MAX . ' виджетов']);
            }

            // группа api не проходит ResolveUiTheme, а стол свёрстан только в Metronic
            $this->useMetronic();

            $html = DesktopService::renderBatch($desktop, $items, $user, $request->boolean('fresh'));

            // пустой пакет — объект {}, а не массив []
            return ['message' => '', 'html' => (object) $html];
        });
    }

    /**
     * Сохранить раскладку: items (JSON), context (JSON или массив, необязательно)
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function save(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertEdit($desktop, $user);

            $items = $this->json($request->input('items'));
            if (!is_array($items)) {
                throw ValidationException::withMessages(['items' => 'Не удалось прочитать раскладку стола']);
            }

            $context = $request->filled('context') ? $this->json($request->input('context')) : null;

            $desktop = DesktopService::save($desktop, $items, $user, is_array($context) ? $context : null);

            return ['message' => 'Рабочий стол сохранён', 'version' => $desktop->version];
        });
    }

    /**
     * Выбрать валюту и период стола (в сессии)
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function context(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertView($desktop, $user);

            $context = DesktopService::setContext($desktop, $request->only(['currency', 'period']), $user);

            return ['message' => '', 'context' => $context->toArray()];
        });
    }

    /**
     * Создать стол: name, copy_from (id стола, необязательно), system
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return $this->run(function () use ($request) {
            $user = auth()->user();
            $request->validate([
                'name' => 'required|string|max:100',
                'copy_from' => 'nullable|integer',
            ], [
                'name.required' => 'Укажите название стола',
                'name.max' => 'Название — не длиннее 100 символов',
            ]);

            $system = $request->boolean('system');

            if ($request->filled('copy_from')) {
                $source = Desktop::find((int) $request->input('copy_from'));
                if (!$source) {
                    throw ValidationException::withMessages(['copy_from' => 'Стол для копирования не найден']);
                }

                $desktop = DesktopService::copy($source, $user, (string) $request->input('name'), $system);
            } else {
                $desktop = DesktopService::create($user, (string) $request->input('name'), $system);
            }

            return [
                'message' => $system ? 'Системный пресет создан' : 'Рабочий стол создан',
                'id' => $desktop->id,
                'url' => url('/desktop/' . $desktop->id),
            ];
        });
    }

    /**
     * Переименовать стол: name
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertEdit($desktop, $user);

            $request->validate(['name' => 'required|string|max:100'], [
                'name.required' => 'Укажите название стола',
                'name.max' => 'Название — не длиннее 100 символов',
            ]);

            DesktopService::rename($desktop, (string) $request->input('name'), $user);

            return ['message' => 'Название сохранено', 'id' => $desktop->id, 'name' => $desktop->name];
        });
    }

    /**
     * Удалить стол
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($desktop) {
            $user = auth()->user();
            DesktopService::assertEdit($desktop, $user);

            $name = $desktop->name;
            DesktopService::delete($desktop, $user);

            return ['message' => 'Стол «' . e($name) . '» удалён', 'url' => url('/desktop')];
        });
    }

    /**
     * Сделать стол столом по умолчанию (личный — владелец, системный — админ)
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeDefault(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($desktop) {
            DesktopService::makeDefault($desktop, auth()->user());

            return [
                'message' => $desktop->is_system
                    ? 'Этот пресет получат новые пользователи'
                    : 'Этот стол открывается по умолчанию',
                'id' => $desktop->id,
            ];
        });
    }

    /**
     * Скопировать стол в личные: name (необязательно)
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function copy(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($request, $desktop) {
            $user = auth()->user();
            DesktopService::assertView($desktop, $user);

            $request->validate(['name' => 'nullable|string|max:100'], [
                'name.max' => 'Название — не длиннее 100 символов',
            ]);

            $copy = DesktopService::copy($desktop, $user, $request->input('name'), false);

            return [
                'message' => 'Создан стол «' . e($copy->name) . '»',
                'id' => $copy->id,
                'url' => url('/desktop/' . $copy->id),
            ];
        });
    }

    /**
     * Обновить личный стол из системного пресета, из которого он создан
     *
     * @param Request $request
     * @param Desktop $desktop
     * @return \Illuminate\Http\JsonResponse
     */
    public function applySource(Request $request, Desktop $desktop)
    {
        return $this->run(function () use ($desktop) {
            $desktop = DesktopService::applySource($desktop, auth()->user());

            return [
                'message' => 'Стол обновлён из системного пресета',
                'id' => $desktop->id,
                'url' => url('/desktop/' . $desktop->id),
            ];
        });
    }

    /**
     * Выполнить действие и превратить ошибку в понятный ответ
     *
     * @param callable $action
     * @return \Illuminate\Http\JsonResponse
     */
    protected function run(callable $action)
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            // все ошибки разом: тостер выводит HTML, текст экранируем
            return response()->json([
                'result' => 'error',
                'message' => collect($e->errors())->flatten()->map(fn($message) => e($message))->implode('<br>')
                    ?: e($e->getMessage()),
            ]);
        } catch (AuthorizationException $e) {
            return response()->json(['result' => 'error', 'message' => e($e->getMessage())]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['result' => 'error', 'message' => 'Не получилось выполнить действие']);
        }

        return response()->json(array_merge(['result' => 'success'], $result));
    }

    /**
     * Значение из JSON-строки; массив возвращается как есть, пустое — null
     *
     * @param mixed $value
     * @return mixed
     */
    protected function json($value)
    {
        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
    }

    /**
     * Включить тему Metronic для отрисовки виджетов в AJAX-запросе
     *
     * @return void
     */
    protected function useMetronic(): void
    {
        UiTheme::use('metronic');

        $path = UiTheme::viewsPath();
        if (!$path || !is_dir($path)) {
            return;
        }

        $finder = View::getFinder();
        if (!in_array(realpath($path), array_map('realpath', $finder->getPaths()), true)) {
            $finder->prependLocation($path);
        }
        $finder->flush();
    }
}
