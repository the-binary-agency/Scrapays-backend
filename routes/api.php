<?php
use Illuminate\Support\Facades\Route;

/**
 * Public Routes
 */
// send Contact Message
Route::post('contactmessages/send', 'ContactMessageController@store');

Route::post('refactor', 'UserController@refactor');

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
     * Login with USSD
     */
    Route::post('ussd/login', 'Auth\LoginController@ussd');

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
         * Hosehold
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
        //  Unautomate Enterprise Pickup
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
    //  Search for User with phone number or ID
    Route::get('users/{phone}/name', 'UserController@getUserName');

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
     * Admin-Only
     */
    Route::group([

        'middleware' => 'authorize'

    ], function () {

        /**
         * Users
         */
        Route::get('users/count', 'UserController@getUserCount');
        Route::resource('users', 'UserController', ['only' => ['index', 'show']]);
        Route::get('users/{phone}/phone', 'UserController@getUserWithPhone');

        /**
         * Admins
         */
        Route::resource('admins', 'AdminController', ['only' => ['index', 'show', 'destroy']]);

        /**
         * Enterprises
         */
        Route::resource('enterprises', 'EnterpriseController', ['only' => ['index', 'show', 'destroy']]);

        /**
         * Households
         */
        Route::resource('households', 'HouseholdController', ['only' => ['index', 'show', 'destroy']]);

        /**
         * Collectors
         */
        Route::resource('collectors', 'CollectorController', ['only' => ['index', 'show', 'destroy']])->middleware(['admin:role']);
        // Toggle collector Status
        Route::get('collectors/{collector}/togglestatus', 'CollectorController@toggle');
        // Get Collector Details
        Route::get('collectors/{collector}/details', 'CollectorController@details');

        /**
         * Hosts
         */
        Route::resource('hosts', 'HostController', ['only' => ['index', 'show', 'destroy']]);

        /**
         * Collected Scraps
         */
        Route::resource('collectedscraps', 'CollectedScrapController', ['except' => ['update', 'store']]);

        /**
         * Contact Messages
         */
        Route::resource('contactmessages', 'ContactMessageController', ['except' => ['update']]);
        Route::post('contactmessages/{contactmessage}/reply', 'ContactMessageController@reply');

        /**
         * Inventories
         */
        Route::resource('inventories', 'InventoryController', ['except' => ['update', 'store']]);

        /**
         * Listed Scraps
         */
        Route::resource('listedscraps', 'ListedScrapController', ['except' => ['update']]);

        /**
         * Locations
         */
        Route::get('locations', 'LocationController@getLocations');

        /**
         * Materials
         */
        Route::resource('materials', 'MaterialController', ['except' => ['index']]);

        /**
         * Pickup Requests
         */
        // Assign collector to pickup
        Route::put('pickuprequests/assign', 'PickupRequestController@assign');
        // Get Pickup request Count
        Route::get('pickuprequests/count', 'PickupRequestController@count');

        /**
         * Wallet
         */
        Route::resource('wallets', 'WalletController', ['only' => ['update', 'destroy']]);

    });

});
