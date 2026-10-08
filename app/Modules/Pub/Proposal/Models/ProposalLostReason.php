<?php

namespace App\Modules\Pub\Proposal\Models;

/**
 * Причина, по которой КП не дошло до сделки.
 *
 * Обязательна для статуса «Проиграно».
 * Из неё строится отчёт «почему мы теряем деньги».
 *
 * Патч v27: «Заморожено» и «Отменено» были отдельными статусами, теперь —
 * причины проигрыша (коды совпадают с прежними кодами статусов).
 */
enum ProposalLostReason: string
{
    case FROZEN = 'frozen';
    case CANCELED = 'canceled';
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
            ProposalLostReason::FROZEN => [
                'label' => 'Заморожено',
                'hint' => 'Заказчик поставил проект на паузу',
                'color' => 'warning',
                'sort' => __LINE__,
            ],
            ProposalLostReason::CANCELED => [
                'label' => 'Отменено',
                'hint' => 'Заказчик отменил закупку или проект',
                'color' => 'secondary',
                'sort' => __LINE__,
            ],
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

    /**
     * Причины из списка кодов (patch v44): в исходном порядке,
     * неизвестные коды и повторы отбрасываются
     *
     * @param mixed $codes массив кодов, enum-ы, json-строка или один код
     * @return ProposalLostReason[]
     */
    public static function fromCodes($codes): array
    {
        if (is_string($codes)) {
            $decoded = json_decode($codes, true);
            $codes = is_array($decoded) ? $decoded : [$codes];
        }

        $ret = [];
        foreach ((array) $codes as $code) {
            $case = $code instanceof ProposalLostReason ? $code : ProposalLostReason::tryFrom((string) $code);
            if ($case && !in_array($case, $ret, true)) {
                $ret[] = $case;
            }
        }

        return $ret;
    }

    /**
     * Подписи причин через запятую — для журнала изменений и подсказок
     *
     * @param mixed $codes см. fromCodes()
     * @return string
     */
    public static function labels($codes): string
    {
        return implode(', ', array_map(fn(ProposalLostReason $case) => $case->data()['label'], static::fromCodes($codes)));
    }
}
