<?php

namespace App\Services\Portal;

use App\Modules\Pub\User\Models\User;
use App\Services\Tools\Tools;
use Http;

class Telegram
{
    const TELEGRAM_TOKEN = "2012980880:AAGLxWN8-xF2bQ5TsUe9A9zwqYZG8kUUoCw";

    public static function send(User $user, $text)
    {
        if(empty($user->telegram_id)) return false;
        $url = "https://api.telegram.org/bot" . static::TELEGRAM_TOKEN .  "/sendMessage";
        $response = Tools::CurlPostJson($url, [
            'chat_id' => $user->telegram_id,
            'text' => $text,
            'parse_mode' => 'html'
        ]);
    }

    public static function prepare(\Illuminate\Support\Collection $arParams)
    {
        $ret = '';
        if(!empty($arParams['title'])) $ret .= "<b>{$arParams['title']}</b>" . chr(10) . chr(10);
        if(!empty($arParams['message'])) $ret .= "{$arParams['message']}";
        $explode = \Str($ret)->explode(chr(10));
        foreach($explode as $i => $str) {
            $explode[$i] = trim($str);
        }
        $ret = $explode->implode(chr(10));
        return $ret;
    }
}
