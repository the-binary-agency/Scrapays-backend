<?php


Route::resource('listedscrap', 'ListedScrapController');
Route::get('getAllUsers', 'ListedScrapController@getUsers');
Route::post('getSingleScrap', 'ListedScrapController@getSingleScrap');

Route::post('sendContactMessage', 'ContactMessageController@send');

Route::post('listCollectedScrap', 'CollectedScrapController@listCollectedScrap');

Route::group([

    'middleware' => 'api',
    'prefix' => 'auth'

], function () {

    Route::post('loginwithphone', 'AuthController@loginWithPhone');
    Route::post('loginwithemail', 'AuthController@loginWithEmail');
    Route::post('signup', 'AuthController@signup');
    Route::post('logout', 'AuthController@logout');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');
    Route::post('resetPassword', 'ChangePasswordController@process');
    Route::post('updateUser', 'AuthController@updateUser');

    Route::post('getUserWithID', 'AuthController@getUserWithID');
    Route::get('getAllUsers', 'AuthController@getUsers');
    Route::get('getAllAdmins', 'AuthController@getAdmins');
    Route::post('getUserWithToken', 'AuthController@getUserWithToken');
    Route::post('getCollectorWithToken', 'AuthController@getCollectorWithToken');
    Route::post('getProducerWithToken', 'AuthController@getProducerWithToken');
    Route::post('getVendorWithToken', 'AuthController@getVendorWithToken');

    Route::post('registerVendor', 'AuthController@registerVendor');
    Route::post('getApprovedCollectors', 'AuthController@getApprovedCollectors');
    Route::post('approveCollector', 'AuthController@approveCollector');

    Route::post('getDisposedTonnage', 'AuthController@getDisposedTonnage');


});