<?php


Route::resource('listedscrap', 'ListedScrapController');
Route::get('getAllUsers', 'ListedScrapController@getUsers');
Route::get('getSingleScrap/{id}', 'ListedScrapController@getSingleScrap');

Route::post('sendContactMessage', 'ContactMessageController@send');

Route::post('listCollectedScrap', 'CollectedScrapController@listCollectedScrap');

Route::get('unauthenticated', 'AuthController@unauthenticated')->name('unauthenticated');

Route::group([

    // 'middleware' => 'api',
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
    Route::put('updateUser/{id}', 'AuthController@updateUser');

    Route::get('getUserWithID/{id}', 'AuthController@getUserWithID');
    Route::get('getAllUsers/{id}', 'AuthController@getUsers');
    Route::get('getAllAdmins', 'AuthController@getAdmins');
    Route::get('getUserWithTonnage/{id}', 'AuthController@getUserWithTonnage');
    // Route::get('getCollectorWithTonnage/{id}', 'AuthController@getCollectorWithToken');
    // Route::get('getProducerWithTonnage/{id}', 'AuthController@getProducerWithToken');
    // Route::get('getVendorWithTonnage/{id}', 'AuthController@getVendorWithToken');

    Route::post('registerVendor', 'AuthController@registerVendor');
    Route::get('getApprovedCollectors/{id}', 'AuthController@getApprovedCollectors');
    Route::post('approveCollector', 'AuthController@approveCollector');

    Route::get('getProducedTonnage/{id}', 'AuthController@getProducedTonnage');
    Route::get('getDisposedTonnage/{id}', 'AuthController@getDisposedTonnage');

    Route::get('getUserCount/{id}', 'AuthController@getUserCount');

    Route::get('getMaterialPrices/{id}', 'AuthController@getMaterialPrices');
    Route::post('setMaterialPrices/{id}', 'AuthController@setMaterialPrices');
    Route::post('automatePickup', 'AuthController@automatePickup');
    
    Route::post('requestPickup', 'requestPickupController@initiateRequest');


});