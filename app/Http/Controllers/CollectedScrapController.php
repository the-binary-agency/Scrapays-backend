<?php

namespace App\Http\Controllers;

use App\collectedScrap;
use App\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectedScrapController extends Controller
{
    public function listCollectedScrap( Request $request )
    {
        $this->validate($request, [
            'producerID' => 'required|string',
            'collectorID' => 'required|string',
            'role' => 'required|string'
        ]);

        if(!$this->validateID($request->input('collectorID')) || !$this->validateRole($request->input('role'))  )
        {
            return $this->unauthorisedResponse();
        }

        if(!$this->validateUUID($request->input('producerID')) )
        {
            return $this->failedResponse();
        }

        collectedScrap::create($request->all());
        return $this->successResponse();
    }

    public function validateUUID($uuid)
    {
        return !!User::where('id', $uuid)->first();
    }

    public function validateID($id)
    {
        return !!User::where('id', $id)->first();
    }

    public function validateRole($role)
    {
        return !!User::where('role', $role)->first();
    }

     public function successResponse()
    {
        return response()->json([
            'data' => 'Collected Scrap Listed Successfully.'
        ], Response::HTTP_OK);
    }

    public function unauthorisedResponse()
    {
        return response()->json([
            'error' => 'You are not authorised.'
        ], Response::HTTP_UNAUTHORIZED);
    }

    public function failedResponse()
    {
        return response()->json([
            'error' => 'There is no user with the supplied ID'
        ], Response::HTTP_NOT_FOUND);
    }
}
