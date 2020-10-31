<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;

class AdminController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admins = User::all();
        return $this->showAll($admins);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $admin)
    {
        return $this->showOne($admin);
    }

}
