<?php

namespace App\Modules\Pub\UserDepartment\Controllers\Api;

use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use Illuminate\Http\Request;

class ApiUserDepartmentController
{
    public function agreement_save(Request $request, UserDepartment $userDepartment)
    {
        $userDepartment->agreementers()->sync($request->agreementer);
        return ['result' => 'success', 'count' => $userDepartment->agreementers()->count()];
    }
}
