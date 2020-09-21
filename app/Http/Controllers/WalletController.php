<?php

namespace App\Http\Controllers;

use App\collectedScrap;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use AfricasTalking\SDK\AfricasTalking;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Exception;

class WalletController extends Controller
{

//  "content": {
//         "secret": "SAvibrifAfojEBy5oXBS9Dk7",
//         "userID": "scrapaysLive"
//     }
//  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyZXF1ZXN0IjoiKiIsInVzZXJJRCI6InNjcmFwYXlzTGl2ZSIsInRyYW5zRGF0ZSI6MTU5NjcyOTcwMSwibWF4cmVxdWVzdCI6IjAiLCJwdWJsaWNLZXkiOiI4eVZhemdObXdRcDJkNEs2ZnU2VWRpZksifQ.Mf2XQHpW-WoKg22Yn8YhY-Nm6E4zdWUOuphbsbI79sw",
//         "publicKey": "8yVazgNmwQp2d4K6fu6UdifK"

     public function unauthenticated()
    {
        return response()->json(['error' => 'You are not authorised.'], Response::HTTP_UNAUTHORIZED);
    }

    public function getWalletBalance($phone){

        $user = User::find($phone);
        $pin = Crypt::decryptString($user->pin);

        $balance = $this->getBalance($phone, $pin);
        
         return response()->json([
                'balance' => $balance
            ], Response::HTTP_OK);

    }

    public function getBalance($phone, $pin)
    {
        $curl = curl_init();
        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                    'phone' => $phone,
                    'pin' => $pin,
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apis.dcptap.website/w/public/v1/wallet/balance',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            'token: Bearer '.$token,
            'publicKey: '.$publicKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response->content->balance;
    }

    public function getWalletHistory($phone){
        $user = User::find($phone);
        if(!$user || $user->userable_type != 'App\Enterprise' || $user->userable_type != 'App\Household')
        {
            $this->unauthenticated();
        }

        $hist = DB::table('transactions')->where('payable_id', $phone)->get();
        $history = array();
        foreach($hist as $h){
            $hi = (object)[
                'amount' => $h->amount/100.00,
                'type' => $h->type,
                'time' => $h->created_at,
                'uuid' => $h->uuid
            ];
            array_push($history, $hi);
        }
        return response()->json([
                'history' => $history
            ], Response::HTTP_OK);
    }
    
    public function withdrawFromWallet(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|string',
            'amount' => 'required',
            'accountNumber' => 'required',
            'bankCode' => 'required',
        ]);

         $user = User::find($request->phone);

         $curl = curl_init();
         $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                    'phone' => '+234'.substr($request->phone, 1),
                    'pin' => $request->pin,
                    'narration' => 'withdrawal from wallet to bank account',
                    'transID' => Str::random(15),
                    'amount' => $request->amount,
                    'bankName' => $request->bankName,
                    'bankCode' => $request->bankCode,
                    'accountNumber' => $request->accountNumber,
                    'accountName' => $request->accountName
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apis.dcptap.website/w/public/v1/wallet/withdraw',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            'token: Bearer '.$token,
            'publicKey: '.$publicKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
        $balance = $user->balanceFloat;
        if($balance > 500.00){

                if($balance >= sprintf('%.2f', $request->amount)){

            }else{
                return response()->json([
                    'Error' =>'Insufficient Balance'
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

        }else{
            return response()->json([
                'Error' =>'You can only withdraw a minimum of ₦500'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }
    }

    public function transfer(Request $request)
    {
        $this->validate($request, [
            'phone' => 'required|string',
            'amount' => 'required',
            'accountNo' => 'required',
            'bankCode' => 'required',
        ]);

         $user = User::find($request->phone);

         $curl = curl_init();
         $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                    'phone' => '+234'.substr($request->phone, 1),
                    'pin' => $request->pin,
                    'fullname' => $request->fullname,
                    'beneficiary' => $request->beneficiary,
                    'amount' => $request->amount,
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apis.dcptap.website/w/public/v1/wallet/transfer',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            'token: Bearer '.$token,
            'publicKey: '.$publicKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return response()->json($response, Response::HTTP_OK);
    }

    public function creditWallet(Request $request)
    {
        $curl = curl_init();
        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                    'phone' => '+234'.substr($request->phone, 1),
                    'transID' => Str::random(15),
                    'narration' =>$request->narration,
                    'amount' => $request->amount
            ]
        ];
        $fields_string = http_build_query($fields);
        curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://apis.dcptap.website/w/public/v1/wallet/credit',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
       CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            'token: Bearer '.$token,
            'publicKey: '.$publicKey
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return response()->json($response, Response::HTTP_OK);
    }

    
        public function buyAirtime(Request $request)
    {

        $this->validate($request, [
            'phone' => 'required|string',
            'amount' => 'required',
        ]);

        $user = User::find($request->phone);
       if($user)
       {

            if($this->isAbleToTransact($user, $request->amount) == true)
        {
            
             // Set your app credentials
            $username = env('AFT_USER_NAME');
            $apiKey = env('AFT_API_KEY');
            // Initialize the SDK
            $AT  = new AfricasTalking($username, $apiKey);

            // Get the airtime service
            $airtime  = $AT->airtime();

            // Set the phone number, currency code and amount in the format below
            $recipients = [[
                'phoneNumber'  => $request->phone,
                'currencyCode' => 'NGN',
                'amount' => $request->amount
            ]];

            try {
                // That's it, hit send and we'll take care of the rest
                $results = $airtime->send([
                    'recipients' => $recipients
                ]);

                if($results['data']->errorMessage == 'None')
                {

                    $am = floatval( sprintf('%.2f', $request->amount));
                    $user->withdrawFloat($am);
                     return response()->json([
                    'Success' => 'Airtime purchase made successfully'
                    ], Response::HTTP_OK);

                }else{

                    return response()->json([
                    'Error' => $results['data']->responses[0]->errorMessage
                    ], Response::HTTP_UNPROCESSABLE_ENTITY);

                }
            } catch(Exception $e) {

                return response()->json([
                'Error' => $e->getMessage()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);

            }

        }else
        {

            return response()->json([
            'Error' => 'You have insufficient funds or have less than  ₦500'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        }

       }
       
    }

    public function isAbleToTransact($user, $amount)
    {
        $balance = $user->balanceFloat;
        $amountInString =  sprintf('%.2f', $amount);

        if($balance > 500.00){

                if(floatval($balance) >= floatval($amountInString)){

                return true;

            }else{

                return false;

            }

        }else{
           
           return false;

        }
    }
}
