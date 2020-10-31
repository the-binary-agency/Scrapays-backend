<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;

class CollectorController extends ApiController
{

    public function __construct()
    {
        // $this->middleware('can:view,collector')->only('index');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $collectors = User::where('userable_type', 'Collector')->get();
        return $this->showAll($collectors);
    }

    /**
     * Display the specified collector.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $collector)
    {
        return $this->showOne($collector);
    }

    /**
     * Display a listing of the specified collector's collected scrap.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function collectedScrap(User $collector)
    {
        $collectedScrap = $collector->collectedScrap;
        return $this->showAll($collectedScrap);
    }

    /**
     * Display a listing of the specified collector's assigned requests
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function pickupRequests(User $collector)
    {
        $pickups = $collector->assignedPickup;
        return $this->showAll($pickups);

    }

    /**
     * Toggle the specified collector's activated status
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function toggle(User $collector)
    {
        $data = [
            'approved_as_collector' => !$collector->userable->approved_as_collector
        ];

        $collector->userable->update($data);

        if ($collector->userable->approved_as_collector) {
            return $this->successResponse('Collector was activated successfully.', 200, true);
        } else {
            return $this->successResponse('Collector was deactivated successfully.', 200, true);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $collector)
    {
        $collector->delete();
        return $this->successResponse('Collector has been deleted successfully', 200, true);
    }

    /**
     * Show the specified resource details.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function details($id)
    {
        if ($collector = User::find($id)) {
            return $this->successResponse($collector, 200, true);
        } else if ($collector = User::where('phone', '+234' . substr($id, 1))->first()) {
            return $this->successResponse($collector, 200, true);
        };
        return $this->errorResponse('Not found', 404);

    }

}
