<?php

namespace App\Traits\Eloquent\Model;

use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabObject\Models\LabObject;

trait HasTreeStructure
{
    /*** RELATIONS ***/

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort');
    }


    /**
     * Scope is_live для активных записей
     *
     * @param $query
     * @return mixed
     */
    public function scopeIsLive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Получить всех потомков
     *
     * @param $filterID
     * @return mixed
     */
    public function getAllChildren($filterID = [])
    {
        if (empty($filterID)) {
            $arID = $this->children()->pluck('id');
        } else {
            $arID = $this->children()->whereIn('id', $filterID)->pluck('id');
        }
        foreach ($this->children as $child) {
            $arID = $arID->merge($child->getAllChildren($filterID));
        }

        return $arID;
    }

    /**
     * Получить дерево
     *
     * @param $arID
     * @return array
     */
    public static function getTree($arID)
    {
        $res = self::whereIn('id', $arID)->get();


        $arID = collect();
        foreach ($res as $item)
            $arID = $arID->merge($item->chain_id)->unique();

        $arRet = [];
        self::findMany($arID)->where('depth', '>', 1)->each(function ($item) use (&$arRet) {
            $key = $item->is_last ? $item->name : implode(" / ", $item->chain_name);
            $arRet[$key] = $item;
        });

        return $arRet;
    }
    public static function normalizeAll()
    {
        $data = static::orderBy('id', 'desc')->get();
        foreach($data as $item) {
            $chain = collect($item->name);
            $chain_slug = collect($item->slug);
            $chainID = collect($item->id);
            $target = $item;
            $k = 0;


            while(!empty($target?->parent)) {
                $target = $target->parent;
                $chain->add($target->name);
                $chain_slug->add($target->slug);
                $chainID->add($target->id);
            }

            $item->update([
                'root_id' => $chainID->last(),
                'chain_id' => $chainID->reverse()->values(),
                'chain_name' => $chain->reverse()->values(),
                'depth' => $chain->count(),
                'is_last' => !count($item->children)
            ]);
        }
    }
}
