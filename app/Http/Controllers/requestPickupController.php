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
use Symfony\Component\HttpFoundation\Response;

class requestPickupController extends Controller
{
    use Notifications;
    public $user;
    public $materials;
    public $scheduleDate;
    public $time;
    public $pickup;

    public function initiateRequest(Request $request)
    {
        $this->user = User::find($request->id);
        $this->materials = $request->materials;
        $this->scheduleDate = $request->scheduleDate;
        $this->time = $request->scheduleTime;
        return $this->verifyUser($request->id);
    }

    public function verifyUser($id)
    {
        if( !$this->isProducer() ){
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->checkPendingRequest($id);
    }

    public function isProducer(){
        if ($this->user->userable_type != 'App\Enterprise'){
            return false;
        }
        return true;
    }

    public function checkPendingRequest($id)
    {
        if ( $pickup = PickupRequest::where('userID', $id)->first() ){
            if( $pickup->status == 'Pending' ){
                return response()->json(['error' => 'Sorry, you still have a pending request. You can not make new pickup requests until your previous request has been resolved. '], Response::HTTP_CONFLICT);
            }
        }  
        return $this->savePickup($this->materials);
    }

    public function savePickup($materials)
    {
        
        
        $pickup = new PickupRequest;
        $pickup->userID = $this->user->phone;
        $pickup->materials = json_encode($materials);
        $pickup->address = $this->user->userable->address;
        $sch = (object) [
            'scheduleDate' => $this->scheduleDate,
            'scheduleTime' => $this->time,
        ];
        $pickup->schedule = json_encode($sch);
        $pickup->status = 'Pending';

        $pickup->save();
        return $this->requestPickup();
    }

    public function requestPickup(){
        $pickup = PickupRequest::where('userID', $this->user->phone)->first();
        return $this->sendMails($pickup);
    }

    public function sendMails($pickup)
    {
        $this->createNotification($this->user->phone, "You made a pickup request and details have been sent to your email.");
        
        Mail::to('pickup@scrapays.com')->send(new requestPickupMail($this->user, $pickup));
        Mail::to($this->user->email)->send(new responsePickupMail($this->user, $pickup));

        return response()->json(['data' => 'A pickup request has been issued successfully. Please check your email for further details'], Response::HTTP_OK);
    }

    public function cancelPickup(Request $request)
    {   
        $this->user = User::find($request->phone);
        $pickup = PickupRequest::where('userID', $request->phone)->first();

        if($pickup)
        {
                if($pickup->status == 'Pending')
            {
                $pickup->status = 'Cancelled';
                $pickup->save();

                $this->createNotification($request->phone, "Your pending pickup request has been cancelled.");
        
                Mail::to('pickup@scrapays.com')->send(new CancelPickupAdminMail($this->user, $pickup));
                Mail::to($this->user->email)->send(new CancelPickupUserMail($this->user, $pickup));

                return response()->json(['success' => 'Your pending Pickup request has been cancelled.'], Response::HTTP_OK);
            }else{
                return response()->json(['error' => 'You have no Pending Pickup Requests.'], Response::HTTP_NOT_FOUND);
            }
        }

        return response()->json(['error' => 'You have no Pending Pickup Requests.'], Response::HTTP_NOT_FOUND);
    }

}
