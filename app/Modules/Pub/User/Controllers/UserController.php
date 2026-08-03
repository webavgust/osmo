<?php

namespace App\Modules\Pub\User\Controllers;

use App\Facades\Tools;
use App\Modules\Pub\Access\Services\AccessUserService;
use App\Modules\Pub\AuthAttempt\Services\AuthAttemptService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\EducationTaskCourse\Models\EducationTaskCourse;
use App\Modules\Pub\LabMeasure\Models\LabMeasure;
use App\Modules\Pub\LabMeasure\Repository\LabMeasureRepository;
use App\Modules\Pub\LabObject\Models\LabObject;
use App\Modules\Pub\LabObject\Repository\LabObjectRepository;
use App\Modules\Pub\Order\Services\OrderService;
use App\Modules\Pub\OrderTask\Models\OrderTask;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\User\Request\AuthRequest;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\UserWorkCalendar\Models\UserWorkCalendar;
use App\Modules\Pub\WorkCalendar\Models\WorkCalendar;
use App\Services\AjaxToken\AjaxToken;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class UserController extends Controller
{
    use HasBreadcrumb;

    private $service;

    public function __construct()
    {
        $this->service = new UserService();
        $this->attempt = new AuthAttemptService();
        $this->breadcrumb_add(route('users.list'), 'Пользователи');
    }

    /**
     * Аутентификация
     *
     * @param AuthRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function Auth(AuthRequest $request)
    {
        // получим пользователя по логину в локальной базе
        $user = $this->service->getByLogin($request->login);


        $this->attempt->init($request);
        // если пользователь не найден, проверим, есть ли он на портале и в случае необходимости обновим
        if (!$user) {
            $this->attempt->failed();
            return redirect()->route('auth.form', ['back' => $request->back])->withErrors(['message' => __('Пользователь не существует')])->withInput();
        }

        // пробуем авторизовать пользователя
        $credentials = $request->only('login', 'password');
        if (Auth::attempt($credentials, $request->remember)) {
//            $request->session()->regenerate();
            AjaxToken::generate();
            $access_service = new AccessUserService(auth()->id());
            $access_service->refresh();
            $this->attempt->success(auth()->id());


            if (empty($request->back)) {
                return redirect()->route('dashboard.index');
            } else {
                return redirect($request->back);
            }
        } else {
            $this->attempt->failed();

            return redirect()->route('auth.form')->withErrors(['message' => __('Неправильный пароль!')])->withInput();
        }
    }

    /**
     * Форма аутентификации
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function form(Request $request)
    {
        return view('auth', ['back' => $request->back]);
    }

    /**
     * Выход из профиля
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout()
    {
        Session::flush();
        AjaxToken::clear();
        auth()->logout();

        return redirect()->route('auth.form');
    }

    /**
     * Список пользователей
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view()
    {
        $this->breadcrumb_add('', 'Пользователи');

        return view('pub::user.list', [
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Детальная страница пользователя
     *
     * @param User $user
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function detail(User $user)
    {
        $this->breadcrumb_add('', $user->fullName);

        $sub_users = $user->sub_users;
        $parent_users = $user->parent_users;

        return view('pub::user.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'user' => $user,
            'sub_users' => $sub_users,
            'parent_users' => $parent_users,
        ]);
    }


    public function lab_object_bind(User $user = null)
    {
        $this->breadcrumb_add('', 'Привязка аналитика к объектам');

        $users = UserRepository::getAnalytics();
        $objects = LabObjectRepository::getFirst();
        $measure_cats = LabMeasureRepository::getCategories();



        return view('pub.user.analytics_bind', [
            'user' => $user,
            'objects' => $objects,
            'users' => $users,
            'measure_cats' => $measure_cats,
        ]);
    }




    /**
     * Смена пользователя
     *
     * @param $id
     * @return void
     */
    public function changeUser($id)
    {
        Auth::loginUsingId($id);
        $access_service = new AccessUserService($id);
        $access_service->refresh();

        session()->regenerate();
        AjaxToken::generate();
    }

    /**
     * Sidebar управление подчиненными
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_sub_users_sub(User $user)
    {
        $repo = new UserRepository();
        $template = View::make('pub.user.sidebars.sub_users_sub', ['title' => 'Управление подчинёнными', 'user' => $user, 'users' => $repo->getAll()]);

        return $template;
    }

    /**
     * Sidebar управление руководителями
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_sub_users_parent(User $user)
    {
        $repo = new UserRepository();
        $template = View::make('pub.user.sidebars.sub_users_parent', ['title' => 'Управление руководителями', 'user' => $user, 'users' => $repo->getAll()]);

        return $template;
    }

    /**
     * Sidebar группы пользователей
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_groups(User $user)
    {
        if (empty($user)) abort(404);

        return View::make('pub.user.sidebars.show_groups', ['title' => 'Группы пользователя', 'rows' => $user->groups]);
    }

    /**
     * Sidebar подразделения пользователей
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_departments(User $user)
    {
        if (empty($user)) abort(404);

        return View::make('pub.user.sidebars.show_departments', ['title' => 'Подразделения пользователя', 'rows' => $user->departments]);
    }

    /**
     * Рабочий календарь
     *
     * @param User $user
     * @param $year
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function work_calendar_show(User $user, $year = null)
    {
        if (empty($year)) $year = date("Y");
        $dates = $user->work_calendar()->where('year', $year)->where('type', UserWorkCalendar::STATUS_HOLIDAY)->get()->pluck('date')->toArray();
        $times = $user->work_calendar()->where('year', $year)->where('type', UserWorkCalendar::STATUS_CUSTOM)->get()->keyBy('date');

        $this->breadcrumb_add(route('users.view', $user), $user->fullName);
        $this->breadcrumb_add('', 'Рабочий график');

        $user_working_time = $user->setting->get('user_work_times') ?? config('settings.working_time');

        return view('pub::user.work_calendar', [
            'user' => $user,
            'year' => $year,
            'dates' => $dates,
            'user_working_time' => $user_working_time,
            'user_custom_time' => $times,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Установка записей для рабочего календаря
     *
     * @param User $user
     * @param $date
     * @return \Illuminate\Contracts\View\View
     */
    public function work_calendar_set_time(User $user, $date = null)
    {
        if (empty($date)) abort(404);
        $has = $user->work_calendar()->where('date', $date)->where('type', UserWorkCalendar::STATUS_CUSTOM)->first();

        return View::make('pub.user.sidebars.work_calendar_set_time', ['title' => Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y'), 'user' => $user, 'date' => $date, 'has' => $has]);

    }

    /**
     * Box для смены пользователя
     *
     * @return false|\Illuminate\Contracts\View\View
     */
    public function box_mask()
    {
        if (!_can('super_user') && !Session::has('mask_admin'))
            return false;

        $users = UserRepository::getAll()->keyBy('id');

        $users->map(fn($item) => $item->url = route('users.mask', [$item, 'url' => $_SERVER['HTTP_REFERER']]));

        // определим у кого есть доступ
        $users_with_access = \App\Modules\Pub\Access\Services\AccessUserService::getUsersByAccess(\App\Modules\Pub\Access\Models\Access::find(1));
        $users = $users->filter(fn($user) => in_array($user->id, $users_with_access));

        $urls = $users->pluck('url', 'id');
        $template = View::make('pub.user.boxes.mask', [
            'title' => 'Смена пользователя',
            'users' => $users,
            'user' => Session::has('mask_admin') ? auth()->user() : null,
            'urls' => $urls
        ]);

        return $template;
    }

    /**
     * Смена пользователя
     *
     * @param Request $request
     * @param User $user
     * @return false|\Illuminate\Http\RedirectResponse
     */
    public function mask(Request $request, User $user)
    {
        if (!_can('super_user') && !Session::has('mask_admin'))
            return false;

        if (!Session::has('mask_admin'))
            Session::put('mask_admin', auth()->user()->api_token);

        $this->changeUser($user->id);

        // проверим доступ по URL
        $redirect_url = $request->get('url');

        return Redirect::to($redirect_url);
    }

    /**
     * Вернуться к своему профилю
     *
     * @param Request $request
     * @param $token
     * @return \Illuminate\Http\RedirectResponse|never
     */
    public function unmask(Request $request, $token)
    {
        if (!auth()->user()->silentAdmin())
            return abort(404);

        $user = User::where('api_token', $token)->first();
        if (empty($user))
            abort(404);

        Session::remove('mask_admin');
        $this->changeUser($user->id);

        // проверим доступ по URL
        $redirect_url = $request->get('url');

        return Redirect::to($redirect_url);
    }

}
