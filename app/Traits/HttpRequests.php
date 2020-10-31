<?php

namespace App\Traits;

use GuzzleHttp\Client as Http;

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

        // $http = new Http();

        // $response = $http->request('POST', 'a.com', [
        //     'headers' => [
        //         "token"      => "Bearer " . $token,
        //         "publicKey " => $publicKey
        //     ],
        //     'body'    => $fields
        // ]);

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
            'balance' => '',
            'error'   => ''
        ];
        if ($decoded) {
            if ($decoded->error->message) {
                $res->error = $decoded->error->message;
            } else {
                if ($decoded->success->message) {
                    $res->balance = $decoded->content->balance;
                }
            }
        } else {
            $res->error = 'Something went wrong, please try again.';
        }

        curl_close($curl);
        return $res->balance;
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
}
