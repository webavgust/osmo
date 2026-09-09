<?php

use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

function _date($date, $arParams = [])
{
    $time = strtotime($date);
    if(!$time) return null;
    $carbon = Carbon::createFromTimestamp($time);

    if(!empty($arParams['type']))
    {
        switch($arParams['type'])
        {
            case "r_full":
                $ret = "«{$carbon->format('d')}» ".(\App\Facades\Tools::MONTH_NAME_D[$carbon->format('n')])." {$carbon->year} года";
                return $ret;
                break;
        }
    }

    return $carbon->format($arParams['format'] ?? 'd.m.Y');
}

function _datetime(Carbon $time) {
    return $time->format('d.m.Y H:i:s');
}

function _time_human(Carbon $time) {
    return $time->ago();
}

function _can($access_name)
{
    return auth()->check() && auth()->user()->can($access_name);
//    return auth()->check() && auth()->user()->can_do($access_name);
}

function _docnumber($number) {
    return Str::substr($number, 0, 6) . '<strong class="text-danger">' . Str::substr($number, 6) . '</strong>';
}

function _cost($number) {
    return \App\Facades\Tools::cost_normalize($number);
}

function _notify(User $user, array $arParams = [], $notificators = []) {
    \App\Services\Notificator\Notificator::send($user, $arParams, $notificators);
}

function _token() {
    return auth()->user()->ajax_token;
}

function _module_name($class)
{
    $temp = \Str::after($class, "App\Modules\Pub\\");
    return \Str::before($temp, '\\');
}

function download_path($path)
{
    return asset("storage/" . $path);
}

function is_admin() {
    return auth()->user()->isAdmin();
}

function tools() {
    return App::get(\App\Services\Tools\Tools::class);
}

