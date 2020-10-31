<?php

namespace App\Http\Controllers;

use App\Enterprise;
use App\Http\Controllers\ApiController;
use App\User;

class EnterpriseController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $enterprises = User::where('userable_type', 'Enterprise')->get();
        return $this->showAll($enterprises);
    }

    /**
     * Display the specified enterprise.
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $enterprise)
    {
        return $this->showOne($enterprise);
    }

    /**
     * Display a listing of the specified enterprise's produced scrap.
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function producedScrap(User $enterprise)
    {
        $producedScrap = $enterprise->producedScrap;
        return $this->showAll($producedScrap);
    }

    /**
     * Display a listing of the specified enterprise's pickup requests
     *
     * @param  String  $id
     * @return \Illuminate\Http\Response
     */
    public function pickupRequests(User $enterprise)
    {
        $pickups = $enterprise->requestedPickup;
        return $this->showAll($pickups);

    }

    /**
     * Remove the specified enterprise's from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $enterprise)
    {
        $enterprise->delete();
        return $this->successResponse('User has been deleted successfully', 200, true);
    }

}
