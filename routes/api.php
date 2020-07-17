<?php

Route::get('unauthenticated', 'AuthController@unauthenticated')->name('unauthenticated');

Route::group([

    // 'middleware' => 'auth:api',

], function () {

    Route::post('loginwithphone', 'AuthController@loginWithPhone');
    Route::post('loginwithemail', 'AuthController@loginWithEmail');
    Route::post('registerwithussd', 'AuthController@registerwithussd');
    Route::post('registerEnterprise', 'AuthController@registerEnterprise');
    Route::post('registerHousehold', 'AuthController@registerHousehold');
    Route::post('registerHost', 'AuthController@registerHost');
    Route::post('registerCollector', 'AuthController@registerCollector');
    Route::post('registerAgent', 'AuthController@registerAgent');
    Route::post('refresh', 'AuthController@refresh');
    Route::post('me', 'AuthController@me');
    Route::post('sendPasswordResetLink', 'ResetPasswordController@sendEmail');
    Route::post('resetPassword', 'ChangePasswordController@process');
    Route::post('updateUser/{id}', 'AuthController@updateUser');

    Route::get('getUserWithID/{id}', 'AuthController@getUserWithID');
    Route::get('getAllUsers/{id}', 'AuthController@getUsers');
    Route::get('getAllAdmins', 'AuthController@getAdmins');
    Route::get('getUserWithTonnage/{id}', 'AuthController@getUserWithTonnage');
    Route::get('getUserWithNotifications/{id}', 'AuthController@getUserWithNotifications');
    Route::get('getCollectorWithTonnage/{id}', 'AuthController@getCollectorWithTonnage');
    Route::post('getUserName', 'AuthController@getUserName');

    Route::post('registerVendor', 'AuthController@registerVendor');
    Route::get('getApprovedCollectors/{id}', 'AuthController@getApprovedCollectors');
    Route::post('approveCollector', 'AuthController@approveCollector');

    Route::get('getProducedTonnage/{id}', 'AuthController@getProducedTonnage');
    Route::get('getDisposedTonnage/{id}', 'AuthController@getDisposedTonnage');

    Route::get('getUserCount/{id}', 'AuthController@getUserCount');

    Route::get('getMaterialPrices/{id}', 'MaterialPricesController@getMaterialPrices');
    Route::post('setMaterialPrices/{id}', 'MaterialPricesController@setMaterialPrices');
    Route::post('editMaterialPrices/{id}', 'MaterialPricesController@editMaterialPrices');
    Route::post('automatePickup', 'AuthController@automatePickup');
    Route::post('unAutomatePickup', 'AuthController@unAutomatePickup');
    
    Route::post('requestPickup', 'requestPickupController@initiateRequest');
    Route::post('cancelPickup', 'requestPickupController@cancelPickup');

    // ListedScrap routes
    Route::post('listScrap', 'ListedScrapController@list');
    Route::get('getAllUsers', 'ListedScrapController@getUsers');
    Route::get('getSingleScrap/{id}', 'ListedScrapController@getSingleScrap');
    Route::post('listCollectedScrap', 'CollectedScrapController@listCollectedScrap');

    // Contact Message routes
    Route::post('sendContactMessage', 'ContactMessageController@send');

    // Notifications
    Route::post('toggleNotifications', 'NotificationController@toggleNotifications');
    Route::post('deleteNotifications', 'NotificationController@deleteNotifications');

    // Inventory
    Route::post('submitInventory', 'InventoryController@submitInventory');
    Route::post('getUserInventory', 'InventoryController@getUserInventory');

    Route::get('getlocations', 'LocationDataController@getLocations');

});