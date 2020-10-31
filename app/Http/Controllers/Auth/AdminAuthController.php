<?php

namespace App\Http\Controllers\Auth;

use App\Admin;
use App\Http\Controllers\ApiController;
use App\Http\Requests\UpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminAuthController extends ApiController
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {
        $this->validate($request, [
            'permissions' => 'required'
        ]);

        $prevadmin = User::find($request->input('prevAdmin'));
        if (!$prevadmin || $prevadmin->userable_type != 'Admin') {
            return $this->unauthenticated();
        }

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResposne($res->error, 401);
        } else {

            $admin              = new Admin();
            $admin->permissions = json_encode($request->input('permissions'));
            $admin->save();

            $admin->user()->save($res->user);

            return $this->successResponse('You have successfully created a new Admin', 200, true);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, User $admin)
    {
        if ($request->file('avatar_image')) {

            if ($admin->avatar_image) {
                Storage::delete('public/profile_pictures/' . $admin->avatar_image);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $admin->avatar_image = $fileNameToStore;

            $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);

        }

        $admin->first_name = $request->input('first_name');
        $admin->last_name  = $request->input('last_name');
        $admin->email      = $request->input('email');

        // if (!$admin->isDirty()) {
        //     return $this->errorResponse('You need to specify different values to update.', 422);
        // }

        $admin->save();

        return $this->successResponse('Profile Updated Successfully.', 200, true);
    }

    /**
     * Get the authenticated Admin
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $admin = $request->admin;
        return $this->showOne($admin);
    }
}
