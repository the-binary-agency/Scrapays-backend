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
            'producerPhone' => 'required|string',
            'collectorID' => 'required|string',
        ]);

        if( !$this->validateRole($request->collectorID) )
        {
            return $this->unauthorisedResponse();
        }

        if(!$this->validateUUID($request->input('producerPhone')) )
        {
            return $this->failedResponse();
        }
        
        $materialstosave = array();
        
        foreach ($request->materials as $mat) {
           $materialobject = (object) [
                'name' => $mat['name'],
                'weight' => $mat['weight'],
            ];
            
            array_push($materialstosave, $materialobject);
        }

        $scrap = new collectedScrap();
        $scrap->producerPhone = $request->producerPhone;
        $scrap->collectorID = $request->collectorID;
        $scrap->materials = json_encode($materialstosave);
        $scrap->save();

        return $this->successResponse();
    }

    public function validateUUID($phone)
    {
        return !!User::find($phone)->first();
    }

    public function validateRole($id)
    {
        $user = User::find($id);
        return !!$user->userable_type == 'App\Collector';
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
