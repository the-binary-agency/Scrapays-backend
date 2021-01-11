<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\User;
use Illuminate\Http\Request;

class AdminController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $admins = User::where('userable_type', 'Admin')->get();
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

    /**
     * Display the specified resource.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function changePermissions(Request $request, User $admin)
    {
        if ($request->user->id == $admin->id) {
            return $this->errorResponse('You cannot change your own permissions.', 403);
        }

        $userable = [
            'permissions' => json_encode($request->permissions)
        ];

        $admin->userable->update($userable);

        return $this->successResponse('Permissions Updated Successfully.', 200, true);

    }

}
