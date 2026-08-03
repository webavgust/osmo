<?php


namespace App\Services\PortalRepository;


use App\Interfaces\PortalRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPortalRepository implements PortalRepositoryInterface
{
    const STATUS_SUCCESS = "success";

    protected function getData($url)
    {
        $response = Http::get($url)->json();
        if (!$response || $response['status'] !== self::STATUS_SUCCESS) {
             //if(app()->environment() == "development") Log::error("Ошибка при обращении к порталу [url: {$url}]");
            return [];
        }

        return $response['result'];
    }

    protected function getAuth($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch), 1);
        curl_close ($ch);
        if (!$response || $response['status'] !== self::STATUS_SUCCESS) {
            //Log::error("Ошибка при обпащении к порталу [url: {$url}]");
            return false;
        }

        return true;
    }
}
