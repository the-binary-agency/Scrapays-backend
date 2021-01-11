<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Mail\CancelPickupAdminMail;
use App\Mail\CancelPickupUserMail;
use App\Mail\requestPickupMail;
use App\Mail\responsePickupMail;
use App\PickupRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PickupRequestController extends ApiController
{
    /**
     * Display a listing of the pickup.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $pickupRequest = PickupRequest::with('producer:id,phone')->get();
        return $this->showAll($pickupRequest);
    }

    /**
     * Store a newly created pickup in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'producer_id'   => 'required|string',
            'materials'     => 'required',
            'schedule_date' => 'required',
            'schedule_time' => 'required',
            'address'       => 'required',
            'description'   => 'required'
        ]);

        $user          = User::findOrFail($request->producer_id);
        $pickupRequest = (object) [
            'materials'     => $request->materials,
            'schedule_date' => $request->schedule_date,
            'schedule_time' => $request->schedule_time,
            'address'       => $request->address,
            'comment'       => $request->comment,
            'description'   => $request->description
        ];

        return $this->verifyUser($user, $pickupRequest);
    }

    /**
     * Store a newly created pickup in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeUssdPickup(Request $request)
    {
        $this->validate($request, [
            'phone'         => 'required',
            'materials'     => 'required',
            'schedule_date' => 'required',
            'schedule_time' => 'required',
            'address'       => 'required',
            'description'   => 'required'
        ]);

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return $this->errorResponse("User with phone number {$request->phone} not found.", 404);
        }
        $pickupRequest = (object) [
            'materials'     => $request->materials,
            'schedule_date' => $request->schedule_date,
            'schedule_time' => $request->schedule_time,
            'address'       => $request->address,
            'comment'       => $request->comment,
            'description'   => $request->description
        ];

        return $this->verifyUSSDUser($user, $pickupRequest);
    }

    /**
     * Display the specified pickup.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(PickupRequest $pickupRequest)
    {
        return $this->showOne($pickupRequest);
    }

    /**
     * Remove the specified pickup from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(PickupRequest $pickuprequest)
    {
        $pickuprequest->delete();
        return $this->successResponse('Pickup requested deleted successfully.', 200, true);
    }

    public function verifyUser($user, $pickupRequest)
    {
        if (!$this->isEnterprise($user)) {
            return $this->successResponse('Not authorized to access this route.', 401, true);
        }
        return $this->checkPendingRequest($user, $pickupRequest);
    }

    public function verifyUSSDUser($user, $pickupRequest)
    {
        if (!$this->isHousehold($user)) {
            return $this->successResponse('Not authorized to access this route.', 401, true);
        }
        return $this->checkPendingRequest($user, $pickupRequest);
    }

    public function isHousehold($user)
    {
        if ($user->userable_type != 'Household') {
            return false;
        }
        return true;
    }

    public function isEnterprise($user)
    {
        if ($user->userable_type != 'Enterprise') {
            return false;
        }
        return true;
    }

    public function checkPendingRequest($user, $pickupRequest)
    {
        if ($pickup = PickupRequest::where('producer_id', $user->id)->first()) {
            if ($pickup->status == 'Pending') {
                return $this->errorResponse('Sorry, you still have a pending request. You can not make new pickup requests until your previous request has been resolved. ', 422);
            }
        }
        return $this->savePickup($user, $pickupRequest);
    }

    public function savePickup($user, $pickupRequest)
    {
        $pickup                = new PickupRequest();
        $pickup->producer_id   = $user->id;
        $pickup->producer_name = $user->first_name . " " . $user->last_name;
        $pickup->materials     = json_encode($pickupRequest->materials);
        $pickup->address       = $pickupRequest->address ? $pickupRequest->address : $user->userable->request_address;
        $pickup->comment       = $pickupRequest->comment;
        $pickup->description   = $pickupRequest->description;
        $sch                   = (object) [
            'schedule_date' => $pickupRequest->schedule_date,
            'schedule_time' => $pickupRequest->schedule_time
        ];
        $pickup->schedule = json_encode($sch);
        $pickup->status   = 'Pending';

        $pickup->save();
        return $this->requestPickup($user);
    }

    public function requestPickup($user)
    {
        $pickup = PickupRequest::where('producer_id', $user->id)->first();
        return $this->sendMails($pickup, $user);
    }

    public function sendMails($pickup, $user)
    {
        // $this->createNotification($user->id, "You made a pickup request and details have been sent to your email.");

        Mail::to('pickup@scrapays.com')->send(new requestPickupMail($user, $pickup));
        if ($user->email) {
            Mail::to($user->email)->send(new responsePickupMail($user, $pickup));
            return $this->successResponse('A pickup request has been issued successfully. Please check your email for further details', 201, true);
        }

        return $this->successResponse('A pickup request has been issued successfully.', 201, true);
    }

    public function cancel(User $enterprise)
    {
        $pickup = PickupRequest::where('producer_id', $enterprise->id)->first();

        if ($pickup) {
            if ($pickup->status == 'Pending') {
                $pickup->status = 'Cancelled';
                $pickup->save();

                // $this->createNotification($enterprise->id, "Your pending pickup request has been cancelled.");

                Mail::to('pickup@scrapays.com')->send(new CancelPickupAdminMail($enterprise, $pickup));
                Mail::to($enterprise->email)->send(new CancelPickupUserMail($enterprise, $pickup));

                return $this->successResponse('Your pending Pickup request has been cancelled.', 200, true);
            } else {
                return $this->errorResponse('You have no Pending Pickup Requests.', 404);
            }
        }
        return $this->errorResponse('You have no Pending Pickup Requests.', 404);
    }

    public function assign(Request $request)
    {
        $this->validate($request, [
            'collector_id' => 'required|string',
            'pickup_id'    => 'required|string'
        ]);

        $collector                  = User::findOrFail($request->collector_id);
        $pickup                     = PickupRequest::findOrFail($request->pickup_id);
        $pickup->assigned_collector = $collector->id;
        $pickup->save();

        return $this->successResponse('Request has been assigned to the chosen collector.', 200, true);
    }

    public function count()
    {
        $pickups = PickupRequest::all();
        $pickups = $this->filterData($pickups);

        $res = [
            'pickupcount' => $pickups->count()
        ];
        return $this->successResponse($res, 200, true);
    }
}
