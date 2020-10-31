<?php

namespace App\Http\Controllers\Auth;

use App\Host;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\UpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HostAuthController extends ApiController
{
    /**
     * Get the authenticated Host
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $host = $request->user;
        return $this->showOne($host);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(SignUpRequest $request)
    {
        $this->validate($request, [
            'hosting_address'    => 'required|string',
            'hosting_duration'   => 'required',
            'space_size'         => 'required',
            'hosting_start_date' => 'required'
        ]);

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->err, 401);
        } else {

            $host                   = new Host();
            $host->host_address     = $request->hostAddress;
            $host->space_size       = $request->spaceSize;
            $host->hosting_duration = $request->hostingDuration;
            $host->host_start_date  = $request->hostStartDate;
            $host->save();

            $host->user()->save($res->user);

            return $this->sendSignupMail($request, 'Host');
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, User $user)
    {
        $this->validate($request, [
            'hosting_address'    => 'required|string',
            'hosting_duration'   => 'required',
            'space_size'         => 'required',
            'hosting_start_date' => 'required'
        ]);

        if ($request->file('avatar_image')) {

            if ($user->avatar_image) {
                Storage::delete('public/profile_pictures/' . $user->avatar_image);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $user->avatar_image = $fileNameToStore;

            $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);

        }

        $user->first_name = $request->input('first_name');
        $user->last_name  = $request->input('last_name');
        $user->email      = $request->input('email');

        if (!$user->isDirty()) {
            return $this->errorResponse('You need to specify different values to update.', 422);
        }

        $user->save();

        $host = [
            'hosting_address'    => $request->input('hostingAddress'),
            'hosting_duration'   => $request->input('hostingDuration'),
            'space_size'         => $request->input('spaceSize'),
            'hosting_start_date' => $request->input('hostingStartDate')
        ];

        $user->userable->update($host);

        return $this->successResponse('Profile Updated Successfully.', 200, true);
    }
}
