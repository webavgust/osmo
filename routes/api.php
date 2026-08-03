<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'spider', 'middleware' => ['ajax.api']], function() {
    Route::post('tick', function (Request $request) {

        $request->validate([
            'is_active' => 'bool|nullable',
            'toast_count' => 'int|nullable',
            'page' => 'string|nullable'
        ]);

        $data = $request->all();

        return \App\Services\Spider\Spider::getStatus($data);
    })->name('spider.tick');
});
