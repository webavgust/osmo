<?php

namespace App\View\Components\Client;


use App\Modules\Pub\Client\Models\Client;
use Illuminate\View\Component;

class ClientSearchSelector extends Component
{
    public $uid;
    public $client;
    public $app;

    public function __construct($uid, $clientId = null, $app = null)
    {
        $this->uid = $uid;
        $this->app = $app;
        if (!empty($clientId))
            $this->client = Client::find($clientId);
    }


    public function render()
    {
        return view('components.client.client_search_selector', [
            'uid' => $this->uid,
            'client' => $this->client,
            'app' => $this->app,
        ]);
    }
}
