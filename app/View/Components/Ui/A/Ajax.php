<?php

namespace App\View\Components\Ui\A;


use Illuminate\Support\Str;
use Illuminate\View\Component;

class Ajax extends Component
{
    public $params;
    public $uuid;

    public function __construct($url, $method = null, $data = [], $reload = false, $redirect = false, $dataType = 'json', $message = null, $submitMessage = null, $confirmMessage = null, $callback = null, $pre = null)
    {
        $params = [];
        $params['method'] = $method ?? 'POST';
        $params['url'] = $url . ((Str::lower($params['method']) == 'post') ? '?_token=' . _token() : '');
        $params['data'] = $data;
        $params['reload'] = $reload;
        $params['redirect'] = $redirect;
        $params['submit_message'] = $submitMessage;
        $params['dataType'] = $dataType;
        $params['message'] = $message;
        $params['confirm'] = $confirmMessage;
        $params['callback'] = $callback;
        $params['pre'] = $pre;
        $this->params = $params;
        $this->uuid = 'refresh' . Str::random(16);
    }

    /**
     * Вывод компонента
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.ui.a.ajax');
    }
}
