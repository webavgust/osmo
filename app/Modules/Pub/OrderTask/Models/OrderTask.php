<?php

namespace App\Modules\Pub\OrderTask\Models;

use App\Models\ModuleModel;
use App\Models\traits\HasDetailPage;
use App\Modules\Pub\DocumentNumber\Models\DocumentNumber;
use App\Modules\Pub\Evaluation\Models\Evaluation;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\OrderTaskAgreement\Models\OrderTaskAgreement;
use App\Modules\Pub\OrderTaskObject\Models\OrderTaskObject;
use App\Modules\Pub\Reminder\Traits\HasReminder;
use App\Modules\Pub\Service\Models\Service;
use App\Modules\Pub\SubContract\Models\SubContract;
use App\Modules\Pub\User\Models\User;
use App\Traits\Eloquent\Model\HasCreator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasEvents;
use Illuminate\Support\Facades\DB;

class OrderTask extends ModuleModel
{
    use HasEvents, HasReminder, HasDetailPage, HasCreator;

    public static $module_name = 'Техническое задание';
    public static $module_icon = 'fa-diagram-subtask';
    public static $detail_route = 'order_task.detail';

    private static mixed $STATUS;
    protected $log_events = ['updated'];
    protected $fillable = ['contacts', 'status', 'sub_contract_id', 'block_id', 'created_by', 'discount', 'supervisor_rate', 'minus_rate', 'portal_data'];
    protected $searchable = [
        "block_id", "order_id", "id"
    ];
    protected $casts = [
        'agreed' => 'bool',
        'portal_data' => 'json',
    ];

    const STATUS_STARTED = "started";       // заполнен 1 шаг
    const STATUS_CREATED = "created";       // заполнен 2 шаг
    const STATUS_AGREEMENT = "agreement";   // на согласовании
    const STATUS_ACCEPTED = "agreed";       // согласование одобрено
    const STATUS_DECLINED = "declined";     // согласование не одобрено
    const STATUS_WORKING = "working";       // нажали кнопку Передать
    const STATUS_ALL_WORKS_FINISHED = "works_finished";     // все пробы отработаны
    const STATUS_FINISHED = "finished";     // завершён
    const STATUS_CANCELLED = "cancelled";   // отменён
    const STATUS_ARCHIVE = "archive";       // в архиве


    const STATUS_LANG = [
        self::STATUS_STARTED => "Заполнен 1 шаг",
        self::STATUS_CREATED => "Заполнен 2 шаг",
        self::STATUS_AGREEMENT => "На согласовании",
        self::STATUS_ACCEPTED => "Согласовано",
        self::STATUS_DECLINED => "Не согласовано",
        self::STATUS_WORKING => "В работе",
        self::STATUS_ALL_WORKS_FINISHED => "Все пробы внесены",
        self::STATUS_FINISHED => "Завершено",
        self::STATUS_CANCELLED => "Отменено",
        self::STATUS_ARCHIVE => "В архиве",
    ];

    const STATUS_COLOR = [
        self::STATUS_STARTED => ["badge" => "bg-light text-dark", "button" => "light", "text" => "text-dark"],
        self::STATUS_CREATED => ["badge" => "bg-info", "button" => "secondary", "text" => "text-dark"],
        self::STATUS_AGREEMENT => ["badge" => "bg-warning text-dark", "button" => "warning"],
        self::STATUS_ACCEPTED => ["badge" => "bg-success", "button" => "success"],
        self::STATUS_DECLINED => ["badge" => "bg-danger", "button" => "danger"],
        self::STATUS_WORKING => ["badge" => "badge bg-primary", "button" => "primary"],
        self::STATUS_ALL_WORKS_FINISHED => ["badge" => "badge bg-success", "button" => "success"],
        self::STATUS_FINISHED => ["badge" => "badge bg-secondary", "button" => "secondary"],
        self::STATUS_CANCELLED => ["badge" => "badge bg-danger", "button" => "danger"],
        self::STATUS_ARCHIVE => ["badge" => "badge bg-danger", "button" => "danger"],
    ];


    const STATUS_DATA = [
        self::STATUS_STARTED => ["sort" => 0],
        self::STATUS_CREATED => ["sort" => 10],
        self::STATUS_AGREEMENT => ["sort" => 20],
        self::STATUS_ACCEPTED => ["sort" => 30],
        self::STATUS_DECLINED => ["sort" => 40],
        self::STATUS_WORKING => ["sort" => 50],
        self::STATUS_ALL_WORKS_FINISHED => ["sort" => 60],
        self::STATUS_FINISHED => ["sort" => 70],
        self::STATUS_CANCELLED => ["sort" => 80],
        self::STATUS_ARCHIVE => ["sort" => 90],
    ];

    const MODULE_NAME = "Техническое задание";


    public static $logger_data = [
        'address' => ['name' => 'Адрес'],
    ];
    public $logger_casts = [];

    /*** RELATIONS ***/

    public function sub_contract()
    {
        return $this->belongsTo(SubContract::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function number()
    {
        return $this->morphOne(DocumentNumber::class, 'target');
    }

    public function objects()
    {
        return $this->hasMany(OrderTaskObject::class);
    }

    public function objectsA()
    {
        return $this->hasMany(OrderTaskObject::class)->whereHas('lab_object', $filter = function ($query) {
            $query->whereIn('root_id', [
                LabObject::GROUP_WATER,
                LabObject::GROUP_EARTH
            ]);
        });
    }

    public function objectsB()
    {
        return $this->hasMany(OrderTaskObject::class)->whereHas('lab_object', $filter = function ($query) {
            $query->whereNotIn('root_id', [
                LabObject::GROUP_WATER,
                LabObject::GROUP_EARTH
            ]);
        });
    }

    public function agreement()
    {
        return $this->hasOne(OrderTaskAgreement::class);
    }

    public function agreementers()
    {
        return $this->hasManyThrough(User::class, OrderTaskAgreement::class, 'order_task_id', 'id', 'id', 'user_id');
    }


    public function samplers()
    {
        return $this->morphMany(Sampler::class, 'target');
    }

    public function samplersA()
    {
        return $this->samplers()->where('extra', 'A');
    }

    public function salaries($type)
    {
        return $this->hasMany(Salary::class)->where('type', $type);
    }

    public function plan_visits()
    {
        return $this->hasMany(PlanVisit::class);
    }

    public function globalSamplers($type = 'A')
    {
        switch ($type) {
            case 'A':
                $samplers = $this->samplersA->pluck('user_id');
                $objects = $this->objectsA;
                break;
            case 'B':
                $samplers = $this->samplersB->pluck('user_id');
                $objects = $this->objectsB;
                break;
        }

        $objects->each(function ($object) use (&$samplers) {
            $samplers = $samplers->merge($object->samplers->pluck('user_id'));
            $object->addresses->each(function ($address) use (&$samplers) {
                $samplers = $samplers->merge($address->samplers->pluck('user_id'));
                $address->points->each(function ($point) use (&$samplers) {
                    $samplers = $samplers->merge($point->samplers->pluck('user_id'));
                });
            });
        });

        return $samplers->unique()->count();
    }

    public function samplersB()
    {
        return $this->samplers()->where('extra', 'B');
    }


    public function hasMeasures()
    {
        return DB::table('order_tasks')
                ->leftJoin('order_task_objects', 'order_tasks.id', '=', 'order_task_objects.order_task_id')
                ->leftJoin('order_task_addresses', 'order_task_objects.id', '=', 'order_task_addresses.order_task_object_id')
                ->leftJoin('order_task_points', 'order_task_addresses.id', '=', 'order_task_points.order_task_address_id')
                ->leftJoin('order_task_measures', 'order_task_points.id', '=', 'order_task_measures.order_task_point_id')
                ->where('order_tasks.id', $this->id)
                ->where('order_task_measures.id', '>', 0)
                ->count() > 0;
    }

    public function hasAccess()
    {
        return
            is_admin()
            ||
            $this->creator->id == auth()->id()
            ||
            (in_array($this->status, [static::STATUS_AGREEMENT, static::STATUS_ACCEPTED, static::STATUS_DECLINED, static::STATUS_ARCHIVE]) && $this->agreement?->users->contains(auth()->user()))
            ||
            (_can('order_task_submit') && in_array($this->status, [static::STATUS_ACCEPTED]))
            ||
            (auth()->user()->isSupervisor() && in_array($this->status, [static::STATUS_WORKING, static::STATUS_ALL_WORKS_FINISHED, static::STATUS_FINISHED]));

    }

    public function hasWorkMode()
    {
        return in_array($this->status, [\App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_WORKING, \App\Modules\Pub\OrderTask\Models\OrderTask::STATUS_ALL_WORKS_FINISHED]);
    }

    public function canCreate2()
    {
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_STARTED]) && $this->objects->count() > 0;
    }

    public function canEdit1()
    {
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_CREATED]);
    }

    public function canEdit2()
    {
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_CREATED]) && $this->objects->count() > 0;
    }

    public function canEdit()
    {
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_STARTED, self::STATUS_CREATED]);
    }

    public function canCopy()
    {
        return _can('order_task_copy') && $this->hasAccess() && !in_array($this->status, [self::STATUS_STARTED, self::STATUS_CREATED]);
    }

    public function canAgree()
    {
        return _can('order_task_agree') && $this->hasAccess() && !$this->agreed && in_array($this->status, [self::STATUS_CREATED]);
    }

    public function canMakeDecision()
    {
        return _can('order_task_agree') && !$this->agreed && in_array($this->status, [self::STATUS_AGREEMENT]);
    }

    public function canAgreeView()
    {
        return _can('order_task_agree') && !$this->agreed && in_array($this->status, [self::STATUS_AGREEMENT]);
    }

    public function canAttach()
    {
        return false;
        return _can('order_task_attach') && $this->hasAccess() && !$this->order_id && in_array($this->status, [self::STATUS_ACCEPTED]);
    }

    public function canViewHistory()
    {
        return 0 && _can('order_task_history') && $this->hasAccess() && $this->iteration > 1;
    }

    public function canDelete()
    {
        // TODO: доделать условия
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_STARTED, self::STATUS_CREATED]);
    }

    public function canCancel()
    {
        // TODO: доделать условия
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_DECLINED]);
    }

    public function canRemake()
    {
        return _can('order_task_edit') && $this->hasAccess() && in_array($this->status, [self::STATUS_DECLINED])
            && (OrderTask::where('block_id', $this->block_id)->orderBy('id', 'desc')->first())->id == $this->id;
    }

    public function canStartWorking()
    {
        return _can('order_task_submit')
            && $this->hasAccess()
            && in_array($this->status, [self::STATUS_ACCEPTED]);
    }

    public function canSetSamplers(): bool
    {
        return (_can('can_select_sampler') || auth()->user()->isSupervisor())
            && $this->hasAccess()
            && in_array($this->status, [self::STATUS_WORKING]);
    }

    public function scopeSearch(Builder $builder, $search)
    {
        $words = collect(explode(" ", $search));
        $builder->where(function ($builder) use ($words) {
            $builder->where(function ($builder) use ($words) {
                foreach ($this->searchable as $i => $field) {
                    $builder->orWhere(function ($builder) use ($words, $field) {
                        $words->each(fn($item) => $builder->where($field, 'LIKE', '%' . $item . '%'));
                    });
                }
            })->orWhereHas('sub_contract', function ($builder) use ($words) {
                $builder->where(function ($builder) use ($words) {
                    foreach (['contract_id', 'slug'] as $i => $field) {
                        $words->each(fn($item) => $builder->orWhere($field, 'LIKE', '%' . $item . '%'));
                    }
                });
            });
        });
        return $builder;
    }

    /*
     *  COUNTS
     */

    public function getObjectsAllAttribute()
    {
        return DB::table('order_tasks', 'ot')->where('ot.id', $this->id)
            ->leftJoin('order_task_objects as oto', 'oto.order_task_id', 'ot.id')
            ->pluck('oto.id');
    }

    public function getAddressesAllAttribute()
    {
        return DB::table('order_tasks', 'ot')->where('ot.id', $this->id)
            ->leftJoin('order_task_objects as oto', 'oto.order_task_id', 'ot.id')
            ->leftJoin('order_task_addresses as ota', 'ota.order_task_object_id', 'oto.id')
            ->pluck('ota.id');
    }

    public function getPointsAllAttribute()
    {
        return DB::table('order_tasks', 'ot')->where('ot.id', $this->id)
            ->leftJoin('order_task_objects as oto', 'oto.order_task_id', 'ot.id')
            ->leftJoin('order_task_addresses as ota', 'ota.order_task_object_id', 'oto.id')
            ->leftJoin('order_task_points as otp', 'otp.order_task_address_id', 'ota.id')
            ->pluck('otp.id');
    }

    public function getMeasuresAllAttribute()
    {
        return DB::table('order_tasks', 'ot')->where('ot.id', $this->id)
            ->leftJoin('order_task_objects as oto', 'oto.order_task_id', 'ot.id')
            ->leftJoin('order_task_addresses as ota', 'ota.order_task_object_id', 'oto.id')
            ->leftJoin('order_task_points as otp', 'otp.order_task_address_id', 'ota.id')
            ->leftJoin('order_task_measures as otm', 'otm.order_task_point_id', 'otp.id')
            ->pluck('otm.id');
    }


    /*
     *  SCOPES
     */

    public function scopeForA($query)
    {
        /*
         *  В ТЗ: "вода" или "почва или отходы"
         */
        return
            $query->where('status', self::STATUS_WORKING)
                ->whereHas('objects', $filter = function ($query) {
                    $query->whereHas('lab_object', $filter = function ($query) {
                        $query->whereIn('root_id', [
                            LabObject::GROUP_WATER,
                            LabObject::GROUP_EARTH
                        ]);
                    });
                })->with('creator');
    }


    public function scopeForB($query)
    {
        /*
         *  В ТЗ (или):
         *   1) НЕ "вода" и НЕ "почва или отходы"
         *   2) Есть выезд
         */
        return
            $query->where('status', self::STATUS_WORKING)
                ->where(function (Builder $query) {
                    // 1)
                    $query->whereHas('objects', $filter = function (Builder $query) {
                        $query->whereHas('lab_object', $filter = function (Builder $query) {
                            $query->whereNotIn('root_id', [
                                LabObject::GROUP_WATER,
                                LabObject::GROUP_EARTH
                            ]);
                        });
                    })
                        ->orWhereHas('objects.services', function (Builder $query) {
                            $query->whereIn('id', [
                                Service::TYPE_VISIT_ASSISTANCE,
                                Service::TYPE_TRANSPORT_COSTS,
                            ]);
                        });
                })->with('creator');
    }

    public function scopeForUsers(Builder $query, $users = []): Builder
    {
        if (empty($users)) $users = collect([auth()->user()]);

        $query->whereIn('creator', $users->pluck('id')->toArray());

        return $query;
    }

    public function scopeAvailable(Builder $query, User $user = null): Builder
    {
        if (!auth()->user()->isAdmin()) $user = auth()->user();

        if (!empty($user))
            $query->where('creator', $user->id);


        return $query;
    }

    public function scopeBetweenDates(Builder $query, $dates)
    {
        $query->where(function (Builder $query) use ($dates) {
            $query->whereBetween('created_at', $dates);
        });
        return $query;
    }


    public function getPlanSupervisorSalaryAttribute(): float
    {
        $sum = 0;
        $this->objects->each(function ($object) use (&$sum) {
            $object->addresses->each(function ($address) use (&$sum) {
                // 1. вычтем ставку
                $temp = $address->cost_total * (1 - $this->minus_rate / 100);

                // 2. вычтем расходы
                $temp -= $address->cost_raw;

                // 3.
                $temp *= $this->supervisor_rate / 100;

                $sum += $temp;
            });
        });
        return $sum;
    }

    public function getPlanCostTotalAttribute(): float
    {
        $cost = 0;
        $this->objects->each(function ($object) use (&$cost) {
            $object->addresses->each(function ($address) use (&$cost) {
                $cost += $address->cost_raw;
            });
        });

        return $this->plan_supervisor_salary + $cost;
    }


    public function getCostTotalAttribute(): float
    {
        $cost = 0;
        $this->objects->each(function ($object) use (&$cost) {
            $cost += $object->cost_total;
        });

        $cost -= $this->discount;

        return $cost;
    }


    public function isWorking(): bool
    {
        return $this->status == OrderTask::STATUS_WORKING;
    }

    public function isFinished(): bool
    {
        return $this->status == OrderTask::STATUS_ALL_WORKS_FINISHED;
    }

    public function hasSamplers()
    {
        foreach ($this->objects as $object) {
            if (!$object->hasSamplers())
                return false;
        }

        return true;
    }


    public function getSamplers()
    {
        $data = collect();
        foreach ($this->objects as $object) {
            $data = $data->merge($object->getSamplers());
        }

        return $data->keyBy('id');
    }

    public function canFinish()
    {
        if ($this->status !== OrderTask::STATUS_WORKING)
            return false;

        $check = true;
        $this->objects->each(function ($obj) use (&$check) {
            if (!$obj->isFinished())
                $check = false;
        });

        return $check && $this->hasAccess();
    }


    public function getProgress()
    {

        $children = $this->objects;

        $total = [];
        foreach ($children as $child) {
            $progress = $child->getProgress();
            foreach ($progress as $key => $value) {
                if (empty($total[$key]))
                    $total[$key] = 0;
                $total[$key] = $total[$key] + $value;
            }
        }

        return collect($total);
    }

    public function getDirections()
    {
        $directions = [];

        if ($this->objectsA->isNotEmpty())
            $directions[] = OrderTaskObject::DIRECTION_A;

        if ($this->objectsB->isNotEmpty())
            $directions[] = OrderTaskObject::DIRECTION_B;

        return $directions;
    }
}

