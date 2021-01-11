<?php

namespace App\Http\Controllers;

use App\CollectedScrap;
use App\Events\SearchUserEvent;
use App\Http\Controllers\ApiController;
use App\Material;
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
        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return $this->errorResponse("User with phone number {$phone} cannot be found.", 404);
        }
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
        $producer_phone = '+234' . substr($phone, 1);

        if ($user = User::where('phone', $producer_phone)->first()) {
            $response = (object) [
                'Name' => $user->first_name . ' ' . $user->last_name
            ];

            return $this->successResponse($response, 200, true);
        } else if ($user = User::find($phone)) {
            $response = (object) [
                'Name' => $user->first_name . ' ' . $user->last_name
            ];

            return $this->successResponse($response, 200, true);

        } else {
            return $this->errorResponse("Not Found.", 404);
        }
    }

    /**
     * Show a specified producer's name.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProducerName(Request $request, $phone)
    {
        $user = $request->user;

        if (($user->userable_type != 'Collector' || !$user->userable->approved_as_collector) && $user->userable_type != 'Admin') {
            return $this->errorResponse('Only Admins and approved Collectors can access this route.', 401);
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

        $history = $this->calculateHistory($collection, $request->user);

        return $this->successResponse($history, 200, true);
    }

    public function calculateHistory($collection, $authorized_user)
    {
        $holder           = (object) [];
        $sorted_materials = array();

        $original_materials = Material::all();

        foreach ($collection as $coll) {
            array_filter(json_decode($coll->materials), function ($mat) use ($holder, $original_materials) {

                $collector_commission = 0;
                $host_commission      = 0;

                foreach ($original_materials as $o_mat) {
                    if ($o_mat->name === $mat->name) {
                        $collector_commission = $o_mat['collector_commission'];
                        $host_commission      = $o_mat['host_commission'];
                    }
                }

                if (property_exists($holder, $mat->name)) {

                    $current_material = $holder->{$mat->name};

                    if ($mat->name == 'Composite') {
                        $prev_cost     = $current_material->cost;
                        $prev_weight   = $current_material->weight;
                        $prev_col_com  = $current_material->collector_commission;
                        $prev_host_com = $current_material->host_commission;

                        $cost     = $prev_cost + $mat->cost;
                        $weight   = $prev_weight + $mat->weight;
                        $col_com  = $prev_col_com + ($mat->weight * $collector_commission);
                        $host_com = $prev_host_com + ($mat->weight * $host_commission);

                        $holder->{$mat->name} = (object) [
                            'weight'               => $weight,
                            'cost'                 => $cost,
                            'collector_commission' => $col_com,
                            'host_commission'      => $host_com
                        ];

                    } else {
                        $prev_cost     = $current_material->cost;
                        $prev_weight   = $current_material->weight;
                        $prev_col_com  = $current_material->collector_commission;
                        $prev_host_com = $current_material->host_commission;

                        $cost     = $prev_cost + ($mat->weight * $mat->price);
                        $weight   = $prev_weight + $mat->weight;
                        $col_com  = $prev_col_com + ($mat->weight * $collector_commission);
                        $host_com = $prev_host_com + ($mat->weight * $host_commission);

                        $holder->{$mat->name} = (object) [
                            'weight'               => $weight,
                            'cost'                 => $cost,
                            'collector_commission' => $col_com,
                            'host_commission'      => $host_com
                        ];
                    }

                } else {
                    if ($mat->name == 'Composite') {
                        $holder->{$mat->name} = (object) [
                            'weight'               => $mat->weight,
                            'cost'                 => $mat->cost,
                            'collector_commission' => $collector_commission * $mat->weight,
                            'host_commission'      => $host_commission * $mat->weight
                        ];
                    } else {
                        $holder->{$mat->name} = (object) [
                            'weight'               => $mat->weight,
                            'cost'                 => $mat->weight * $mat->price,
                            'collector_commission' => $collector_commission * $mat->weight,
                            'host_commission'      => $host_commission * $mat->weight
                        ];
                    }
                }
            });
        }

        foreach ($holder as $material => $values) {
            $current_material = $holder->{$material};
            $new_mat          = (object) [
                'name'   => $material,
                'weight' => $current_material->weight,
                'cost'   => $current_material->cost
            ];

            if ($authorized_user->userable_type === 'Admin' || $authorized_user->userable_type === 'Collector') {
                $new_mat->{'collector_commission'} = $current_material->collector_commission;
            }
            if ($authorized_user->userable_type === 'Admin' || $authorized_user->userable_type === 'Host') {
                $new_mat->{'host_commission'} = $current_material->host_commission;
            }

            array_push($sorted_materials, $new_mat);
        }

        $total_tonnage  = 0;
        $total_cost     = 0;
        $total_col_com  = 0;
        $total_host_com = 0;

        foreach ($sorted_materials as $material) {
            $total_tonnage += $material->weight;
            $total_cost += $material->cost;
            $total_col_com += $material->collector_commission;
            $total_host_com += $material->host_commission;
        }

        $hist = (object) [
            'materials'     => $sorted_materials,
            'total_tonnage' => $total_tonnage,
            'total_cost'    => $total_cost
        ];

        if ($authorized_user->userable_type === 'Admin' || $authorized_user->userable_type === 'Collector') {
            $hist->{'total_collector_commission'} = $total_col_com;
        }
        if ($authorized_user->userable_type === 'Admin' || $authorized_user->userable_type === 'Host') {
            $hist->{'total_host_commission'} = $total_host_com;
        }

        return $hist;
    }

    public function refactor()
    {
        // $user = User::all();

        // foreach ($user as $us) {
        //     $newUser                = user::find($us->id);
        //     $newUser->userable_type = explode('App\\', $newUser->userable_type)[1];
        //     $newUser->save();
        // }

        // error_log('--------------------------- Finished Users --------------------------------------');

        // $collectedscrap = CollectedScrap::all();

        // foreach ($collectedscrap as $scrap) {
        //     $producer  = User::where('phone', '+234' . substr($scrap->producer_id, 1))->first();
        //     $collector = User::where('phone', $scrap->collector_id)->first();
        //     $materials = json_decode($scrap->materials);

        //     foreach ($materials as $material) {
        //         error_log('before ----->  ' . $material->weight);
        //         $material->weight = round($material->weight, 2);
        //         error_log('after ----->  ' . $material->weight);
        //     }

        //     $newScrap = CollectedScrap::find($scrap->id);

        //     if ($producer) {
        //         $newScrap->producer_id = $producer->id;
        //     }
        //     if ($collector) {
        //         $newScrap->collector_id = $collector->id;
        //     }

        //     $newScrap->materials = json_encode($materials);
        //     $newScrap->save();
        // }

        // error_log('--------------------------- Finished Collected Scrap --------------------------------------');

        // $notifications = Notification::all();

        // foreach ($notifications as $noty) {
        //     $user    = User::where('phone', $noty->user_id)->first();
        //     $newNoty = Notification::find($noty->id);

        //     if ($user) {
        //         $newNoty->user_id = $user->id;
        //     }

        //     $newNoty->save();
        // }

        // error_log('--------------------------- Finished Notifications --------------------------------------');

        // $pickupRequests = PickupRequest::all();

        // foreach ($pickupRequests as $pickup) {
        //     $producer  = User::where('phone', $pickup->producer_id)->first();
        //     $collector = '';
        //     if ($pickup->assigned_collector) {
        //         $collector = User::where('phone', $pickup->assigned_collector)->first();
        //     }
        //     $schedules = json_decode($pickup->schedule);

        //     foreach ($schedules as $key => $schedule) {
        //         if ($key == 'scheduleDate') {
        //             error_log('before date ----->  ' . json_encode($schedules));
        //             $schedules->schedule_date = $schedules->scheduleDate;
        //             unset($schedules->scheduleDate);
        //             error_log('after date ----->  ' . json_encode($schedules));
        //         } else if ($key == 'scheduleTime') {
        //             error_log('before time ----->  ' . json_encode($schedules));
        //             $schedules->schedule_time = $schedules->scheduleTime;
        //             unset($schedules->scheduleTime);
        //             error_log('after time ----->  ' . json_encode($schedules));
        //         }
        //     }

        //     $newPickup = PickupRequest::find($pickup->id);
        //     if ($producer) {
        //         $newPickup->producer_id = $producer->id;
        //     }
        //     if ($collector) {
        //         $newPickup->assigned_collector = $collector->id;
        //     }
        //     $newPickup->schedule = json_encode($schedules);
        //     $newPickup->save();
        // }

        // $collectedscrap = CollectedScrap::all();

        // foreach ($collectedscrap as $scrap) {
        //     $materials = json_decode($scrap->materials);

        //     foreach ($materials as $material) {
        //         // $material->weight = round($material->weight, 2);
        //         $exploded = explode('/kg', $material->price);
        //         if (array_key_exists(1, $exploded)) {
        //             error_log('before ----->  ' . $material->price);
        //             $material->price = $exploded[0];
        //             error_log('after ----->  ' . $material->price);
        //         }
        //     }

        //     $newScrap = CollectedScrap::find($scrap->id);

        //     $newScrap->materials = json_encode($materials);
        //     $newScrap->save();
        // }

        $users = User::all();

        foreach ($users as $user) {
            if ($user->userable_type !== 'Admin') {
                $scrap     = array();
                $materials = array();

                if ($user->userable_type === 'Collector') {
                    $materials = $user->collectedScrap->pluck('materials');
                } else if ($user->userable_type === 'Enterprise' || $user->userable_type === 'Household') {
                    $materials = $user->producedScrap->pluck('materials');
                }

                foreach ($materials as $material) {
                    foreach (json_decode($material) as $mat) {
                        array_push($scrap, $mat);
                    }
                }

                $holder = (object) [];

                array_filter($scrap, function ($material) use ($holder) {
                    if (property_exists($holder, $material->name)) {
                        $holder->{$material->name} = (object) [
                            'weight' => $holder->{$material->name}->weight + $material->weight,
                            'cost'   => $holder->{$material->name}->cost + ($material->weight * $material->price)
                        ];
                    } else {
                        $holder->{$material->name} = (object) [
                            'weight' => $material->weight,
                            'cost'   => ($material->weight * $material->price)
                        ];
                    }
                });

                $tonnage = 0;
                $cost    = 0;

                foreach ($holder as $material => $value) {
                    $tonnage += $holder->{$material}->weight;
                    $cost += $holder->{$material}->cost;
                }

                // error_log($user->first_name . ' ' . $user->last_name . ' ' . $tonnage . '-' . $cost);

                $user->total_tonnage = $tonnage;
                if ($user->userable_type === 'Enterprise' || $user->userable_type === 'Household') {
                    $user->total_earnings = $cost;
                }
                $user->save();

            }

        }

        error_log('--------------------------- Done --------------------------------------');

        return $this->successResponse('success', 200, true);
    }

    public function searchUsers(Request $request)
    {
        $query = $request->query('query');
        $users = User::where('first_name', 'like', '%' . $query . '%')
            ->orWhere('last_name', 'like', '%' . $query . '%')
            ->orWhere('phone', 'like', '%' . $query . '%')
            ->get();

        $users = $this->showAll($users);

        //broadcast search results with Pusher channels
        event(new SearchUserEvent($users));

        return $this->successResponse("ok", 200, true);
    }
}
