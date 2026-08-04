<?php

namespace App\Modules\Pub\Proposal\Models;

/**
 * Причина, по которой КП не дошло до сделки.
 *
 * Обязательна для статусов «Проиграно», «Заморожено», «Отменено».
 * Из неё строится отчёт «почему мы теряем деньги».
 */
enum ProposalLostReason: string
{
    case PRICE = 'price';
    case COMPETITOR = 'competitor';
    case BUDGET = 'budget';
    case TIMELINE = 'timeline';
    case FUNCTIONAL = 'functional';
    case NO_NEED = 'no_need';
    case NO_RESPONSE = 'no_response';
    case INTERNAL = 'internal';
    case OTHER = 'other';

    public function data(): array
    {
        return match ($this) {
            ProposalLostReason::PRICE => [
                'label' => 'Дорого',
                'hint' => 'Цена не устроила заказчика',
                'color' => 'danger',
                'sort' => __LINE__,
            ],
            ProposalLostReason::COMPETITOR => [
                'label' => 'Ушли к конкуренту',
                'hint' => 'Выбрали другого поставщика',
                'color' => 'danger',
                'sort' => __LINE__,
            ],
            ProposalLostReason::BUDGET => [
                'label' => 'Нет бюджета',
                'hint' => 'Бюджет не выделен или урезан',
                'color' => 'warning',
                'sort' => __LINE__,
            ],
            ProposalLostReason::TIMELINE => [
                'label' => 'Сроки',
                'hint' => 'Не устроили сроки поставки или внедрения',
                'color' => 'warning',
                'sort' => __LINE__,
            ],
            ProposalLostReason::FUNCTIONAL => [
                'label' => 'Не хватило функционала',
                'hint' => 'Продукт не закрывает задачу заказчика',
                'color' => 'warning',
                'sort' => __LINE__,
            ],
            ProposalLostReason::NO_NEED => [
                'label' => 'Отпала потребность',
                'hint' => 'Проект у заказчика закрыт или отложен',
                'color' => 'secondary',
                'sort' => __LINE__,
            ],
            ProposalLostReason::NO_RESPONSE => [
                'label' => 'Заказчик не отвечает',
                'hint' => 'Контакт потерян',
                'color' => 'secondary',
                'sort' => __LINE__,
            ],
            ProposalLostReason::INTERNAL => [
                'label' => 'Наше решение',
                'hint' => 'Отказались сами: нерентабельно, нет ресурсов',
                'color' => 'dark',
                'sort' => __LINE__,
            ],
            ProposalLostReason::OTHER => [
                'label' => 'Другое',
                'hint' => 'Опишите причину в комментарии',
                'color' => 'secondary',
                'sort' => __LINE__,
            ],
        };
    }

    static function getDecorated(): array
    {
        $ret = [];
        foreach (static::cases() as $case) {
            $ret[$case->value] = $case->data();
        }

        return $ret;
    }
}
