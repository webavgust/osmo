<?php


namespace App\Modules\Pub\User\Repositories;


use App\Facades\Tools;
use App\Services\Portal\Repository\AbstractPortalRepository;
use Illuminate\Support\Facades\Log;

class UserPortalRepository extends AbstractPortalRepository
{
    /**
     * Получение списка пользователей из портала
     *
     * @return array
     */
    public function getAll(): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=user';

        return $this->getData($url);
    }

    /**
     * Получить одну запись из портала
     *
     * @param int $id
     * @return array
     */
    public function getOne(int $id): array
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=user&id=' . $id;

        return $this->getData($url)[0];
    }

    /**
     * Проверка аутентификации
     *
     * @param $login
     * @param $password
     * @param $unsafe
     * @return bool
     */
    public function checkAuth($login, $password, $unsafe = 0): bool
    {
        $login = trim($login);
        $password = trim($password);

        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=check_user_login';

        $login_crypt = str_replace("\0", "#NULL#", openssl_encrypt($login, 'aes256', env('API_TOKEN'), OPENSSL_RAW_DATA, md5(env('API_TOKEN'), 1)));
        $password_crypt = str_replace("\0", "#NULL#", openssl_encrypt($password, 'aes256', env('API_TOKEN'), OPENSSL_RAW_DATA, md5(env('API_TOKEN'), 1)));

        if ($unsafe) {
            return $this->getAuth($url, ['login' => $login, 'password' => $password, 'unsafe' => $unsafe]);
        } else {
            return $this->getAuth($url, ['login' => $login_crypt, 'password' => $password_crypt]);
        }
    }

    /**
     * Проверка логина на существование
     *
     * @param $login
     * @return bool
     */
    public function isLoginExist($login): bool
    {
        $url = env('PORTAL_URL') . '/api/?token=' . env('API_TOKEN') . '&qr=check_user_exist';
        $response = Tools::CurlPostJson($url, ["login" => $login]);

        return !empty($response['status']) && $response['status'] == self::STATUS_SUCCESS;
    }
}
