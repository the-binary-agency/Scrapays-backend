<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Inventory;
use App\User;
use Illuminate\Http\Request;

class InventoryController extends ApiController
{
    /**
     * Display a listing of the inventories.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $inventories = Inventory::all();
        $this->showAll($inventories);
    }

    /**
     * Store a newly created inventory in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'enterprise_id'     => 'required',
            'material'          => 'required',
            'volume'            => 'required',
            'revenue_generated' => 'required',
            'receipt'           => 'required'
        ]);

        if ($request->file('receipt')) {

            // get the File Name and Extension
            $fileNameWithExt = $request->file('receipt')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('receipt')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $request->file('receipt')->storeAs('public/Inventory_Receipts', $fileNameToStore);

        }

        $inventory                    = new Inventory();
        $inventory->enterprise_id     = $request->input('enterprise_id');
        $inventory->enterprise_phone  = $request->user->phone;
        $inventory->material          = $request->input('material');
        $inventory->volume            = $request->input('volume');
        $inventory->revenue_generated = $request->input('revenue_generated');
        $inventory->receipt           = $fileNameToStore;

        $inventory->save();

        return $this->successResponse('Inventory Updated Successfully.', 201, true);
    }

    /**
     * Display the specified inventory.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $inventory = Inventory::findOrFail($id);
        return $this->showAll($inventory);
    }

    /**
     * Display the specified enterpise's inventory.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function get(User $enterprise)
    {
        $inventory = $enterprise->inventories;
        return $this->showAll($inventory);
    }

    /**
     * Remove the specified inventory from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Inventory $inventory)
    {
        $inventory->delete();
        return $this->successResponse('Inventory deleted successfully.', 200, true);
    }
}
