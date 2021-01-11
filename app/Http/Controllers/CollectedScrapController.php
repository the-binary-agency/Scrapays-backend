<?php

namespace App\Http\Controllers;

use App\CollectedScrap;
use App\Http\Controllers\ApiController;
use App\Inventory;
use App\Material;
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
            'collector_id' => 'required|string'
        ]);

        if (!$request->newUser) {
            $this->validate($request, [
                'producer_id' => 'required|string'
            ]);
        }

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
        } else if ($request->newUser) {
            $us                   = (object) $request->newUser;
            $scrap->address       = $us->request_address;
            $scrap->producer_type = 'Household';
        }

        if ($request->pickup_id) {
            $pickup         = PickupRequest::find($request->pickup_id);
            $pickup->status = 'Resolved';
        }

        $new_household = null;
        if ($request->newUser) {
            $new_household = $this->registerProducerDuringCollection($request->newUser);
            if ($new_household->message != 'success') {
                return $this->errorResponse($new_household->message, 422);
            }
            $scrap->producer_id = $new_household->household->id;
        }

        $this->calculateTonnage($scrap->producer_id, $request->total_tonnage, $request->total_cost, $request->collector_id);

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

    public function calculateTonnage($producer_id, $total_tonnage, $total_cost, $collector_id)
    {
        $producer = User::findOrFail($producer_id);

        if ($tt = $producer->total_tonnage) {
            $formatted_earnings       = floatval($producer->total_earnings);
            $formatted_cost           = floatval($total_cost);
            $producer->total_tonnage  = $tt += $total_tonnage;
            $producer->total_earnings = $formatted_earnings += $formatted_cost;
            $producer->save();
        } else {
            $producer->total_tonnage  = $total_tonnage;
            $producer->total_earnings = $total_cost;
            $producer->save();
        }

        $collector = User::findOrFail($collector_id);

        if ($tt = $collector->total_tonnage) {
            $formatted_cost           = floatval($total_cost);
            $collector->total_tonnage = $tt += $total_tonnage;
            $collector->save();
        } else {
            $collector->total_tonnage = $total_tonnage;
            $collector->save();
        }

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
        $holder           = (object) [];
        $sorted_materials = array();

        $original_materials = Material::all();

        foreach ($collection as $coll) {
            array_filter(json_decode($coll->materials), function ($mat) use ($holder, $original_materials) {

                $collector_commission = 0;
                $host_commission      = 0;
                $revenue_commission   = 0;

                foreach ($original_materials as $o_mat) {
                    if ($o_mat->name == $mat->name) {
                        $collector_commission = $o_mat['collector_commission'];
                        $host_commission      = $o_mat['host_commission'];
                        $revenue_commission   = $o_mat['revenue_commission'];
                    }
                }

                if (property_exists($holder, $mat->name)) {

                    $current_material = $holder->{$mat->name};

                    // error_log(json_encode($current_material));

                    if ($mat->name == 'Composite') {
                        $prev_cost     = $current_material->cost;
                        $prev_weight   = $current_material->weight;
                        $prev_col_com  = $current_material->collector_commission;
                        $prev_host_com = $current_material->host_commission;
                        $prev_rev_com  = $current_material->revenue_commission;

                        $cost     = $prev_cost + $mat->cost;
                        $weight   = $prev_weight + $mat->weight;
                        $col_com  = $prev_col_com + ($mat->weight * $collector_commission);
                        $host_com = $prev_host_com + ($mat->weight * $host_commission);
                        $rev_com  = $prev_rev_com + ($mat->weight * $revenue_commission);

                        $holder->{$mat->name} = (object) [
                            'weight'               => $weight,
                            'cost'                 => $cost,
                            'collector_commission' => $col_com,
                            'host_commission'      => $host_com,
                            'revenue_commission'   => $rev_com
                        ];

                    } else {
                        $prev_cost     = $current_material->cost;
                        $prev_weight   = $current_material->weight;
                        $prev_col_com  = $current_material->collector_commission;
                        $prev_host_com = $current_material->host_commission;
                        $prev_rev_com  = $current_material->revenue_commission;

                        $cost     = $prev_cost + ($mat->weight * $mat->price);
                        $weight   = $prev_weight + $mat->weight;
                        $col_com  = $prev_col_com + ($mat->weight * $collector_commission);
                        $host_com = $prev_host_com + ($mat->weight * $host_commission);
                        $rev_com  = $prev_rev_com + ($mat->weight * $revenue_commission);

                        $holder->{$mat->name} = (object) [
                            'weight'               => $weight,
                            'cost'                 => $cost,
                            'collector_commission' => $col_com,
                            'host_commission'      => $host_com,
                            'revenue_commission'   => $rev_com
                        ];
                    }

                } else {
                    if ($mat->name == 'Composite') {
                        $holder->{$mat->name} = (object) [
                            'weight'               => $mat->weight,
                            'cost'                 => $mat->cost,
                            'collector_commission' => $collector_commission * $mat->weight,
                            'host_commission'      => $host_commission * $mat->weight,
                            'revenue_commission'   => $revenue_commission * $mat->weight
                        ];
                    } else {
                        $holder->{$mat->name} = (object) [
                            'weight'               => $mat->weight,
                            'cost'                 => $mat->weight * $mat->price,
                            'collector_commission' => $collector_commission * $mat->weight,
                            'host_commission'      => $host_commission * $mat->weight,
                            'revenue_commission'   => $revenue_commission * $mat->weight
                        ];
                    }
                }
            });
        }

        foreach ($holder as $material => $values) {
            $current_material = $holder->{$material};
            array_push($sorted_materials, (object) [
                'name'                 => $material,
                'weight'               => $current_material->weight,
                'cost'                 => $current_material->cost,
                'collector_commission' => $current_material->collector_commission,
                'host_commission'      => $current_material->host_commission,
                'revenue_commission'   => $current_material->revenue_commission
            ]);
        }

        $total_tonnage  = 0;
        $total_cost     = 0;
        $total_col_com  = 0;
        $total_host_com = 0;
        $total_rev_com  = 0;

        foreach ($sorted_materials as $material) {
            $total_tonnage += $material->weight;
            $total_cost += $material->cost;
            $total_col_com += $material->collector_commission;
            $total_host_com += $material->host_commission;
            $total_rev_com += $material->revenue_commission;

        }

        $hist = (object) [
            'materials'                  => $sorted_materials,
            'total_tonnage'              => $total_tonnage,
            'total_cost'                 => $total_cost,
            'total_collector_commission' => $total_col_com,
            'total_host_commission'      => $total_host_com,
            'total_revenue_commission'   => $total_rev_com
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

                array_filter($scrap, function ($material) use ($holder) {
                    if (property_exists($holder, $material->name)) {
                        $holder->{$material->name} = $holder->{$material->name}+$material->weight;
                    } else {
                        $holder->{$material->name} = $material->weight;
                    }
                });

                $tonnage = array();

                foreach ($holder as $material => $value) {
                    array_push($tonnage, (object) [
                        'name'   => $material,
                        'weight' => $holder->{$material}
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

            foreach ($holder as $material => $value) {
                array_push($tonnage, (object) [
                    'name'   => $material,
                    'weight' => (int) $holder->{$material}
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

            array_filter($scrap, function ($material) use ($holder) {
                if (property_exists($holder, $material->name)) {
                    $holder->{$material->name} = $holder->{$material->name}+$material->weight;
                } else {
                    $holder->{$material->name} = $material->weight;
                }
            });

            $tonnage = array();

            foreach ($holder as $material => $value) {
                array_push($tonnage, (object) [
                    'name'   => $material,
                    'weight' => $holder->{$material}
                ]);
            }

            return $this->successResponse($tonnage, 200, true);
        }
        return $this->successResponse([], 200, true);
    }
}
