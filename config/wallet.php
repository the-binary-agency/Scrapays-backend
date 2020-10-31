<?php

return [

    'walletSecret'    => config('app.debug') ? env('TEST_WALLET_SECRET') : env('WALLET_SECRET'),

    'walletToken'     => config('app.debug') ? env('TEST_WALLET_TOKEN') : env('WALLET_TOKEN'),

    'walletPublicKey' => config('app.debug') ? env('TEST_WALLET_PUBLIC_KEY') : env('WALLET_PUBLIC_KEY')

];
