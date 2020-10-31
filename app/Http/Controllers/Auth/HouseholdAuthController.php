<?php

namespace App\Http\Controllers\Auth;

use App\Household;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\UpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HouseholdAuthController extends ApiController
{
    /**
     * Get the authenticated Household
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $household = $request->user;
        return $this->showOne($household);
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
            'request_address' => 'required'
        ]);

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->error, 401);
        } else {

            $household                  = new Household();
            $household->request_address = $request->request_address;
            $household->save();

            $household->user()->save($res->user);

            return $this->sendSignupMail($request, 'Household');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, User $household)
    {
        $this->validate($request, [
            'request_address' => 'required'
        ]);

        if ($request->file('avatar_image')) {

            if ($household->avatar_image) {
                Storage::delete('public/profile_pictures/' . $household->avatar_image);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $household->avatar_image = $fileNameToStore;

            $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);
        }

        $household->first_name = $request->input('first_name');
        $household->last_name  = $request->input('last_name');
        $household->email      = $request->input('email');

        // if (!$household->isDirty()) {
        //     return $this->errorResponse('You need to specify different values to update.', 422);
        // }

        $household->save();

        $househld = [
            'request_address' => $request->input('request_address')
        ];

        $household->userable->update($househld);

        return $this->successResponse('Profile Updated Successfully.', 200, true);
    }

    public function updateUSSD(Request $request, $phone)
    {
        $this->validate($request, [
            'request_address' => 'required'
        ]);

        $household = User::where('phone', $phone);

        // if ($request->file('avatar_image')) {

        //     if ($household->avatar_image) {
        //         Storage::delete('public/profile_pictures/' . $household->avatar_image);
        //     }
        //     // get the File Name and Extension
        //     $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
        //     // let get only the file name
        //     $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
        //     // get file extension
        //     $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
        //     // rename the file
        //     $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

        //     $household->avatar_image = $fileNameToStore;

        //     $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);
        // }

        // $household->first_name = $request->input('first_name');
        // $household->last_name  = $request->input('last_name');
        // $household->email      = $request->input('email');

        // if (!$household->isDirty()) {
        //     return $this->errorResponse('You need to specify different values to update.', 422);
        // }

        // $household->save();

        $househld = [
            'request_address' => $request->input('request_address')
        ];

        $household->userable->update($househld);

        return $this->successResponse('Profile Updated Successfully.', 200, true);
    }
}
