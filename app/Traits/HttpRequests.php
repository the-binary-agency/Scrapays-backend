<?php

namespace App\Traits;

use GuzzleHttp\Client as Http;
use GuzzleHttp\Exception\RequestException;

/**
 * Make Http Requests
 */
trait HttpRequests
{
    public function createWallet($request)
    {
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone'    => '+234' . substr($request->phone, 1),
                'pin'      => '1234',
                'fullname' => $request->first_name . " " . $request->last_name
            ]
        ];

        $fields_string = http_build_query($fields);
        $curl          = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://apis.dcptap.website/w/public/v1/wallet/create",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "POST",
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                "token: Bearer " . $token,
                "publicKey: " . $publicKey
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode($response);
        return $decoded;
    }

    public function creditWallet($fields)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/credit',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function getWalletBalance($phone, $pin)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone' => $phone,
                'pin'   => $pin
            ]
        ];

        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/balance',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);

        $decoded = json_decode($response);

        $res = (object) [
            'balance' => '0.00',
            'error'   => ''
        ];
        if ($decoded) {
            if ($decoded->error->message) {
                $res->error = $decoded->error->message;
            } else {
                error_log('bal' . $decoded->content->balance);
                if ($decoded->success->message) {
                    $res->balance = $decoded->content->balance;
                }
            }
        } else {
            $res->error = 'Something went wrong, please try again.';
        }

        curl_close($curl);
        return $res;
    }

    public function withdrawFromWallet($request)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone'         => '+234' . substr($request->phone, 1),
                'pin'           => $request->pin,
                'narration'     => 'withdrawal from wallet to bank account',
                'transID'       => Str::random(15),
                'amount'        => $request->amount,
                'bankName'      => $request->bank_name,
                'bankCode'      => $request->bank_code,
                'accountNumber' => $request->account_number,
                'accountName'   => $request->account_name
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/withdraw',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function transferTobank($request)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone'       => '+234' . substr($request->phone, 1),
                'pin'         => $request->pin,
                'fullname'    => $request->full_name,
                'beneficiary' => $request->beneficiary,
                'amount'      => $request->amount
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/transfer',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function buyAirtime($request)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone'       => '+234' . substr($request->phone, 1),
                'pin'         => $request->pin,
                'fullname'    => $request->full_name,
                'beneficiary' => $request->beneficiary,
                'amount'      => $request->amount
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/transfer',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    public function getWalletHistory($user)
    {
        $curl = curl_init();

        $publicKey = config('wallet.walletAdminPublicKey');
        $token     = config('wallet.walletAdminToken');

        $fields = [
            'data' => [
                'phone' => '+234' . substr($user->phone, 1)
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/admin/get-walllet-history',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $decoded = json_decode($response);

        return $decoded;
    }

    public function setWalletPin($user)
    {
        $curl      = curl_init();
        $publicKey = config('wallet.walletAdminPublicKey');
        $token     = config('wallet.walletAdminToken');

        $fields = [
            'data' => [
                'phone' => $user->phone,
                'pin'   => $user->new_pin
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/admin/set-wallet-pin',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $decoded = json_decode($response);
        return $decoded;
    }

    public function getWalletWithdrawalHistory($phone, $pin)
    {
        $curl = curl_init();

        $publicKey = config('wallet.walletPublicKey');
        $token     = config('wallet.walletToken');

        $fields = [
            'data' => [
                'phone' => $phone,
                'pin'   => $pin
            ]
        ];
        error_log(json_encode($fields));
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
            CURLOPT_URL            => 'https://apis.dcptap.website/w/public/v1/wallet/history/credit',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => $fields_string,
            CURLOPT_HTTPHEADER     => array(
                'token: Bearer ' . $token,
                'publicKey: ' . $publicKey
            )
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        $decoded = json_decode($response);

        return $decoded;
    }
}
