<?php

namespace App\Modules\Admin\Consts\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Consts\Services\AdminConstService;
use App\Modules\Pub\Constant\Models\Constant;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * AJAX админ-панели: константы (patch v28, этап C).
 *
 * Правила — в AdminConstService, здесь только разбор запроса и ответ в формате
 * `{result: 'success'|'error', message}` со статусом 200: страница показывает
 * toastr и не падает в 500-ю.
 */
class ApiConstsController extends Controller
{
    /** Поля формы попапа */
    protected const FIELDS = ['key', 'name', 'value', 'note'];

    /**
     * Создать константу
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return $this->run(function () use ($request) {
            $constant = AdminConstService::create($request->only(self::FIELDS));

            return ['message' => 'Константа ' . e($constant->key) . ' создана', 'id' => $constant->id];
        });
    }

    /**
     * Изменить константу
     *
     * @param Request $request
     * @param Constant $constant
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Constant $constant)
    {
        return $this->run(function () use ($request, $constant) {
            AdminConstService::update($constant, $request->only(self::FIELDS));

            return ['message' => 'Константа ' . e($constant->key) . ' сохранена', 'id' => $constant->id];
        });
    }

    /**
     * Удалить несистемную константу
     *
     * @param Request $request
     * @param Constant $constant
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, Constant $constant)
    {
        return $this->run(function () use ($constant) {
            AdminConstService::delete($constant);

            return ['message' => 'Константа ' . e($constant->key) . ' удалена', 'id' => $constant->id];
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
            // все ошибки формы разом: тостер выводит HTML, текст экранируем
            return response()->json([
                'result' => 'error',
                'message' => collect($e->errors())->flatten()->map(fn($message) => e($message))->implode('<br>')
                    ?: e($e->getMessage()),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'result' => 'error',
                'message' => 'Не получилось выполнить действие: ' . e($e->getMessage()),
            ]);
        }

        return response()->json(array_merge(['result' => 'success'], $result));
    }
}
