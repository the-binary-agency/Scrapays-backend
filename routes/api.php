<?php
use Illuminate\Support\Facades\Route;

/**
 * Public Routes
 */
// send Contact Message
Route::post('contactmessages', 'ContactMessageController@store');

// Route::post('refactor', 'UserController@refactor');

/**
 * Public Auth
 */
Route::group([

    'prefix' => 'auth'

], function () {

    /**
     * Register Admin
     */
    Route::post('admins/register', 'Auth\RegisterController@admin');
    /**
     * Register Household
     */
    Route::post('households/register', 'Auth\RegisterController@household');
    /**
     * Register USSD
     */
    Route::post('ussd/register', 'Auth\RegisterController@ussd');
    /**
     * Register Enterprise
     */
    Route::post('enterprises/register', 'Auth\RegisterController@enterprise');
    /**
     * Register Collector
     */
    Route::post('collectors/register', 'Auth\RegisterController@collector');
    /**
     * Register Host
     */
    Route::post('hosts/register', 'Auth\RegisterController@host');

    /**
     * Login with email
     */
    Route::post('email/login', 'Auth\LoginController@email');
    /**
     * Login with phone number
     */
    Route::post('phone/login', 'Auth\LoginController@phone');

    /**
     * Send password reset email
     */
    Route::post('password/email', 'Auth\ForgotPasswordController@sendEmail');
    /**
     * Reset password
     */
    Route::post('password/reset', 'Auth\ResetPasswordController@process');
});

/**
 * Private Routes
 */
Route::group([

    'middleware' => 'auth'

], function () {

    /**
     * Private Auth
     */
    Route::group([

        'prefix' => 'auth'

    ], function () {
        /**
         * Users
         */
        Route::get('users/{user}/notifications', 'UserController@getNotifications');

        /**
         * Admin
         */
        //  Get logged in admin
        Route::get('admins/me', 'Auth\AdminAuthController@me')->middleware(['admin']);
        // Update Admin
        Route::put('admins/{admin}', 'Auth\AdminAuthController@update')->middleware(['admin']);

        /**
         * Household
         */
        // Get logged in Household
        Route::get('households/me', 'Auth\HouseholdAuthController@me');
        // Update Household
        Route::put('households/{household}', 'Auth\HouseholdAuthController@update');
        // Update Household through USSD
        Route::put('households/ussd/{phone}', 'Auth\HouseholdAuthController@updateUSSD');

        /**
         * Enterprise
         */
        // Get logged in Enterprise
        Route::get('enterprises/me', 'Auth\EnterpriseAuthController@me');
        //  Update Enterprise
        Route::put('enterprises/{enterprise}', 'Auth\EnterpriseAuthController@update');
        //  Automate Enterprise Pickup
        Route::put('enterprises/pickup/{enterprise}/automate', 'Auth\EnterpriseAuthController@automatePickup');
        //  Un-automate Enterprise Pickup
        Route::put('enterprises/pickup/{enterprise}/unautomate', 'Auth\EnterpriseAuthController@unAutomatePickup');

        /**
         * Collector
         */
        // Get logged in Collector
        Route::get('collectors/me', 'Auth\CollectorAuthController@me');
        //  Update Collector
        Route::put('collectors/{collector}', 'Auth\CollectorAuthController@update');

        /**
         * Host
         */
        // Get logged in Host
        Route::get('hosts/me', 'Auth\HostAuthController@me');
        //  Update Host
        Route::put('hosts/{host}', 'Auth\HostAuthController@update');
    });

    /**
     * Location
     */
    // Ping Collector Location
    Route::post('locations/ping', 'LocationController@ping');
    // Get collection address with coordinates
    Route::get('locations/address', 'LocationController@getAddressWithCoordinates');

    /**
     * Users
     */
    //  Search for Producer with phone number or ID
    Route::get('users/{phone}/producer-name', 'UserController@getProducerName');

    /**
     * Pickup Requests
     */
    Route::get('pickuprequests/{enterprise}/cancel', 'PickupRequestController@cancel');

    /**
     * Wallets
     */
    Route::get('wallets/{user}/balance', 'WalletController@balance');
    Route::post('wallets/withdraw', 'WalletController@withdraw');
    Route::post('wallets/transfer', 'WalletController@transfer');
    Route::post('wallets/airtime', 'WalletController@airtime');
    Route::get('wallets/{user}/withdrawal-history', 'WalletController@getWithdrawalHistory');

    /**
     * Enterprises
     */
    // Get enterprise produced scrap
    Route::get('enterprises/{enterprise}/producedscraps', 'EnterpriseController@producedScrap');

    /**
     * Households
     */
    // Get household produced scrap
    Route::get('households/{household}/producedscraps', 'HouseholdController@producedScrap');

    /**
     * Collectors
     */
    // Get collector Collected scrap
    Route::get('collectors/{collector}/collectedscraps', 'CollectorController@collectedScrap');
    // Get collector assigned pickups
    Route::get('collectors/{collector}/pickuprequests', 'CollectorController@pickupRequests');

    /**
     * Collectedscraps
     */
    // Get all collectedscrap history
    Route::get('collectedscraps/history', 'CollectedScrapController@getAllScrapHistory');
    // Get produced tonnage
    Route::get('collectedscraps/{producer}/producedtonnage', 'CollectedScrapController@producedTonnage');
    // Get collected tonnage
    Route::get('collectedscraps/{collector}/collectedtonnage', 'CollectedScrapController@collectedTonnage');
    // Get a single user's scrap history
    Route::get('collectedscraps/{user}/history', 'UserController@getSingleScrapHistory');

    /**
     * Materials
     */
    // Get materials
    Route::get('materials', 'MaterialController@index');

    /**
     * Collected Scraps
     */
    //  List
    Route::post('collectedscraps', 'CollectedScrapController@store');

    /**
     * Pickup Requests
     */
    Route::resource('pickuprequests', 'PickupRequestController', ['only' => ['store', 'index']]);
    // Request a pickup through USSD
    Route::post('pickuprequests/ussd', 'PickupRequestController@storeUssdPickup');

    /**
     * Inventories
     */
    Route::resource('inventories', 'InventoryController', ['only' => ['store']]);

    /**
     * Admin-Only -----------------------------------------------------------------------------------------------------------------------
     */
    /**
     * Users
     */
    Route::get('users/count', 'UserController@getUserCount')->middleware('authorize:can_view_users');
    Route::get('users/search', 'UserController@searchUsers')->middleware('authorize:can_view_users');
    Route::resource('users', 'UserController', ['only' => ['index', 'show']])->middleware('authorize:can_view_users');
    Route::get('users/{phone}/phone', 'UserController@getUserWithPhone')->middleware('authorize:can_view_users');
    Route::get('users/{phone}/name', 'UserController@getUserName')->middleware('authorize:can_view_users');

    /**
     * Admins
     */
    Route::resource('admins', 'AdminController', ['only' => ['index', 'show', 'destroy']])->middleware('authorize:can_view_users');
    Route::put('admins/{admin}/change-permissions', 'AdminController@changePermissions')->middleware('authorize:all');

    /**
     * Enterprises
     */
    Route::resource('enterprises', 'EnterpriseController', ['only' => ['index', 'show', 'destroy']])->middleware('authorize:can_view_producers');

    /**
     * Households
     */
    Route::resource('households', 'HouseholdController', ['only' => ['index', 'show', 'destroy']])->middleware('authorize:can_view_producers');

    /**
     * Collectors
     */
    Route::resource('collectors', 'CollectorController', ['only' => ['index', 'show', 'destroy']])->middleware('authorize:can_view_users');
    // Toggle collector Status
    Route::get('collectors/{collector}/togglestatus', 'CollectorController@toggle')->middleware('authorize:can_give_access_to_collector');
    // Get Collector Details
    Route::get('collectors/{collector}/details', 'CollectorController@details')->middleware('authorize:can_view_users');

    /**
     * Hosts
     */
    Route::resource('hosts', 'HostController', ['only' => ['index', 'show', 'destroy']])->middleware('authorize:can_view_users');

    /**
     * Collected Scraps
     */
    Route::resource('collectedscraps', 'CollectedScrapController', ['except' => ['update', 'store']])->middleware('authorize:can_view_listing');

    /**
     * Contact Messages
     */
    Route::resource('contactmessages', 'ContactMessageController', ['except' => ['update']])->middleware('authorize:can_view_messages');
    Route::post('contactmessages/{contactmessage}/reply', 'ContactMessageController@reply')->middleware('authorize:can_view_messages');

    /**
     * Inventories
     */
    Route::resource('inventories', 'InventoryController', ['except' => ['update', 'store']])->middleware('authorize:can_view_users');

    /**
     * Listed Scraps
     */
    Route::resource('listedscraps', 'ListedScrapController', ['except' => ['update']])->middleware('authorize:can_view_listing');

    /**
     * Locations
     */
    Route::get('locations', 'LocationController@getLocations')->middleware('authorize:can_view_users');

    /**
     * Materials
     */
    Route::resource('materials', 'MaterialController', ['except' => ['index']])->middleware('authorize:can_update_materials');

    /**
     * Pickup Requests
     */
    // Assign collector to pickup
    Route::put('pickuprequests/assign', 'PickupRequestController@assign')->middleware('authorize:can_view_users');
    // Get Pickup request Count
    Route::get('pickuprequests/count', 'PickupRequestController@count')->middleware('authorize:can_view_users');

    /**
     * Wallet
     */
    Route::resource('wallets', 'WalletController', ['only' => ['update', 'destroy']])->middleware('authorize:can_view_users');
    Route::get('wallets/{user}/history', 'WalletController@getCombinedWalletHistory')->middleware('authorize:can_view_users');
    Route::post('wallets/set-pin', 'WalletController@changeWalletPin')->middleware('authorize:all');

});
