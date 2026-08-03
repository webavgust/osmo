<?php

namespace App\Traits\Eloquent\Model;

use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasTimestamps
{
    /**
     * Scope between_dates для фильтрации по диапазону дат
     *
     * @param Builder $query
     * @param $dates
     * @return Builder
     */
    public function scopeBetweenDates(Builder $query, $dates)
    {
        $query->where(function (Builder $query) use ($dates) {
            $query->whereBetween('created_at', $dates);
        });
        return $query;
    }
}
