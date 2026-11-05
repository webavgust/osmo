<?php

namespace App\Modules\Pub\Contract\Services;

use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\User\Models\User;
use Carbon\CarbonInterface;

class ContractService
{
    /**
     * Последний порядковый номер, выданный в Google-таблице договоров (L-AA-64/11.26).
     * В портале есть не все номера таблицы: без этой границы удаление договора
     * могло бы вернуть номер, уже выданный в таблице.
     */
    private const LAST_SHEET_SEQUENCE = 64;

    /**
     * Номер договора: тип-менеджер-порядковый/месяц.год, например L-AA-65/11.26
     *
     * @param ContractType $type
     * @param User $manager
     * @param CarbonInterface $date дата договора — по ней месяц и год в номере
     * @return string
     */
    public static function generateNumber(ContractType $type, User $manager, CarbonInterface $date): string
    {
        return sprintf(
            '%s-%s-%d/%s',
            $type->data()['prefix'],
            trim((string) $manager->initials),
            self::nextSequence(),
            $date->format('n.y')
        );
    }

    /**
     * Следующий порядковый номер: сквозной по всем типам и менеджерам
     *
     * Учитываются только номера формата «…-N/месяц.год»: в базе есть опечатки вроде
     * L-AK-511.26 (это 51/1.26) — без косой черты они не считаются номером 511.
     *
     * @return int
     */
    public static function nextSequence(): int
    {
        $max = Contract::whereNotNull('number')->pluck('number')
            ->map(fn ($number) => self::sequenceOf($number) ?? 0)
            ->max();

        return max(self::LAST_SHEET_SEQUENCE, (int) $max) + 1;
    }

    /**
     * Почему номер нельзя сохранить: он занят целиком или занят его порядковый номер
     *
     * Вызывать внутри транзакции: блокировка строк не даёт двум одновременным
     * сохранениям получить один номер. Неизменённый номер не проверяется — в базе
     * есть старые повторы порядковых номеров (L-PG-26/5.25 и P-АА-26/10.2024).
     *
     * @param string|null $number
     * @param Contract|null $except договор, который сейчас редактируется
     * @return string|null текст ошибки или null, если номер свободен
     */
    public static function numberConflict(?string $number, ?Contract $except = null): ?string
    {
        $number = trim((string) $number);
        if ($number === '' || ($except && $number === trim((string) $except->number))) return null;

        $sequence = self::sequenceOf($number);
        foreach (Contract::query()->lockForUpdate()->get(['id', 'number']) as $other) {
            if ($except && $other->id === $except->id) continue;

            if (mb_strtolower(trim((string) $other->number)) === mb_strtolower($number)
                || ($sequence !== null && self::sequenceOf($other->number) === $sequence)) {
                return "Порядковый номер занят договором {$other->number}. Сгенерируйте номер ещё раз";
            }
        }

        return null;
    }

    /**
     * Порядковый номер из номера договора формата «…-N/месяц.год»
     *
     * @param string|null $number
     * @return int|null null, если номер другого формата
     */
    private static function sequenceOf(?string $number): ?int
    {
        return preg_match('~-(\d+)/\d{1,2}\.\d{2,4}$~u', trim((string) $number), $match) ? (int) $match[1] : null;
    }
}
