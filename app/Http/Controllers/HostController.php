<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;

class HostController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $hosts = User::where('userable_type', 'Host')->get();
        return $this->showAll($hosts);

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $host)
    {
        return $this->showOne($host);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $host)
    {
        $host->delete();
        return $this->successResponse('User has been deleted successfully', 200, true);
    }
}
