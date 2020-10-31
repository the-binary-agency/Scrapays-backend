<?php

namespace App\Http\Controllers\Auth;

use App\Enterprise;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SignUpRequest;
use App\Http\Requests\UpdateRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EnterpriseAuthController extends ApiController
{

    /**
     * Get the authenticated Enterprise.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $enterprise = $request->user;
        return $this->showOne($enterprise);
    }

    /**
     * Store a newly created enterprise in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function register(SignUpRequest $request)
    {
        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->err, 401);
        } else {
            $enterprise = new Enterprise();

            $enterprise->company_name       = $request->company_name;
            $enterprise->company_size       = $request->companySize;
            $enterprise->industry           = $request->industry;
            $enterprise->gender             = $request->gender;
            $enterprise->recovery_automated = false;
            $enterprise->save();

            $enterprise->user()->save($res->user);

            return $this->sendSignupMail($request, 'Enterprise');
        }
    }

    /**
     * Update the specified enterprise's in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, User $enterprise)
    {
        $this->validate($request, [
            'company_name' => 'required',
            'company_size' => 'required',
            'industry'     => 'required',
            'address'      => 'required',
            'gender'       => 'required'
        ]);

        if ($request->file('avatar_image')) {

            if ($enterprise->avatar_image) {
                Storage::delete('public/profile_pictures/' . $enterprise->avatar_image);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $enterprise->avatar_image = $fileNameToStore;

            $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);

        }

        $enterprise->first_name = $request->input('first_name');
        $enterprise->last_name  = $request->input('last_name');
        $enterprise->email      = $request->input('email');

        // if (!$enterprise->isDirty()) {
        //     return $this->errorResponse('You need to specify different values to update.', 422);
        // }

        $enterprise->save();

        $ent = [
            'company_name' => $request->input('company_name'),
            'company_size' => $request->input('company_size'),
            'industry'     => $request->input('industry'),
            'address'      => $request->input('address'),
            'gender'       => $request->input('gender')
        ];

        $enterprise->userable->update($ent);

        return $this->successResponse('Profile Updated Successfully.', 200, true);

    }

    /**
     * Automate the specified enterprise's Pickups.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function automatePickup(User $enterprise)
    {
        if ($enterprise->userable->admin_automated == null) {
            $enterprise->userable->recovery_automated = true;
            $enterprise->push();

            return $this->successResponse('Your recovery has been automated successfully', 200, true);
        } else {
            return $this->successResponse('Your recovery automation has been explicitly set. Contact Scrapays for more info.', 401);
        }
    }

    /**
     * Unautomate the specified enterprise's Pickups.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function unAutomatePickup(User $enterprise)
    {
        if ($enterprise->userable->admin_automated == null) {
            $enterprise->userable->recovery_automated = false;
            $enterprise->push();

            return $this->successResponse('Your recovery has been unautomated successfully', 200, true);
        } else {
            return $this->successResponse('Your recovery automation has been explicitly set. Contact Scrapays for more info.', 401);
        }
    }
}
