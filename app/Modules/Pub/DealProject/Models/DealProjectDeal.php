<?php

namespace App\Modules\Pub\DealProject\Models;

use App\Models\Traits\HasLogger;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Привязка сделки Битрикса к проекту (patch v24).
 *
 * UNIQUE по crm_deal_id в таблице и есть правило «у сделки один проект».
 */
class DealProjectDeal extends Model
{
    use HasLogger;

    protected $table = 'deal_project_deals';
    public $timestamps = false;

    protected $fillable = ['deal_project_id', 'crm_deal_id', 'attached_at', 'attached_by'];
    protected $casts = ['attached_at' => 'datetime'];

    public function deal_project()
    {
        return $this->belongsTo(DealProject::class, 'deal_project_id');
    }

    /** Сделка Битрикса (другое соединение — связь по id) */
    public function crm_deal()
    {
        return $this->belongsTo(CrmDeal::class, 'crm_deal_id', 'id');
    }

    public function author()
    {
        // withTrashed: имя не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'attached_by')->withTrashed();
    }
    /*** ЖУРНАЛ ИЗМЕНЕНИЙ (patch v32) ***/

    public static function logParentRelation(): ?string
    {
        return 'deal_project';
    }

    public static function logLabel(): string
    {
        return 'Сделка Битрикс24';
    }

    /** Сделка у проекта одна на привязку — сопоставляем по id сделки */
    public function logKey(int $index): string
    {
        return (string) $this->crm_deal_id;
    }

    public function logTitle(?int $index = null): string
    {
        // сделка в другой базе — без неё подпись по id
        try {
            $title = static::logText($this->crm_deal?->title, 80);
        } catch (\Throwable $e) {
            $title = '';
        }

        return 'Сделка #' . $this->crm_deal_id . ($title !== '' ? ' «' . $title . '»' : '');
    }

    public static function logIgnore(): array
    {
        return ['attached_at', 'attached_by'];
    }

    public static function logFields(): array
    {
        return [
            'crm_deal_id' => ['label' => 'Сделка Битрикс24', 'relation' => 'crm_deal', 'title' => 'title'],
        ];
    }
}
