<?php

namespace App\Http\Controllers;

use App\CollectedScrap;
use App\Http\Controllers\ApiController;
use App\Notification;
use App\PickupRequest;
use App\Traits\Notifications;
use App\User;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    /**
     * Display a listing of the users.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = User::all();
        return $this->showAll($users);
    }

    /**
     * Show a specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        return $this->showOne($user);
    }

    /**
     * Show a specified user.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserWithPhone($phone)
    {
        $user = User::where('phone', $phone);
        return $this->showOne($user);
    }

    /**
     * Show the count of all users.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserCount()
    {
        $producers  = User::where('userable_type', 'Enterprise')->count() + User::where('userable_type', 'Household')->count();
        $hosts      = User::where('userable_type', 'Host')->count();
        $collectors = User::where('userable_type', 'Collector')->count();

        $count = [
            'producers'  => $producers,
            'hosts'      => $hosts,
            'collectors' => $collectors
        ];

        return $this->successResponse($count, 200, true);
    }

    /**
     * Show a specified user's name.
     *
     * @return \Illuminate\Http\Response
     */
    public function getUserName(Request $request, $phone)
    {
        $collector = $request->user;

        if ($collector->userable_type != 'Collector' || !$collector->userable->approved_as_collector) {
            return $this->errorResponse('Only approved Collectors can access this route.', 401);
        }

        $producer_phone = '+234' . substr($phone, 1);

        if ($user = User::where('phone', $producer_phone)->first()) {
            return $this->getNameResponse($user);
        } else if ($user = User::find($phone)) {
            return $this->getNameResponse($user);
        } else {
            return $this->errorResponse("Not Found.", 404);
        }
    }

    /**
     * Process the user's name.
     *
     * @return \Illuminate\Http\Response
     */
    private function getNameResponse(User $user)
    {
        $pickup = PickupRequest::where('producer_id', $user->id)->first();
        if ($user->userable_type == 'Enterprise' || $user->userable_type == 'Household') {
            $response = (object) [
                'Name'        => $user->first_name . ' ' . $user->last_name,
                'producer_id' => $user->id,
                'pickup_id'   => ''
            ];

            if ($pickup) {
                $response->pickup_id = $pickup->id;

                return $this->successResponse($response, 200, true);
            } else {
                return $this->successResponse($response, 200, true);
            }
        } else {
            return $this->errorResponse("Not Found.", 404);
        }
    }

    /**
     * Process the user's notifications.
     *
     * @return \Illuminate\Http\Response
     */
    public function getNotifications(User $user)
    {
        $notifications = $user->notifications;
        return $this->showAll($notifications);
    }

    public function getSingleScrapHistory(Request $request, User $user)
    {
        $query = (object) $request->query();

        $collection = collectedScrap::all();
        if ($user->userable_type == 'Enterprise' || $user->userable_type == 'Household') {
            $collection = $collection->where('producer_id', $user->id);
        }
        if ($user->userable_type == 'Collector') {
            $collection = $collection->where('collector_id', $user->id);
        }
        if (property_exists($query, 'from')) {
            $f          = json_decode($query->from);
            $from       = $f->year . '-' . $f->month . '-' . $f->day;
            $n          = json_decode($query->to);
            $now        = $n->year . '-' . $n->month . '-' . $n->day;
            $collection = $collection->whereBetween('created_at', [$from, $now]);
        }

        $history = $this->calculateHistory($collection);

        return $this->successResponse($history, 200, true);
    }

    public function calculateHistory($collection)
    {
        $holder = (object) [];
        $obj2   = array();

        foreach ($collection as $coll) {
            array_filter(json_decode($coll->materials), function ($mat) use ($holder) {
                if (property_exists($holder, $mat->name)) {
                    $props = explode('-', $holder->{$mat->name});
                    if ($mat->name == 'Composite') {
                        $prevCost             = $props[1];
                        $prevWeight           = $props[0];
                        $cost                 = $prevCost + $mat->cost;
                        $weight               = $prevWeight + $mat->weight;
                        $holder->{$mat->name} = $weight . '-' . $cost;
                    } else {
                        $prevCost             = $props[1];
                        $prevWeight           = $props[0];
                        $cost                 = $prevCost + ($mat->weight * $mat->price);
                        $weight               = $prevWeight + $mat->weight;
                        $holder->{$mat->name} = $weight . '-' . $cost;
                    }
                } else {
                    if ($mat->name == 'Composite') {
                        $holder->{$mat->name} = $mat->weight . '-' . $mat->cost;
                    } else {
                        $holder->{$mat->name} = $mat->weight . '-' . $mat->weight * $mat->price;
                    }
                }
            });
        }

        foreach ($holder as $prop => $value) {
            $props = explode('-', $holder->{$prop});
            array_push($obj2, (object) [
                'name'   => $prop,
                'weight' => $props[0],
                'cost'   => $props[1]
            ]);
        }
        $total_tonnage = 0;
        $totalcost     = 0;
        foreach ($obj2 as $mat) {
            $total_tonnage += $mat->weight;
            $totalcost += $mat->cost;
        }

        $hist = (object) [
            'materials'     => $obj2,
            'total_tonnage' => $total_tonnage,
            'totalcost'     => $totalcost
        ];

        return $hist;
    }

    public function refactor()
    {
        $user = User::all();

        foreach ($user as $us) {
            $newUser                = user::find($us->id);
            $newUser->userable_type = explode('App\\', $newUser->userable_type)[1];
            $newUser->save();
        }

        error_log('--------------------------- Finished Users --------------------------------------');

        $collectedscrap = CollectedScrap::all();

        foreach ($collectedscrap as $scrap) {
            $producer  = User::where('phone', '+234' . substr($scrap->producer_id, 1))->first();
            $collector = User::where('phone', $scrap->collector_id)->first();
            $materials = json_decode($scrap->materials);

            foreach ($materials as $material) {
                error_log('before ----->  ' . $material->weight);
                $material->weight = round($material->weight, 2);
                error_log('after ----->  ' . $material->weight);
            }

            $newScrap = CollectedScrap::find($scrap->id);

            if ($producer) {
                $newScrap->producer_id = $producer->id;
            }
            if ($collector) {
                $newScrap->collector_id = $collector->id;
            }

            $newScrap->materials = json_encode($materials);
            $newScrap->save();
        }

        error_log('--------------------------- Finished Collected Scrap --------------------------------------');

        $notifications = Notification::all();

        foreach ($notifications as $noty) {
            $user    = User::where('phone', $noty->user_id)->first();
            $newNoty = Notification::find($noty->id);

            if ($user) {
                $newNoty->user_id = $user->id;
            }

            $newNoty->save();
        }

        error_log('--------------------------- Finished Notifications --------------------------------------');

        $pickupRequests = PickupRequest::all();

        foreach ($pickupRequests as $pickup) {
            $producer  = User::where('phone', $pickup->producer_id)->first();
            $collector = '';
            if ($pickup->assigned_collector) {
                $collector = User::where('phone', $pickup->assigned_collector)->first();
            }
            $schedules = json_decode($pickup->schedule);

            foreach ($schedules as $key => $schedule) {
                if ($key == 'scheduleDate') {
                    error_log('before date ----->  ' . json_encode($schedules));
                    $schedules->schedule_date = $schedules->scheduleDate;
                    unset($schedules->scheduleDate);
                    error_log('after date ----->  ' . json_encode($schedules));
                } else if ($key == 'scheduleTime') {
                    error_log('before time ----->  ' . json_encode($schedules));
                    $schedules->schedule_time = $schedules->scheduleTime;
                    unset($schedules->scheduleTime);
                    error_log('after time ----->  ' . json_encode($schedules));
                }
            }

            $newPickup = PickupRequest::find($pickup->id);
            if ($producer) {
                $newPickup->producer_id = $producer->id;
            }
            if ($collector) {
                $newPickup->assigned_collector = $collector->id;
            }
            $newPickup->schedule = json_encode($schedules);
            $newPickup->save();
        }

        error_log('--------------------------- Done --------------------------------------');

        return $this->successResponse('Done', 200, true);
    }
}
