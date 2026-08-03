<?php

namespace App\Modules\Pub\Notify\Repositories;

use App\Modules\Pub\Notify\Models\Notify;
use Carbon\Carbon;

class NotifyRepository
{
    public function getUnreadedWithUpdate()
    {
        $notifies = Notify::where('user_id', auth()->id())->orderBy('id', 'desc');
        $notifies->update(['showed' =>  1]);
        return $notifies->get();
    }

    public function getForUser()
    {
        return Notify::where('user_id', auth()->id());
    }
    public function getForSpider()
    {
        $return = [];

        $notifies = $this->getForUser()->get();
        $return['count'] = $notifies->count();
        if($return['count'] > 0) {
            $return['new'] = $notifies->where('showed', 0)->count();
        }

        return $return;
    }

    public function getToast()
    {
        $toast = Notify::select('id')
            ->where('user_id', auth()->id())
            ->whereNull('toastr_showed_at')
            ->orderBy('id', 'asc')
            ->limit(1)
            ->first();


        if(!empty($toast))
            $toast->update(['toastr_showed_at' => Carbon::now()]);

        return $toast ?? null;
    }

    public function getAll()
    {
        return $this->getForUser()->withTrashed()->get();
    }

    public function getActual()
    {
        return $this->getForUser()->get();
    }

    public function getTrashed($dates = null)
    {
        $builder = $this->getForUser()->onlyTrashed();
        if(!empty($dates))
            $builder->whereBetween('created_at', [$dates['start']->format('Y-m-d'), $dates['end']->format('Y-m-d'), ]);
        return $builder->get();
    }


}
;
