<?php

namespace App\Modules\Pub\Constant\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Constant\Repositories\ConstantRepository;
use Illuminate\Http\Request;

class ConstantController
{
    use HasBreadcrumb;

    private $repo;

    public function __construct()
    {
        $this->repo = new ConstantRepository();
        $this->breadcrumb_add('/constants', 'Переменные окружения');
    }

    /**
     * Список констант
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $consts = $this->repo->getAll();

        return view('pub.constant.index', [
            'breadcrumbs' => $this->breadcrumb,
            'consts' => $consts
        ]);
    }

    /**
     * Обновление констант
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * TODO: [REF] перенести в репозиторий
     */
    public function update(Request $request)
    {
        $request->validate([
            'const' => 'required|array',
            'const.*' => 'required'
        ]);
        foreach ($request->input('const') as $const_id => $value) {
            \DB::update('UPDATE `consts` SET `value` = ? WHERE `id` = ?', [$value, $const_id]);
        }

        return \Redirect::route('constants.index');
    }
}
