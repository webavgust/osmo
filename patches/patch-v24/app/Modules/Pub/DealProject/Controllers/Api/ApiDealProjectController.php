<?php

namespace App\Modules\Pub\DealProject\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Services\PartnerCrmCompanyService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * AJAX проектов по сделкам (patch v24).
 *
 * Правила живут в DealProjectService, здесь — только разбор запроса и ответ
 * в формате `{result: 'success'|'error', message}` со статусом 200: попап
 * показывает toastr и не падает в 500-ю.
 */
class ApiDealProjectController extends Controller
{
    /**
     * Создать проект для сделки
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, CrmDeal $deal)
    {
        return $this->run(function () use ($request, $deal) {
            $project = DealProjectService::createForDeal($deal, $this->input($request));

            return ['message' => 'Проект создан', 'id' => $project->id];
        });
    }

    /**
     * Изменить проект
     *
     * @param Request $request
     * @param DealProject $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, DealProject $project)
    {
        return $this->run(function () use ($request, $project) {
            DealProjectService::update($project, $this->input($request));

            return ['message' => 'Проект сохранён', 'id' => $project->id];
        });
    }

    /**
     * Прикрепить сделку к существующему проекту
     *
     * @param Request $request
     * @param DealProject $project
     * @param CrmDeal $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function attach(Request $request, DealProject $project, CrmDeal $deal)
    {
        return $this->run(function () use ($project, $deal) {
            DealProjectService::attachDeal($project, $deal);

            return ['message' => 'Сделка прикреплена к проекту', 'id' => $project->id];
        });
    }

    /**
     * Открепить сделку от проекта
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function detach(Request $request, CrmDeal $deal)
    {
        return $this->run(function () use ($deal) {
            $project = DealProjectService::detachDeal($deal);

            return ['message' => 'Сделка откреплена от проекта', 'id' => $project?->id];
        });
    }

    /**
     * Отправить проект в архив
     *
     * @param Request $request
     * @param DealProject $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function archive(Request $request, DealProject $project)
    {
        return $this->run(function () use ($project) {
            DealProjectService::archive($project);

            return ['message' => 'Проект отправлен в архив', 'id' => $project->id];
        });
    }

    /**
     * Вернуть проект из архива
     *
     * @param Request $request
     * @param DealProject $project
     * @return \Illuminate\Http\JsonResponse
     */
    public function unarchive(Request $request, DealProject $project)
    {
        return $this->run(function () use ($project) {
            DealProjectService::unarchive($project);

            return ['message' => 'Проект возвращён из архива', 'id' => $project->id];
        });
    }

    /**
     * Сопоставить компанию сделки с партнёром портала (patch v24).
     *
     * Попап проекта предлагает это, когда партнёр сделки не определился:
     * пользователь выбирает партнёра из списка, компания сделки уходит ему в
     * `partner_crm_companies`, и дальше проект заводится как обычно. Одна
     * компания Битрикса — не больше чем у одного партнёра; у партнёра
     * компаний может быть несколько.
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return \Illuminate\Http\JsonResponse
     */
    public function partner(Request $request, CrmDeal $deal)
    {
        return $this->run(function () use ($request, $deal) {
            $partner = Partner::find((int) $request->input('partner_id'));

            if (empty($partner)) {
                throw ValidationException::withMessages(['partner_id' => 'Выберите партнёра из списка.']);
            }

            (new PartnerCrmCompanyService())->attach($partner, (int) $deal->company_id);
            DealProjectService::flush();

            return [
                'message' => 'Компания «' . ($deal->company_name ?: '#' . $deal->company_id)
                    . '» сопоставлена партнёру ' . $partner->name,
                'id' => $partner->id,
            ];
        });
    }

    /**
     * Поля формы попапа
     *
     * @param Request $request
     * @return array
     */
    protected function input(Request $request): array
    {
        return [
            'date_start' => $request->input('date_start'),
            'is_pilot' => $request->boolean('is_pilot'),
            'company_id' => $request->input('company_id'),
            'deadline' => $request->input('deadline'),
            'comment' => $request->input('comment'),
            'specs' => (array) $request->input('specs', []),
        ];
    }

    /**
     * Выполнить действие и превратить ошибку правил в понятный ответ
     *
     * @param callable $action
     * @return \Illuminate\Http\JsonResponse
     */
    protected function run(callable $action)
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            return response()->json([
                'result' => 'error',
                'message' => collect($e->errors())->flatten()->first() ?: $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'result' => 'error',
                'message' => 'Не получилось сохранить проект: ' . $e->getMessage(),
            ]);
        }

        return response()->json(array_merge(['result' => 'success'], $result));
    }
}
