<?php


namespace App\Services\PortalRepository;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AbstractPortalService
{
    protected $rules = [];
    protected function validate(array $row): bool {
        $validator = Validator::make($row, $this->rules);
        if($validator->fails()) {
            Log::error("Ошибка валидации данных [". $this::class ."]");
            return false;
        }
        return true;
    }
}
