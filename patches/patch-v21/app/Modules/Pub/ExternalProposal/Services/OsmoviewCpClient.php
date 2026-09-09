<?php

namespace App\Modules\Pub\ExternalProposal\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Клиент API OSMOVIEW CP Generator (patch v21).
 *
 * По документации: базовый URL и ключ в `config/services.php` (`osmoview_cp`),
 * ключ уходит заголовком `x-api-key`. Методы:
 *   GET /api/cp/list        → {success, count, data: [{id, projectName, ...}]}
 *   GET /api/cp/detail/{id} → {success, data: {...}}
 *
 * Известный блокер: обвязка AI Studio отвечает 302 на проверку cookie —
 * такой ответ (как и любой не-JSON) превращается в исключение с понятным
 * текстом, обходить его клиент не пытается.
 */
class OsmoviewCpClient
{
    private string $base_url;
    private string $api_key;
    private int $timeout;

    public function __construct(?string $base_url = null, ?string $api_key = null, ?int $timeout = null)
    {
        $config = config('services.osmoview_cp', []);

        $this->base_url = rtrim((string) ($base_url ?? $config['base_url'] ?? ''), '/');
        $this->api_key = (string) ($api_key ?? $config['api_key'] ?? '');
        $this->timeout = (int) ($timeout ?? $config['timeout'] ?? 20);
    }

    /**
     * Настроен ли клиент (есть URL и ключ)
     *
     * @return bool
     */
    public function configured(): bool
    {
        return $this->base_url !== '' && $this->api_key !== '';
    }

    /**
     * Список КП
     *
     * @return array строки `data` ответа
     * @throws \RuntimeException
     */
    public function list(): array
    {
        $json = $this->get('/api/cp/list');

        $rows = $json['data'] ?? null;
        if (!is_array($rows)) {
            throw new \RuntimeException('API вернул список без поля data');
        }

        return $rows;
    }

    /**
     * Детальная запись КП
     *
     * @param string $external_id doc-id из списка
     * @return array содержимое `data`
     * @throws \RuntimeException
     */
    public function detail(string $external_id): array
    {
        $json = $this->get('/api/cp/detail/' . rawurlencode($external_id));

        $data = $json['data'] ?? null;
        if (!is_array($data)) {
            throw new \RuntimeException('API вернул detail без поля data');
        }

        return $data;
    }

    /**
     * GET с разбором ответа. 3xx, HTML и не-JSON — исключение.
     *
     * @param string $path
     * @return array
     * @throws \RuntimeException
     */
    private function get(string $path): array
    {
        if (!$this->configured()) {
            throw new \RuntimeException('API OSMOVIEW CP не настроен: заполните OSMOVIEW_CP_URL и OSMOVIEW_CP_KEY в .env');
        }

        try {
            $response = Http::withHeaders([
                    'x-api-key' => $this->api_key,
                    'Accept' => 'application/json',
                ])
                ->withoutRedirecting()
                ->timeout($this->timeout)
                ->get($this->base_url . $path);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Не удалось обратиться к API OSMOVIEW CP: ' . $e->getMessage(), 0, $e);
        }

        return $this->parse($response, $path);
    }

    /**
     * Разбор ответа
     *
     * @param Response $response
     * @param string $path
     * @return array
     * @throws \RuntimeException
     */
    private function parse(Response $response, string $path): array
    {
        $status = $response->status();

        if ($status >= 300 && $status < 400) {
            $location = (string) $response->header('Location');
            $hint = str_contains($location, 'cookie_check')
                ? 'обвязка AI Studio пускает только браузер (проверка cookie), нужен прямой URL Cloud Run без неё'
                : 'переадресация на ' . ($location ?: '?');

            throw new \RuntimeException("API OSMOVIEW CP ответил {$status} на {$path}: {$hint}");
        }

        if ($status === 401 || $status === 403) {
            throw new \RuntimeException("API OSMOVIEW CP отказал в доступе ({$status}): проверьте ключ OSMOVIEW_CP_KEY");
        }

        if ($status >= 400) {
            throw new \RuntimeException("API OSMOVIEW CP ответил ошибкой {$status} на {$path}");
        }

        $body = trim((string) $response->body());
        $content_type = strtolower((string) $response->header('Content-Type'));

        if ($body === '' || str_starts_with($body, '<') || (str_contains($content_type, 'text/html'))) {
            throw new \RuntimeException("API OSMOVIEW CP вернул HTML вместо JSON на {$path}: похоже, включена проверка cookie AI Studio");
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            throw new \RuntimeException("API OSMOVIEW CP вернул не-JSON на {$path}: " . mb_substr($body, 0, 120));
        }

        if (array_key_exists('success', $json) && !$json['success']) {
            throw new \RuntimeException('API OSMOVIEW CP: ' . ($json['error'] ?? $json['message'] ?? 'success = false'));
        }

        return $json;
    }
}
