<?php

namespace App\Http\Controllers;

use App\Household;
use App\Http\Controllers\ApiController;
use App\User;

class HouseholdController extends ApiController
{
    /**
     * Display a listing of the households.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $households = User::where('userable_type', 'Household')->get();
        return $this->showAll($households);
    }

    /**
     * Display the specified household.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $household)
    {
        $this->showOne($household);

    }

    /**
     * Display a listing of the specified households's produced scrap.
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function producedScrap(User $household)
    {
        $producedScrap = $household->producedScrap;
        return $this->showAll($producedScrap);
    }

    /**
     * Display a listing of the specified households's pickup requests
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function pickupRequests(User $household)
    {
        $pickups = $household->requestedPickup;
        return $this->showAll($pickups);

    }

    /**
     * Get the produced tonnage of the specified households
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function tonnage(User $household)
    {
        $pickups = $household->requestedPickup;
        return $this->showAll($pickups);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $household)
    {
        $household->delete();
        return $this->successResponse('User has been deleted successfully', 200, true);
    }
}
