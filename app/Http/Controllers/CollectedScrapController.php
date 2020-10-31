<?php

namespace App\Http\Controllers;

use App\CollectedScrap;
use App\Http\Controllers\ApiController;
use App\Inventory;
use App\PickupRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CollectedScrapController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $collectedScrap = CollectedScrap::all();
        return $this->showAll($collectedScrap);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'mode'         => 'required|string',
            'producer_id'  => 'required|string',
            'collector_id' => 'required|string'
        ]);

        if (!$this->isApproveddCollector($request->user)) {
            return $this->errorResponse('Only approved collectors are allowed to collect materials.', 422);
        }

        $materialstosave = array();

        foreach ($request->materials as $mat) {
            $materialobject = (object) [
                'name'   => $mat['name'],
                'weight' => round($mat['weight'], 2),
                'price'  => $mat['price'],
                'cost'   => $mat['cost']
            ];
            if ($mat['name'] == 'Composite') {
                $materialobject->{'comment'} = $mat['comment'];
            }
            array_push($materialstosave, $materialobject);
        }

        $scrap                 = new collectedScrap();
        $scrap->producer_id    = $request->producer_id;
        $scrap->producer_phone = $request->producer_phone;
        $scrap->collector_id   = $request->collector_id;
        $scrap->payment_method = $request->mode;
        $scrap->cost           = $request->total_cost;
        $scrap->total_tonnage  = $request->total_tonnage;
        $scrap->pickup_id      = $request->pickup_id;
        $scrap->materials      = json_encode($materialstosave);
        if ($producer = User::find($request->producer_id)) {
            if ($producer->userable_type == 'Enterprise') {
                if ($producer->userable->address == null) {
                    $scrap->address = $request->request_address;
                } else {
                    $scrap->address = $producer->userable->address;
                }
                $scrap->producer_type = 'Enterprise';
            } else {
                if ($producer->userable->request_address == null) {
                    $scrap->address = $request->request_address;
                } else {
                    $scrap->address = $producer->userable->request_address;
                }
                $scrap->producer_type = 'Household';
            }
        } else if ($request->new_user) {
            $us                   = (object) $request->new_user;
            $scrap->address       = $us->request_address;
            $scrap->producer_type = 'Household';
        }

        if ($request->pickup_id) {
            $pickup         = PickupRequest::find($request->pickup_id);
            $pickup->status = 'Resolved';
        }

        $newHousehold = null;
        if ($request->new_user) {
            $newHousehold = $this->registerProducerDuringCollection($request->new_user);
            if ($newHousehold != 'success') {
                return $this->errorResponse($newHousehold, 422);
            }
        }

        $this->calculateTonnage($request->producer_id, $request->total_tonnage, $request->total_cost, $request->collector_id);

        if ($request->mode == 'Wallet') {
            $fields = (object) [
                'data' => (object) [
                    'phone'     => '+234' . substr($request->producer_phone, 1),
                    'transID'   => Str::random(16),
                    'narration' => 'Payment for scrap collection.',
                    'amount'    => $request->total_cost
                ]
            ];
            $res     = $this->creditWallet($fields);
            $decoded = json_decode($res);
            if ($decoded->error->message) {
                return $this->errorResponse($decoded->error->message, 422);
            } else if ($decoded->success->message) {
                $scrap->save();
                if ($request->pickup_id) {
                    $pickup->save();
                }
            }
        } else {
            $scrap->save();
            if ($request->pickup_id) {
                $pickup->save();
            }
        }

        return $this->successResponse($scrap, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(CollectedScrap $collectedscrap)
    {
        return $this->showOne($collectedscrap);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(CollectedScrap $collectedscrap)
    {
        $collectedscrap->delete();
        return $this->successResponse('Collected Scrap deleted successfully.', 200, true);
    }

    public function calculateTonnage($producer_id, $total_tonnage, $totalCost, $collector_id)
    {
        $producer = User::find($producer_id);

        if ($tt = $producer->total_tonnage) {
            $formattedEarnings        = floatval($producer->total_earnings);
            $formattedCost            = floatval($totalCost);
            $producer->total_tonnage  = $tt += $total_tonnage;
            $producer->total_earnings = $formattedEarnings += $formattedCost;
            $producer->save();
        } else {
            $producer->total_tonnage  = $total_tonnage;
            $producer->total_earnings = $totalCost;
            $producer->save();
        }

        $collector = User::find($collector_id);

        if ($tt = $collector->total_tonnage) {
            $formattedCost            = floatval($totalCost);
            $collector->total_tonnage = $tt += $total_tonnage;
            $collector->save();
        } else {
            $collector->total_tonnage = $total_tonnage;
            $collector->save();
        }

    }

    public function validateProducerId($id)
    {
        if ($producer = User::find($id)) {
            return true;
        }
        return false;
    }

    public function isApproveddCollector($collector)
    {
        if ($collector->userable_type !== 'Collector' || $collector->userable->approved_as_collector != true) {
            return false;
        }
        return true;
    }

    public function getAllScrapHistory(Request $request)
    {
        $query      = (object) $request->query();
        $collection = CollectedScrap::all();
        if (property_exists($query, 'producer_type')) {
            $collection = $collection->where('producer_type', $query->producer_type);
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
        $total_cost    = 0;
        foreach ($obj2 as $mat) {
            $total_tonnage += $mat->weight;
            $total_cost += $mat->cost;
        }

        $hist = (object) [
            'materials'     => $obj2,
            'total_tonnage' => $total_tonnage,
            'total_cost'    => $total_cost
        ];

        return $hist;
    }

    public function producedTonnage(User $producer)
    {
        $inventories = Inventory::where('enterprise_id', $producer->id)->get();
        if ($producer->userable->recovery_automated == true || count($inventories) == 0) {
            $scrap = array();
            if ($totaltonnage = CollectedScrap::where('producer_id', $producer->id)->pluck('materials')) {
                foreach ($totaltonnage as $tt) {
                    foreach (json_decode($tt) as $mat) {
                        array_push($scrap, $mat);
                    }
                }

                $holder = (object) [];

                array_filter($scrap, function ($d) use ($holder) {
                    if (property_exists($holder, $d->name)) {
                        $holder->{$d->name} = $holder->{$d->name}+$d->weight;
                    } else {
                        $holder->{$d->name} = $d->weight;
                    }
                });

                $tonnage = array();

                foreach ($holder as $prop => $value) {
                    array_push($tonnage, (object) [
                        'name'   => $prop,
                        'weight' => $holder->{$prop}
                    ]);
                }

                return $this->successResponse($tonnage, 200, true);
            }
            return $this->successResponse([], 200, true);

        } else {
            $holder = (object) [];

            foreach ($inventories as $inventory) {
                if (property_exists($holder, $inventory->material)) {
                    $holder->{$inventory->material} = $holder->{$inventory->material}+$inventory->volume;
                } else {
                    $holder->{$inventory->material} = $inventory->volume;
                }
            }

            $tonnage = array();

            foreach ($holder as $prop => $value) {
                array_push($tonnage, (object) [
                    'name'   => $prop,
                    'weight' => (int) $holder->{$prop}
                ]);
            }

            return $this->successResponse($tonnage, 200, true);
        }
    }

    public function collectedTonnage(User $collector)
    {
        $scrap = array();
        if ($totaltonnage = CollectedScrap::where('collector_id', $collector->id)->pluck('materials')) {

            foreach ($totaltonnage as $tt) {
                foreach (json_decode($tt) as $mat) {
                    array_push($scrap, $mat);
                }
            }

            $holder = (object) [];

            array_filter($scrap, function ($d) use ($holder) {
                if (property_exists($holder, $d->name)) {
                    $holder->{$d->name} = $holder->{$d->name}+$d->weight;
                } else {
                    $holder->{$d->name} = $d->weight;
                }
            });

            $tonnage = array();

            foreach ($holder as $prop => $value) {
                array_push($tonnage, (object) [
                    'name'   => $prop,
                    'weight' => $holder->{$prop}
                ]);
            }

            return $this->successResponse($tonnage, 200, true);
        }
        return $this->successResponse([], 200, true);
    }
}
