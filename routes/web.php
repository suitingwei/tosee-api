<?php

Route::get('/', function () {
    return redirect()->to('/logs');
});

Route::auth();

Route::get('logs', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');

/**
 * Mobile routes for h5 api.
 */
Route::group(['prefix' => 'v1/mobile', 'namespace' => 'V1\Mobile'], function () {
    Route::get('/groupshoots/{id}', 'GroupShootController@show');
});

/**
 * Admin routes.
 */
Route::group(['prefix' => 'admin', 'namespace' => 'V1\Admin'], function () {
    Route::post('/login', 'LoginController@login');
    Route::group(['middleware' => 'auth'], function () {
        Route::get('/', 'AdminController@index');

        Route::group(['prefix' => 'groupshoots'], function () {
            Route::put('/templates/{template}/sorts','GroupShootTemplatesController@updateSort');
            Route::resource('/templates', 'GroupShootTemplatesController');
        });
    });
});

