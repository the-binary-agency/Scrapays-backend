<?php

namespace App\Http\Controllers\Auth;

use App\Collector;
use App\Http\Controllers\ApiController;
use App\Http\Requests\SignUpRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CollectorAuthController extends ApiController
{
    /**
     * Get the authenticated Collector
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        $collector = $request->user;
        return $this->showOne($collector);
    }

    public function register(SignUpRequest $request)
    {
        $this->validate($request, [
            'collection_coverage_zone' => 'required|string'
        ]);

        $res = $this->saveUser($request);

        if ($res->error) {
            return $this->errorResponse($res->err, 401);
        } else {

            $collector                           = new Collector();
            $collector->collection_coverage_zone = $request->collection_coverage_zone;
            $collector->approved_as_collector    = false;
            $collector->save();

            $collector->user()->save($res->user);

            return $this->sendSignupMail($request, 'Collector');
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $collector)
    {
        $this->validate($request, [
            'collection_coverage_zone' => 'required|string'
        ]);

        if ($request->file('avatar_image')) {

            if ($collector->avatar_image) {
                Storage::delete('public/profile_pictures/' . $collector->avatar_image);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatar_image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatar_image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $collector->avatar_image = $fileNameToStore;

            $request->file('avatar_image')->storeAs('public/profile_pictures', $fileNameToStore);

        }

        $collector->first_name = $request->input('first_name');
        $collector->last_name  = $request->input('last_name');
        $collector->email      = $request->input('email');

        // if (!$collector->isDirty()) {
        //     return $this->errorResponse('You need to specify different values to update.', 422);
        // }

        $collector->save();

        $col = [
            'collection_coverage_zone' => $request->input('collection_coverage_zone')
        ];

        $collector->userable->update($col);

        return $this->successResponse('Profile Updated Successfully.', 200, true);
    }
}
