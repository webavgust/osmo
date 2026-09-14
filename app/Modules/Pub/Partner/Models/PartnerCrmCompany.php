<?php

namespace App\Modules\Pub\Partner\Models;

use App\Models\Traits\HasLogger;
use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use Illuminate\Database\Eloquent\Model;

/**
 * Сопоставление партнёра портала с компанией Битрикс24.
 *
 * Компании живут в отдельной базе (соединение bitrix), поэтому связь
 * crm_company() — обычный belongsTo по id: Eloquent сходит во второе
 * соединение отдельным запросом, JOIN'а между базами не будет.
 * Там, где JOIN всё-таки нужен (сырые запросы), обязателен
 * COLLATE utf8mb4_unicode_ci — у avgbitrix коллация utf8mb4_0900_ai_ci.
 */
class PartnerCrmCompany extends Model
{
    use HasLogger;

    protected $table = 'partner_crm_companies';

    public $timestamps = false;

    protected $fillable = ['partner_id', 'crm_company_id', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    /**
     * Партнёр портала
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    /**
     * Компания Битрикса (другое соединение — связь по id вручную)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function crm_company()
    {
        return $this->belongsTo(CrmCompany::class, 'crm_company_id', 'id');
    }

    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v29) ***/

    public static function logParentRelation(): ?string
    {
        return 'partner';
    }

    public static function logLabel(): string
    {
        return 'Компания Битрикс24';
    }

    public function logKey(int $index): string
    {
        return (string) $this->crm_company_id;
    }

    public function logTitle(?int $index = null): string
    {
        // компания в другой базе — без неё подпись по id
        try {
            $title = static::logText($this->crm_company?->title, 80);
        } catch (\Throwable $e) {
            $title = '';
        }

        return 'Компания Битрикс24 ' . ($title !== '' ? '«' . $title . '»' : '#' . $this->crm_company_id);
    }

    public static function logFields(): array
    {
        return [
            'crm_company_id' => ['label' => 'Компания Битрикс24', 'relation' => 'crm_company', 'title' => 'title'],
        ];
    }
}
