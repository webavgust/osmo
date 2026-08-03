<?php


namespace App\Traits\Eloquent\Model;


trait FindOrCreate
{
    public static function findOrCreate($id)
    {
        $obj = static::find($id);
        return $obj ?: new static;
    }
}
