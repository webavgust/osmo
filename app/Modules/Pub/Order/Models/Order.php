<?php

namespace App\Modules\Pub\Order\Models;

use App\Models\ModuleModel;
use App\Models\traits\HasDetailPage;
use App\Modules\Pub\Calendar\Models\Calendar;
use App\Modules\Pub\ChangeLogger\Traits\HasLogger;
use App\Modules\Pub\DocumentNumber\Models\DocumentNumber;
use App\Modules\Pub\Order\Repositories\OrderRepository;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\OrderComment\Models\OrderComment;
use App\Modules\Pub\OrderFieldLog\Models\OrderFieldLog;
use App\Modules\Pub\OrderPeriod\Models\OrderPeriod;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class Order extends ModuleModel
{
    use HasLogger, HasReminder, HasDetailPage;

    public static $module_name = 'Заявка';
    public static $module_icon = 'fa-briefcase';
    public static $detail_route = 'order.detail';

    public const STATUS = [
        'active' => [
            'color' => ["badge" => "bg-info", "button" => "info"],
            'name' => 'Активная'
        ],
        'finished' => [
            'color' => ["badge" => "bg-secondary", "button" => "secondary"],
            'name' => 'Завершённая'
        ],
        'archived' => [
            'color' => ["badge" => "badge bg-danger", "button" => "danger"],
            'name' => 'Архивная'
        ],
    ];


    protected $fillable = [
        // FROM PORTAL
        "order_name", "customer_id", "order_sent_to_techdep",  "customer_name", "contract_id", "contract_conclusion", "author_id", "manager_id", "curator_id", "last_control_date", "second_control_date", "md_specify_days", "md_specify_finaldate", "md_specify_end_period_date", "md_specify_periodicity", "md_specify_locationplace",

        // GENERATE
        "is_archived", "is_finished"

    ];
    static protected $portalFillable = [
        "order_name", "order_sent_to_techdep", "customer_id", "customer_name", "contract_id", "contract_conclusion", "author_id", "manager_id", "curator_id", "last_control_date", "second_control_date", "md_specify_days", "md_specify_finaldate", "md_specify_end_period_date", "md_specify_periodicity", "md_specify_locationplace"
    ];

    protected $searchable = [
        "order_name", "customer_name", "id"
    ];

    static public function getPortalFillable()
    {
        return static::$portalFillable;
    }

    /*
     *  RELATIONS
     */

    public function author()
    {
        return $this->belongsTo(User::class);
    }

    public function manager()
    {
        return $this->belongsTo(User::class);
    }

    public function curator()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(OrderComment::class);
    }

    public function periods()
    {
        return $this->hasMany(OrderPeriod::class);
    }

    public function field_logs()
    {
        return $this->hasMany(OrderFieldLog::class);
    }

    public function order_task()
    {
        return $this->hasOne(OrderTask::class);
    }


    public function event_dc1()
    {
        return $this->morphOne(Calendar::class, 'target')->where('target_sub', 'order_dc1');
    }

    public function event_dc2()
    {
        return $this->morphOne(Calendar::class, 'target')->where('target_sub', 'order_dc2');
    }





    public function log(array $data)
    {
        $count = 0;
        foreach($data as $field => $ar) {
            if($ar[0] == $ar[1]) continue;
            $count++;
            $this->field_logs()->create([
                'field' => $field,
                'old' => $ar[0],
                'new' => $ar[1],
                'user_id' => auth()->user()->id
            ]);
        }
        return $count > 0;
    }


    public function scopeSearch(Builder $builder, $search)
    {
        $words = collect(explode(" ", $search));


        $builder->where(function($builder) use ($words) {
            foreach($this->searchable as $i => $field) {
                $builder->orWhere(function ($builder) use ($words, $field) {
                    $words->each(fn($item) => $builder->where($field, 'LIKE', '%' . $item . '%'));
                });
            }
        });
        return $builder;
    }

    public function daysData()
    {
        $service = new OrderService();
        if(empty($this->md_specify_days)) return [];
        $data_until_end = $service->daysCalc(['type' => 'date', 'date' => $this->md_specify_finaldate, 'days' => $this->md_specify_days, 'order_date' => $this->order_sent_to_techdep]);


        $remain = $data_until_end['days'] ? min($data_until_end['days'], $this->md_specify_days) :  null;
        $left = $data_until_end['days'] ? max($remain, $this->md_specify_days - $data_until_end['days']) : 0;


        $data = [
            'total' => $this->md_specify_days,
            'remain' => $remain,
            'left' => $left,
            'percent' => round(($left / $this->md_specify_days) * 100, 1)
        ];
        return $data;
    }

    public function canView()
    {
        $table_data = (new OrderRepository())->getTable();
        return auth()->user()->isAdmin() || in_array($this->id, $table_data['filter']['id']);
    }


    public function scopeBetweenDates(Builder $query, $dates)
    {
        $query->where(function(Builder $query) use ($dates) {
            $query->whereBetween('created_at', $dates)
                ->orWhereBetween('updated_at', $dates);
        });
        return $query;
    }


    public function scopeAsManager(Builder $query, $users = []): Builder
    {
        //if(is_admin()) return $query;
        if(empty($users)) $users = collect([auth()->user()]);

        $query->whereIn('manager_id', $users->pluck('id')->toArray());

        return $query;
    }

    public function scopeAsCurator(Builder $query, $users = []): Builder
    {
        //if(is_admin()) return $query;
        if(empty($users)) $users = collect([auth()->user()]);

        $query->whereIn('curator_id', $users->pluck('id')->toArray());
        return $query;
    }
}
