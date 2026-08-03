<?php

namespace App\Modules\Pub\AuthAttempt\Models;

use App\Models\ModuleModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuthAttempt extends ModuleModel
{
    protected $table = 'user_auth_attempts';
    protected $fillable = ['login', 'user_id', 'success', 'ip', 'unique_token', 'user_agent', 'attempted_at'];
    public  $timestamps = false;


}
