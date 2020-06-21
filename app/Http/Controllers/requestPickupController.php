<?php

namespace App\Http\Controllers;

use App\Mail\requestPickupMail;
use App\Mail\responsePickupMail;
use App\PickupRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\Response;

class requestPickupController extends Controller
{
    public $user;
    public $materials;
    public $schedule;
    public $time;
    public $pickup;

    public function initiateRequest(Request $request)
    {
        $this->user = User::find($request->id);
        $this->materials = $request->materials;
        $this->schedule = $request->schedule;
        $this->time = $request->scheduleTime;
        return $this->verifyUser($request->id);
    }

    public function verifyUser($id)
    {
        if( $this->user->role != 'Producer' ){
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
        return $this->checkPendingRequest($id);
    }

    public function checkPendingRequest($id)
    {
        if ( $pickup = PickupRequest::where('userID', $id)->first() ){
            if( $pickup->status = 'Pending' ){
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
        $pickup->address = $this->user->address;
        $pickup->schedule = $this->schedule + ' ' + $this->time;
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
        Mail::to('pickup@scrapays.com')->send(new requestPickupMail($this->user, $pickup));
        Mail::to($this->user->email)->send(new responsePickupMail($this->user, $pickup));

        return response()->json(['data' => 'A pickup request has been issued successfully. Please check your email for further details'], Response::HTTP_OK);
    }

}
