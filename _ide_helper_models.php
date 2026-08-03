<?php

// @formatter:off
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Modules\Pub\AccessGroup\Models{
/**
 * App\Modules\Pub\AccessGroup\Models\AccessGroup
 *
 * @property int $id
 * @property int $protected
 * @property string $name
 * @property string|null $prefix
 * @property string|null $icon
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $accesses
 * @property-read int|null $accesses_count
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup wherePrefix($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereProtected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AccessGroup whereUpdatedAt($value)
 */
	class AccessGroup extends \Eloquent {}
}

namespace App\Modules\Pub\Access\Models{
/**
 * App\Modules\Pub\Access\Models\Access
 *
 * @property int $id
 * @property int $access_group_id
 * @property int $protected
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property int $sort
 * @property string $class
 * @property string $method
 * @property int|null $admin_invert
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\Pub\AccessGroup\Models\AccessGroup $access_group
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Menu\Models\Menu[] $menus
 * @property-read int|null $menus_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\UserDepartment\Models\UserDepartment[] $user_departments
 * @property-read int|null $user_departments_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\UserGroup\Models\UserGroup[] $user_groups
 * @property-read int|null $user_groups_count
 * @method static \Illuminate\Database\Eloquent\Builder|Access newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Access newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Access query()
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereAccessGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereAdminInvert($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereProtected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Access whereUpdatedAt($value)
 */
	class Access extends \Eloquent {}
}

namespace App\Modules\Pub\Breadcrumbs\Models{
/**
 * App\Modules\Pub\Breadcrumbs\Models\Breadcrumb
 *
 * @method static \Illuminate\Database\Eloquent\Builder|Breadcrumb newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Breadcrumb newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Breadcrumb query()
 */
	class Breadcrumb extends \Eloquent {}
}

namespace App\Modules\Pub\Menu\Models{
/**
 * App\Modules\Pub\Menu\Models\Menu
 *
 * @property int $id
 * @property int $active
 * @property int $parent_id
 * @property int $protected
 * @property string $name
 * @property string|null $url
 * @property string|null $icon
 * @property int $sort
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $accesses
 * @property-read int|null $accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|Menu[] $children
 * @property-read int|null $children_count
 * @property-read Menu|null $parent
 * @method static \Illuminate\Database\Eloquent\Builder|Menu isLive()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu ofSort($sort)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu query()
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereProtected($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereSort($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Menu whereUrl($value)
 */
	class Menu extends \Eloquent {}
}

namespace App\Modules\Pub\OrderComment\Models{
/**
 * App\Modules\Pub\OrderComment\Models\OrderComment
 *
 * @property int $id
 * @property int $order_id
 * @property int $user_id
 * @property string|null $control_first
 * @property string|null $control_second
 * @property string $text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\Pub\Order\Models\Order|null $order
 * @property-read \App\Modules\Pub\User\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereControlFirst($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereControlSecond($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereText($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OrderComment whereUserId($value)
 */
	class OrderComment extends \Eloquent {}
}

namespace App\Modules\Pub\Order\Models{
/**
 * App\Modules\Pub\Order\Models\Order
 *
 * @property int $id
 * @property string $order_name Название
 * @property string|null $order_sent_to_techdep
 * @property int $is_archived Архивная?
 * @property int $is_finished Закончена?
 * @property int $customer_id
 * @property string $customer_name Заказчик
 * @property int|null $contract_id
 * @property string|null $contract_conclusion Дата заключения контракта
 * @property int|null $author_id Автор
 * @property int|null $manager_id Менеджер
 * @property int|null $curator_id Куратор
 * @property string|null $last_control_date
 * @property string|null $second_control_date
 * @property int|null $md_specify_days
 * @property string|null $md_specify_finaldate
 * @property string|null $md_specify_periodicity
 * @property string|null $md_specify_locationplace
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Modules\Pub\User\Models\User|null $author
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\OrderComment\Models\OrderComment[] $comments
 * @property-read int|null $comments_count
 * @property-read \App\Modules\Pub\User\Models\User|null $curator
 * @property-read \App\Modules\Pub\User\Models\User|null $manager
 * @method static \Illuminate\Database\Eloquent\Builder|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder|Order search($search)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereContractConclusion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereContractId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCuratorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereCustomerName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIsArchived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereIsFinished($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereLastControlDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereManagerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereMdSpecifyDays($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereMdSpecifyFinaldate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereMdSpecifyLocationplace($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereMdSpecifyPeriodicity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereOrderSentToTechdep($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereSecondControlDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Order whereUpdatedAt($value)
 */
	class Order extends \Eloquent {}
}

namespace App\Modules\Pub\UserDepartment\Models{
/**
 * App\Modules\Pub\UserDepartment\Models\UserDepartment
 *
 * @property int $id
 * @property int $active
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $accesses
 * @property-read int|null $accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\User\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserDepartment whereUpdatedAt($value)
 */
	class UserDepartment extends \Eloquent {}
}

namespace App\Modules\Pub\UserGroup\Models{
/**
 * App\Modules\Pub\UserGroup\Models\UserGroup
 *
 * @property int $id
 * @property int $active
 * @property string $name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $accesses
 * @property-read int|null $accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\User\Models\User[] $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|UserGroup whereUpdatedAt($value)
 */
	class UserGroup extends \Eloquent {}
}

namespace App\Modules\Pub\User\Models{
/**
 * App\Modules\Pub\User\Models\User
 *
 * @property int $id
 * @property string|null $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $login
 * @property string $password
 * @property int $active
 * @property string|null $last_name
 * @property string|null $second_name
 * @property int|null $personal_gender
 * @property mixed|null $personal_photo
 * @property string|null $personal_mobile
 * @property string|null $work_department
 * @property string|null $work_position
 * @property string|null $work_phone
 * @property string|null $personal_birthday
 * @property int $is_sync
 * @property int $is_admin
 * @property string|null $api_token
 * @property string|null $ajax_token
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $accesses
 * @property-read int|null $accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\UserDepartment\Models\UserDepartment[] $departments
 * @property-read int|null $departments_count
 * @property-read mixed $full_name
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Access\Models\Access[] $group_accesses
 * @property-read int|null $group_accesses_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\UserGroup\Models\UserGroup[] $groups
 * @property-read int|null $groups_count
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection|\Illuminate\Notifications\DatabaseNotification[] $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Order\Models\Order[] $ordersAsAuthor
 * @property-read int|null $orders_as_author_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Order\Models\Order[] $ordersAsCurator
 * @property-read int|null $orders_as_curator_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Modules\Pub\Order\Models\Order[] $ordersAsManager
 * @property-read int|null $orders_as_manager_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\Laravel\Sanctum\PersonalAccessToken[] $tokens
 * @property-read int|null $tokens_count
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User search($search)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereAjaxToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereApiToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereIsSync($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLogin($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePersonalBirthday($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePersonalGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePersonalMobile($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePersonalPhoto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereSecondName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWorkDepartment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWorkPhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereWorkPosition($value)
 */
	class User extends \Eloquent {}
}

namespace App\Modules\Pub\WorkCalendar\Models{
/**
 * App\Modules\Pub\WorkCalendar\Models\WorkCalendar
 *
 * @property int $day
 * @property int $month
 * @property int $year
 * @property string $date
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar query()
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar untilDate($date)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|WorkCalendar whereYear($value)
 */
	class WorkCalendar extends \Eloquent {}
}

