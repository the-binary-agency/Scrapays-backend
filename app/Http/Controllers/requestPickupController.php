<?php

namespace App\Http\Controllers;

use App\Mail\CancelPickupAdminMail;
use App\Mail\CancelPickupUserMail;
use App\Mail\requestPickupMail;
use App\Mail\responsePickupMail;
use App\PickupRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Traits\Notifications;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class requestPickupController extends Controller
{
    use Notifications;

    public function initiateRequest(Request $request)
    {
        $user = User::find($request->id);
         if($user)
        {
            $pickupRequest = (object)[
                'materials' => $request->materials,
                'scheduleDate' => $request->scheduleDate,
                'time' => $request->scheduleTime,
                'address' => $request->address,
                'comment' => $request->comment,
                'description' => $request->description,
            ];
            return $this->verifyUser($user, $pickupRequest);
        }else{
            return response()->json(['error' => 'No user exists with the supplied phone number'], 404);
        }
    }

    public function initiateUssdRequest(Request $request)
    {
        $user = User::find($request->phone);
        if($user)
        {
            $pickupRequest = (object)[
                'materials' => $request->materials,
                'time' => $request->scheduleTime,
                'scheduleDate' => $request->scheduleDate,
                'address' => $request->address,
                'comment' => null,
                'description' => null,
            ];
        // error_log(json_encode($pickupRequest));
            return $this->verifyUSSDUser($user, $pickupRequest);
        }else{
            return response()->json(['error' => 'No user exists with the supplied phone number'], 404);
        }
    }

    public function verifyUser($user, $pickupRequest)
    {
        if( !$this->isEnterprise($user) ){
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->checkPendingRequest($user, $pickupRequest);
    }

    public function verifyUSSDUser($user, $pickupRequest)
    {
        if( !$this->isHousehold($user) ){
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->checkPendingRequest($user, $pickupRequest);
    }

    public function isHousehold($user){
        if ($user->userable_type != 'App\Household'){
            return false;
        }
        return true;
    }

    public function isEnterprise($user){
        if ($user->userable_type != 'App\Enterprise'){
            return false;
        }
        return true;
    }

    public function checkPendingRequest($user, $pickupRequest)
    {
        if ( $pickup = PickupRequest::where('userID', $user->phone)->first() ){
            if( $pickup->status == 'Pending' ){
                return response()->json(['error' => 'Sorry, you still have a pending request. You can not make new pickup requests until your previous request has been resolved. '], Response::HTTP_CONFLICT);
            }
        }  
        return $this->savePickup($user, $pickupRequest);
    }

    public function savePickup($user, $pickupRequest)
    {   
        $pickup = new PickupRequest;
        $pickup->userID = $user->phone;
        $pickup->producerName = $user->firstName ." ". $user->lastName;
        $pickup->materials = json_encode($pickupRequest->materials);
        $pickup->address = $pickupRequest->address ? $pickupRequest->address : $user->userable->requestAddress;
        $pickup->comment = $pickupRequest->comment;
        $pickup->description = $pickupRequest->description;
        $sch = (object) [
            'scheduleDate' => $pickupRequest->scheduleDate,
            'scheduleTime' => $pickupRequest->time,
        ];
        $pickup->schedule = json_encode($sch);
        $pickup->status = 'Pending';

        $pickup->save();
        return $this->requestPickup($user);
    }

    public function requestPickup($user){
        $pickup = PickupRequest::where('userID', $user->phone)->first();
        return $this->sendMails($pickup, $user);
    }

    public function sendMails($pickup, $user)
    {
        $this->createNotification($user->phone, "You made a pickup request and details have been sent to your email.");
        
        Mail::to('pickup@scrapays.com')->send(new requestPickupMail($user, $pickup));
        if($user->email)
        {
            Mail::to($user->email)->send(new responsePickupMail($user, $pickup));
            return response()->json(['data' => 'A pickup request has been issued successfully. Please check your email for further details'], Response::HTTP_OK);
        }

        return response()->json(['data' => 'A pickup request has been issued successfully.'], Response::HTTP_OK);
    }

    public function cancelPickup(Request $request)
    {   
        $user = User::find($request->phone);
        $pickup = PickupRequest::where('userID', $request->phone)->first();

        if($pickup)
        {
                if($pickup->status == 'Pending')
            {
                $pickup->status = 'Cancelled';
                $pickup->save();

                $this->createNotification($request->phone, "Your pending pickup request has been cancelled.");
        
                Mail::to('pickup@scrapays.com')->send(new CancelPickupAdminMail($user, $pickup));
                Mail::to($user->email)->send(new CancelPickupUserMail($user, $pickup));

                return response()->json(['success' => 'Your pending Pickup request has been cancelled.'], Response::HTTP_OK);
            }else{
                return response()->json(['error' => 'You have no Pending Pickup Requests.'], Response::HTTP_NOT_FOUND);
            }
        }
        return response()->json(['error' => 'You have no Pending Pickup Requests.'], Response::HTTP_NOT_FOUND);
    }

    public function getAssignedPickups($collectorPhone)
    {
        if ($user = User::find($collectorPhone)){
            if($user->userable_type == 'App\Collector' || $user->userable_type == 'App\Admin')
            {            
                $pickups = DB::table('pickup_requests')->where('assignedCollector', $collectorPhone)->get();
                return response()->json(['pickups' => $pickups], Response::HTTP_OK);
            }
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }else{ 
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getPickupRequestCounts($phone)
    {
        if ($user = User::find($phone)){
            if($user->userable_type == 'App\Admin')
            {            
                $pickupCount = DB::table('pickup_requests')->where('status', 'Pending')->count();
                return response()->json(['pickupCount' => $pickupCount], Response::HTTP_OK);
            }
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }else{ 
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getAllPickupRequests($phone)
    {
        if ($user = User::find($phone)){
            if($user->userable_type == 'App\Admin')
            {            
                $pickups = PickupRequest::orderBy('created_at', 'desc')->get();
                return response()->json(['pickups' => $pickups], Response::HTTP_OK);
            }
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }else{ 
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getCollectorWithLog(Request $request)
    {
        if ($user = User::find($request->adminPhone)){
            if($user->userable_type == 'App\Admin')
            {            
                $collector = '';
                $pickups = '';
                if($c = DB::table('users')->where('id', $request->collectorID)->first())
                {
                    $collector = $c;
                }else{
                    $phone = '+234'.substr($request->collectorID, 1);
                    $collector = User::find($phone);
                }
                $pickups = DB::table('pickup_requests')->where('assignedCollector', $collector->phone)->get();

                $assignedPickups = (object)[
                    'collector' => $collector,
                    'pickups' => $pickups
                ];
                return response()->json($assignedPickups, Response::HTTP_OK);
            }
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }else{ 
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function assignToCollector(Request $request)
    {
        if ($user = User::find($request->adminPhone)){
            if($user->userable_type == 'App\Admin')
            {            
                $collector = DB::table('users')->where('id', $request->collectorID)->first();
                $pickup = PickupRequest::find($request->pickup['id']);
                $pickup->assignedCollector = $collector->phone;
                $pickup->save();

                return response()->json('Request has been assigned to the chosen collector.', Response::HTTP_OK);
            }
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }else{ 
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

}
