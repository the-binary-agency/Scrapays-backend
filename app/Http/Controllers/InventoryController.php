<?php

namespace App\Http\Controllers;

use App\Inventory;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class InventoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            ]]);
    }

    public function submitInventory(Request $request)
    {
        if( $request->file('receipt') ){

            // get the File Name and Extension
            $fileNameWithExt = $request->file('receipt')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('receipt')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName ."_" . time() .".".$fileExt;

            $request->file('receipt')->storeAs('public/Inventory_Receipts', $fileNameToStore);

        }

        $inventory = new Inventory();
        $inventory->enterpriseID = $request->input('enterpriseID');
        $inventory->material = $request->input('material');
        $inventory->volume = $request->input('volume');
        $inventory->revenueGenerated = $request->input('revenueGenerated');
        $inventory->receipt = $fileNameToStore;

        $inventory->save(); 

        return response()->json(['Inventory Updated Successfully.'], Response::HTTP_CREATED);
    }

    public function getUserInventory($id)
    {
        if($user = User::find($id))
        {
            $inventory =  DB::table('inventories')->where('enterpriseID', $id)->get();
            return response()->json([$inventory], Response::HTTP_OK);
        }else{
            return response()->json(['You have no inventory with us.'], Response::HTTP_NOT_FOUND);
        };

    }

    public function getSingleInventory(Request $request)
    {
        $user = User::find($request->input('id'));
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        $inventory =  Inventory::find($request->input('inventoryID'));
        return response()->json([$inventory], Response::HTTP_OK);
    }

    public function getAllInventory(Request $request)
    {
        $user = User::find($request->input('id'));
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        $inventory =  Inventory::all();
        return response()->json([$inventory], Response::HTTP_OK);
    }
}
