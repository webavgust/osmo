<?php

namespace App\Modules\Pub\ProposalVariantExtraPay\Models;

use App\Models\ModuleModel;
use App\Models\Traits\HasLogger;
use App\Modules\Pub\Client\Services\ClientService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\ProposalPdfTemplate\Models\ProposalPdfTemplate;
use App\Modules\Pub\ProposalPlatform\Models\ProposalPlatform;
use App\Modules\Pub\ProposalSoftware\Models\ProposalSoftware;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProposalVariantExtraPay extends ModuleModel
{
    use HasLogger;

    protected $fillable = ['name', 'block', 'type', 'percent', 'value', 'base', 'software_start', 'software_end', 'work_start', 'work_end','total', 'currency', 'sort'];


    /*** RELATIONS ***/

    public function variant()
    {
        return $this->belongsTo(ProposalVariant::class, 'proposal_variant_id')->orderBy('sort');
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'variant';
    }

    public static function logLabel(): string
    {
        return 'Доп. платёж';
    }

    public function logTitle(?int $index = null): string
    {
        $name = static::logText($this->name, 60);

        return 'Доп. платёж ' . ($name !== '' ? '«' . $name . '»' : '#' . $this->id);
    }

    public static function logIgnore(): array
    {
        return ['sort'];
    }

    public static function logFields(): array
    {
        return [
            'name' => ['label' => 'Наименование'],
            'block' => ['label' => 'Раздел', 'options' => ['all' => 'Общий', 'software' => 'ПО', 'work' => 'Работы']],
            'type' => ['label' => 'Тип', 'options' => ['percent' => 'Процент', 'fix' => 'Фиксированный']],
            'percent' => ['label' => 'Процент'],
            'value' => ['label' => 'Значение', 'type' => 'money', 'derived' => true],
            'base' => ['label' => 'База', 'type' => 'money', 'derived' => true],
            'software_start' => ['label' => 'ПО: до', 'type' => 'money', 'derived' => true],
            'software_end' => ['label' => 'ПО: после', 'type' => 'money', 'derived' => true],
            'work_start' => ['label' => 'Работы: до', 'type' => 'money', 'derived' => true],
            'work_end' => ['label' => 'Работы: после', 'type' => 'money', 'derived' => true],
            'total' => ['label' => 'Итого КП', 'type' => 'money', 'derived' => true],
            'currency' => ['label' => 'Валюта'],
        ];
    }
}
