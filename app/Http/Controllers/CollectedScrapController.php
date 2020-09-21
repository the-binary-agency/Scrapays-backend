<?php

namespace App\Http\Controllers;

use App\collectedScrap;
use App\Household;
use App\PickupRequest;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;

class CollectedScrapController extends Controller
{
    public function listCollectedScrap( Request $request )
    {
        $this->validate($request, [
            'mode' => 'required|string',
            'producerPhone' => 'required|string',
            'collectorID' => 'required|string',
        ]);

        if( !$this->validateRole($request->collectorID) )
        {
            return $this->unauthorisedResponse();
        }

        if(!$this->validateUUID($request->producerPhone) )
        {
            return $this->failedResponse();
        }
        
        $materialstosave = array();
        
        foreach ($request->materials as $mat) {
           $materialobject = (object) [
                'name' => $mat['name'],
                'weight' => $mat['weight'],
                'price' => $mat['price'],
                'cost' => $mat['cost'],
            ];
            array_push($materialstosave, $materialobject);
        }


        $scrap = new collectedScrap();
        $scrap->producerPhone = $request->producerPhone;
        $scrap->collectorID = $request->collectorID;
        $scrap->paymentMethod = $request->mode;
        $scrap->cost = $request->totalCost;
        $scrap->totalTonnage = $request->totalTonnage;
        $scrap->pickupID = $request->pickupID;
        $scrap->materials = json_encode($materialstosave);
        if(  $producer = User::find('+234'.substr($request->producerPhone, 1)))
        {
            if($producer->userable_type == 'App\Enterprise')
            {
                if($producer->userable->address == null)
                {
                    $scrap->address = $request->requestAddress;
                }else{
                    $scrap->address = $producer->userable->address;
                }
                $scrap->producerType = 'Enterprise';
            }else{
                if($producer->userable->requestAddress == null)
                {
                    $scrap->address = $request->requestAddress;
                }else{
                    $scrap->address = $producer->userable->requestAddress;
                }
                $scrap->producerType = 'Household';
            }
        }else if($producer = DB::table('users')->where('id', $request->producerPhone)->first())
        {
            $ent = DB::table('enterprises')->where('id', $producer->userable_id)->first();
            $scrap->address = $ent->address;
            $scrap->producerType = 'Enterprise';
        }else if($request->newUser)
        {
            $us = (object)$request->newUser;
            $scrap->address = $us->requestAddress;
            $scrap->producerType = 'Household';
        }

        if($request->pickupID)
        {
            $pickup = PickupRequest::find($request->pickupID);
            $pickup->status = 'Resolved';
        }

        $newHousehold = null;
        if($request->newUser)
        {
            $newHousehold = $this->registerNewHousehold($request->newUser);     
            if($newHousehold != 'success')
            {
                return response()->json(['error' => $newHousehold], 500);
            }
        }

        $this->calculateTonnage($request->producerPhone, $request->totalTonnage, $request->totalCost, $request->collectorID);

        if($request->mode == 'Wallet')
        {
            $res = $this->creditWallet($request);
            $decoded = json_decode($res);
            if($decoded->error->message)
            {
                return response()->json($decoded->error->message, Response::HTTP_NOT_FOUND);
            }else if($decoded->success->message)
            {
                $scrap->save();
                if($request->pickupID)
                {
                    $pickup->save();
                }
            }
        }else{
            $scrap->save();
            if($request->pickupID)
            {
                $pickup->save();
            }
        }

        return $this->successResponse();
    }

    public function calculateTonnage($phone, $totalTonnage, $totalCost, $collectorID)
    {
        if ($producer = User::find('+234'.substr($phone, 1))){
           if($tt = $producer->totaltonnage)
           {
                $formattedEarnings = floatval($producer->totalEarnings);
                $formattedCost = floatval($totalCost);
                $producer->totalTonnage = $tt += $totalTonnage;
                $producer->totalEarnings = $formattedEarnings += $formattedCost;
                $producer->save();
           }else{
                $producer->totalTonnage = $totalTonnage;
                $producer->totalEarnings = $totalCost;
                $producer->save();
           }
        }else if($pro = DB::table('users')->where('id', $phone)->first())
        {
            $producer = User::find($pro->phone);
          if($tt = $producer->totaltonnage)
           {
                $formattedEarnings = floatval($producer->totalEarnings);
                $formattedCost = floatval($totalCost);
                $producer->totalTonnage = $tt += $totalTonnage;
                $producer->totalEarnings = $formattedEarnings += $formattedCost;
                $producer->save();
           }else{
                $producer->totalTonnage = $totalTonnage;
                $producer->totalEarnings = $totalCost;
                $producer->save();
           }
        }

        if ($collector = User::find($collectorID)){
           if($tt = $collector->totaltonnage)
           {
                $formattedCost = floatval($totalCost);
                $collector->totalTonnage = $tt += $totalTonnage;
                $collector->save();
           }else{
                $collector->totalTonnage = $totalTonnage;
                $collector->save();
           }
        }
       
    }

    public function validateUUID($phone)
    {
       if($user = User::find($phone))
       {
           return true;
       }else if($user =  DB::table('users')->where('id', $phone)->get()){
           return true;
       }
       return false;
    }

    public function validateRole($id)
    {
        $user = User::find($id);
        return !!$user->userable_type == 'App\Collector';
    }

     public function successResponse()
    {
        return response()->json([
            'data' => 'Collected Scrap Listed Successfully.'
        ], Response::HTTP_OK);
    }

    public function unauthorisedResponse()
    {
        return response()->json([
            'error' => 'You are not authorised.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function failedResponse()
    {
        return response()->json([
            'error' => 'There is no user with the supplied ID'
        ], Response::HTTP_NOT_FOUND);
    }

    public function creditWallet(Request $request)
    {
        $phone = '';
        if($user = User::find('+234'.substr($request->producerPhone, 1))){
            $phone = $user->phone;
        }else if($user = DB::table('users')->where('id', $request->producerPhone)->get())
        {
            $phone = $user[0]->phone;
        };
        // $unformattedPhone = '0' . explode('+234', $phone)[1];
        $random = Str::random(15);
        $curl = curl_init();
        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                    'phone' => $phone,
                    'transID' => $random,
                    'narration' => "Scrap Pickup Request",
                    'amount' => $request->totalCost
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

        return $response;
    }

    public function getCollectorCollections($phone)
    {
        $collections = DB::table('collected_scraps')->where('collectorID', $phone)->latest()->get();
        return response()->json((object)[
            'collections' => $collections
        ], Response::HTTP_OK);
    }

    public function getCollections($phone)
    {
        $collections = DB::table('collected_scraps')->where('producerPhone', $phone)->latest()->get();
        if(sizeof($collections) > 0)
        {
            return response()->json((object)[
                'collections' => $collections
            ], Response::HTTP_OK);
        }else if($producer = User::find('+234'.substr($phone, 1)))
        {
            $collections = DB::table('collected_scraps')->where('producerPhone', $producer->id)->latest()->get();
            return response()->json((object)[
                'collections' => $collections
            ], Response::HTTP_OK);
        }else{
             return response()->json((object)[
                'collections' => []
            ], Response::HTTP_OK);
        }
    }

    public function registerNewHousehold($new_user)
    {
        $request = (object)$new_user;
        error_log($request->firstName);
        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = '+234'.substr($request->phone, 1);
        $user->email = null;
        $user->pin = Crypt::encryptString('1234');
        $user->password = '123456';
        $user->inviteCode = null;

        $household = new Household;
        $household->requestAddress = $request->requestAddress;

        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                'phone' => '+234'.substr($request->phone, 1),
                'pin' => '1234',
                'fullname' =>$request->firstName." ".$request->lastName,
            ],
        ];
        $fields_string = http_build_query($fields);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://apis.dcptap.website/w/public/v1/wallet/create",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            "token: Bearer ".$token,
            "publicKey: ".$publicKey
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode($response);
        $res = '';
        if($decoded)
        {
            if($decoded->error->message)
            {
                $res = $decoded->error->message;
            }else{
                if($decoded->success->message)
                {
                    $user->save();
                    $household->save();
                    $household->user()->save($user);
                    $res = 'success';
                }
            }
        }else
        {
            $res = 'A server error has occured, please try again.';
        }
        return $res;
    }

    public function getHistory(Request $request)
    {
        $query = (object)$request->query();
        $collection = collectedScrap::all();
        if(property_exists($query, 'users'))
        {
            $collection = DB::table('collected_scraps')->where('producerType', $query->users)->get();
        }

        $hist = $this->calculateHistory($collection);
       
       return response()->json((object)['history' => $hist], Response::HTTP_OK);

    }

    public function getCollectionsHistoryWithQuery($phone, Request $request){
        $formatted_phone = '0' . explode('+234', $phone)[1];
        $query = (object)$request->query();
        // $now = '';
        // $from = '';
        if(property_exists($query, 'from'))
        {
            $f = json_decode($query->from);
            $from = $f->year.'-'.$f->month.'-'.$f->day;
            $n = json_decode($query->to);
            $now = $n->year.'-'.$n->month.'-'.$n->day;
            $collection = DB::table('collected_scraps')
                            ->where('producerPhone', $formatted_phone)
                            ->whereBetween('created_at', [$from, $now])
                            ->get();

            $hist = $this->calculateHistory($collection);

            return response()->json((object)[
                'history' => $hist
            ], Response::HTTP_OK);
        }
    }

    public function getCollectorCollectionsHistory($phone)
    {
        $collections = DB::table('collected_scraps')->where('collectorID', $phone)->get();

        $hist = $this->calculateHistory($collections);

        return response()->json((object)[
            'history' => $hist
        ], Response::HTTP_OK);
    }

    public function getCollectorCollectionsHistoryWithQuery($phone, Request $request){
        $query = (object)$request->query();
        // $now = '';
        // $from = '';
        if(property_exists($query, 'from'))
        {
            $f = json_decode($query->from);
            $from = $f->year.'-'.$f->month.'-'.$f->day;
            $n = json_decode($query->to);
            $now = $n->year.'-'.$n->month.'-'.$n->day;
            $collection = DB::table('collected_scraps')
                            ->where('collectorID', $phone)
                            ->whereBetween('created_at', [$from, $now])
                            ->get();

            $hist = $this->calculateHistory($collection);

            return response()->json((object)[
                'history' => $hist
            ], Response::HTTP_OK);
        }
    }

    public function getProducerCollectionsHistory($phone)
    {
        $formatted_phone = '0' . explode('+234', $phone)[1];
        $collections = DB::table('collected_scraps')->where('producerPhone', $formatted_phone)->get();

        $hist = $this->calculateHistory($collections);

        return response()->json((object)[
            'history' => $hist
        ], Response::HTTP_OK);
    }

    public function calculateHistory($collection)
    {
        $holder = (object) [];
        $obj2 = array();

        foreach ($collection as $coll) {
            array_filter(json_decode($coll->materials), function ($mat) use ($holder) {
                if (property_exists($holder, $mat->name)) {
                    $props = explode('-',$holder->{$mat->name});
                    if($mat->name == 'Composite')
                    {
                        $prevCost = $props[1];
                        $prevWeight = $props[0];
                        $cost = $prevCost + $mat->cost;
                        $weight = $prevWeight + $mat->weight;
                        $holder->{$mat->name} = $weight.'-'.$cost;
                    }else{
                        $prevCost = $props[1];
                        $prevWeight = $props[0];
                        $cost = $prevCost + ($mat->weight * $mat->price);
                        $weight = $prevWeight + $mat->weight;
                        $holder->{$mat->name} = $weight.'-'.$cost;
                    }
                } else {
                   if($mat->name == 'Composite')
                   {
                        $holder->{$mat->name} = $mat->weight.'-'.$mat->cost;
                   }else{
                        $holder->{$mat->name} = $mat->weight.'-'.$mat->weight * $mat->price;
                   }
                }
            });
        }

        foreach($holder as $prop => $value) {
            $props = explode('-',$holder->{$prop});
            array_push($obj2, (object)[
                'name' => $prop,
                'weight' => $props[0] ,
                'cost' => $props[1] ,
            ]);
        }
        $totaltonnage = 0;
        $totalcost = 0;
        foreach($obj2 as $mat) {
            $totaltonnage += $mat->weight;
            $totalcost += $mat->cost;
        }

        $hist = (object)[
            'materials' => $obj2,
            'totaltonnage' => $totaltonnage,
            'totalcost' => $totalcost,
        ];
       
       return $hist;
    }
}
