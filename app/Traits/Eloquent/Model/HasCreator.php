<?php

namespace App\Traits\Eloquent\Model;

use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasCreator
{
    /*** RELATIONS ***/

    public function creator()
    {
        // withTrashed: имя автора не пропадает у мягко удалённого пользователя
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * Score for_users для фильтрации по пользователю
     *
     * @param Builder $query
     * @param $users
     * @return Builder
     */
    public function scopeForUsers(Builder $query, $users = []): Builder
    {
        if (empty($users)) $users = collect([auth()->user()]);

        $query->whereIn('created_by', $users->pluck('id')->toArray());

        return $query;
    }
}
