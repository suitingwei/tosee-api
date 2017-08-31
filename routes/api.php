<?php

Route::group(['namespace' => 'V1'], function () {
    /**
     * Qiniu and Wechat notify callbacks.
     */
    Route::group(['prefix' => 'notify'], function () {
        Route::post('/qiniu/pfop', 'NotifyController@qiniuPfop');
        Route::post('/qiniu/upload', 'NotifyController@qiniuUpload');
        Route::post('/qiniu/merge_upload', 'NotifyController@qiniu_merge_upload');
        Route::post('/moneygift/wechat', 'NotifyController@moneyGiftWechat');
        Route::post('/pingpp/charge-success', 'NotifyController@pingppChargeSuccess');
        Route::post('/pingpp/transfer-success', 'NotifyController@pingppTransferSuccess');
        Route::post('/pingpp/refund-success', 'NotifyController@pingppRefundSuccess');
    });

    //Generate groupshoot thumbnail share picture.
    Route::get('qiniu/watermark/{text}', 'ShareController@watermark');
    //Generate groupshoot thumbnail share qrcode.
    Route::get('qiniu/qrcode/{url}', 'ShareController@qrcode');
    Route::get('agreement', 'SystemController@agreement');
    Route::get('about', 'SystemController@agreement');
    Route::get('utils/weather', 'UtilController@weather');
});

Route::group(['prefix' => 'v1', 'middleware' => ['Auth'], 'namespace' => 'V1'], function () {
    //Bind user's aliyun push token..
    Route::post('/users/{user}/push-token', 'UserController@bindPushToken');
    /**
     * Things about groupshoots.
     */
    Route::group(['prefix' => 'groupshoots'], function () {
        Route::post('/', 'GroupShootController@store')->middleware('Validate');
        Route::get('/{id}/edit', 'GroupShootController@edit');
        Route::get('/{id}/delete', 'GroupShootController@delete');
    });
    //Create a new money gift,red bag.
    Route::post('moneygifts', 'MoneyGiftController@store');
    //Get money gift pay info.(For now,there is only wechat pay channel)
    Route::get('moneygifts/{id}', 'MoneyGiftController@show');
    //Report a groupshoot.
    Route::post('reports/groupshoots', 'ReportController@store');
    //Like a groupshoot.
    Route::post('likes/groupshoots', 'LikeController@store');
    //UnLike a groupshoot.
    Route::delete('likes/groupshoots', 'LikeController@destroy');
    //User sign out.
    Route::get('signout', 'UserController@signout');
    /**
     * Things about play.
     */
    Route::group(['prefix' => 'plays'], function () {
        Route::post('plays', 'PlayController@store');
        Route::post('plays/{id}', 'PlayController@update');
        Route::get('plays/{id}', 'PlayController@show');
    });
});

Route::group(['prefix' => 'v1', 'namespace' => 'V1'], function () {
    //Get different resources for client.
    Route::get('resources', 'ResourcesController@index');
    //Get system info.
    Route::get('systems', 'SystemController@index');
    //Get verify sms code.
    Route::post('user/sms', 'UserController@sms');
    //Phone sign in.
    Route::post('signin', 'UserController@signin');
    //Phone sign up.
    Route::post('signup', 'UserController@signup');
    //Wechat sign in.
    Route::post('signin/wechat', 'UserController@signinWechat');
    //Get qiniu upload token.
    Route::post('resource/token', 'ResourceController@token');
    //Groupshoots.
    Route::group(['prefix' => 'groupshoots'], function () {
        //Get info the parent groupshoot.
        Route::get('/owner', 'GroupShootController@owner');
        Route::post('/verify', 'GroupShootController@verify');
        //Get the group shoot templates.
        Route::get('/templates', 'GroupShootController@avaibleTemplates');
        Route::group(['prefix' => '{id}'], function () {
            //Get the fules of the parent shoot.
            Route::get('/rules', 'GroupShootController@rules');
            //members in the groupshoot.
            Route::get('/members', 'GroupShootController@members');
            //Get the groupshoot's share info.
            Route::get('/share', 'ShareController@getGroupShootShareInfo');
            //Get merged covers image url.
            Route::get('/merged-covers', 'ShareController@getMergeGroupShootCovers');
            //Get the share image url.
            Route::get('/share-cover', 'ShareController@getShareCover');
            //Get the new share image info.
            Route::get('/share-new', 'ShareController@getNewGroupShootShareInfo');
            //Get the groupshoot detail info.
            Route::get('/', 'GroupShootController@show');
            //Update the groupshoot info.
            Route::put('/', 'GroupShootController@update');
        });
    });

    //Crop the square avatar  into circle.
    Route::get('/share/avatar-to-circle', 'ShareController@avatarToCircle');

    //Topics.
    Route::get('topics', ['middleware' => 'Etag', 'uses' => 'TopicController@index']);
    Route::get('topics/groupshoot', ['middleware' => 'Etag', 'uses' => 'TopicController@groupshoot']);
});

Route::get('/test', function () {
    function curl_file_get_contents($durl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, _USERAGENT_);
        curl_setopt($ch, CURLOPT_REFERER, _REFERER_);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
    }

    $url = 'http://wx.qlogo.cn/mmopen/xu0fLo9waqKSTDO7j0kSO41O5Luq3LB6ozUvY4O7OsXUWNicB49fBs8nGYzoqcwGDARQZHpVuic4JSDngEVjVo10BoiaPd0iciaOb/0';
    $a   = curl_file_get_contents($url);
    file_put_contents('uploads/2.jpg', $a);
});

