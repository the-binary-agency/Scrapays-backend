<?php
use Illuminate\Support\Facades\Route;

Route::get('unauthenticated', 'AuthController@unauthenticated')->name('unauthenticated');

Route::post('registerAdmin', 'AuthController@registerAdmin');

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
Route::post('sendContactMessage', 'ContactMessageController@send');

Route::group([

    'middleware' => 'auth:api',

], function () {

    // Users
    Route::post('updateUser/{id}', 'AuthController@updateUser');
    Route::post('updateUssdUser/{id}', 'AuthController@updateUssdUser');
    Route::post('getUserWithID', 'AuthController@getUserWithID');
    Route::get('getUserDetails/{id}', 'AuthController@getUserDetails');
    Route::post('getAllUsers', 'AuthController@getUsers');
    Route::get('getAllAdmins', 'AuthController@getAdmins');
    Route::get('getUserWithTonnage/{id}', 'AuthController@getUserWithTonnage');
    Route::get('getUserWithNotifications/{id}', 'AuthController@getUserWithNotifications');
    Route::get('getCollectorWithTonnage/{id}', 'AuthController@getCollectorWithTonnage');
    Route::post('getUserName', 'AuthController@getUserName');
    Route::post('toggleCollectorStatus', 'AuthController@toggleCollectorStatus');
    Route::post('deleteUser', 'AuthController@deleteUser');
    Route::get('getProducedTonnage/{id}', 'AuthController@getProducedTonnage');
    Route::get('getDisposedTonnage/{id}', 'AuthController@getDisposedTonnage');
    Route::get('getMaterialsSalesHistory/{id}', 'AuthController@getMaterialsSalesHistory');
    Route::get('getUserCount/{id}', 'AuthController@getUserCount');
    Route::post('registerVendor', 'AuthController@registerVendor');
    Route::get('getApprovedCollectors/{id}', 'AuthController@getApprovedCollectors');
    Route::post('approveCollector', 'AuthController@approveCollector');

    // Material Prices
    Route::get('getMaterialPrices/{id}', 'MaterialPricesController@getMaterialPrices');
    Route::post('setMaterialPrices/{id}', 'MaterialPricesController@setMaterialPrices');
    Route::post('editMaterialPrices/{id}', 'MaterialPricesController@editMaterialPrices');
    Route::post('deleteMaterialPrices/{id}', 'MaterialPricesController@deleteMaterialPrices');

    Route::post('automatePickup', 'AuthController@automatePickup');
    Route::post('unAutomatePickup', 'AuthController@unAutomatePickup');

    // Pickup Requests
    Route::post('requestPickup', 'requestPickupController@initiateRequest');
    Route::post('requestUssdPickup', 'requestPickupController@initiateUssdRequest');
    Route::post('cancelPickup', 'requestPickupController@cancelPickup');
    Route::get('getAllPickupRequests/{id}', 'requestPickupController@getAllPickupRequests');
    Route::get('getPickupRequestCounts/{id}', 'requestPickupController@getPickupRequestCounts');
    Route::post('getCollectorWithLog', 'requestPickupController@getCollectorWithLog');
    Route::post('assignToCollector', 'requestPickupController@assignToCollector');
    Route::get('getAssignedRequests/{id}', 'requestPickupController@getAssignedPickups');

    // ListedScrap routes
    Route::get('getAllUsers', 'ListedScrapController@getUsers');
    Route::get('getSingleScrap/{id}', 'ListedScrapController@getSingleScrap');
    Route::get('listedscrap', 'ListedScrapController@index');

    Route::post('listCollectedScrap', 'CollectedScrapController@listCollectedScrap');
    Route::get('getCollectorCollections/{id}', 'CollectedScrapController@getCollectorCollections');
    Route::get('getCollections/{id}', 'CollectedScrapController@getCollections');
    Route::get('getCollectionsHistory', 'CollectedScrapController@getHistory');
    Route::get('getCollectorCollectionsHistory/{id}', 'CollectedScrapController@getCollectorCollectionsHistory');
    Route::get('getProducerCollectionsHistory/{id}', 'CollectedScrapController@getProducerCollectionsHistory');
    Route::get('getCollectionsHistoryWithQuery/{id}', 'CollectedScrapController@getCollectionsHistoryWithQuery');
    Route::get('getCollectorCollectionsHistoryWithQuery/{id}', 'CollectedScrapController@getCollectorCollectionsHistoryWithQuery');
    Route::post('listScrap', 'ListedScrapController@list');


    // Notifications
    Route::post('toggleNotifications', 'NotificationController@toggleNotifications');
    Route::post('deleteNotifications', 'NotificationController@deleteNotifications');

    // Inventory
    Route::post('submitInventory', 'InventoryController@submitInventory');
    Route::post('getUserInventory', 'InventoryController@getUserInventory');

    // Wallet
    Route::get('getwalletbalance/{id}', 'WalletController@getWalletBalance');
    Route::get('getwallethistory/{id}', 'WalletController@getWalletHistory');
    Route::post('withdrawfromwallet', 'WalletController@withdrawFromWallet');
    Route::post('creditwallet', 'WalletController@creditWallet');
    Route::get('buyAirtime', 'WalletController@buyAirtime');

    // Location
    Route::get('getlocations', 'LocationDataController@getLocations');
    Route::post('ping', 'MapDataController@ping');
    Route::get('getAddressWithCoordinates/{loc}', 'MapDataController@getAddressWithCoordinates');

    // Messages  
    Route::get('getAllContactMessages', 'ContactMessageController@get');
    Route::post('replyContactMessage', 'ContactMessageController@reply');
    Route::delete('deleteContactMessage/{id}', 'ContactMessageController@delete');
});