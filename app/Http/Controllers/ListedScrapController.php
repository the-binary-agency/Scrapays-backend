<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\ListedScrap;
use Illuminate\Http\Request;

class ListedScrapController extends ApiController
{
    /**
     * Display a listing of the scrap.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $scrap = ListedScrap::all();
        return $this->showAll($scrap);
    }

    /**
     * Store a newly created scrap in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'first_name'           => 'required|string',
            'last_name'            => 'required|string',
            'phone'                => 'required|string',
            'email'                => 'required|email',
            'material_images'      => 'required',
            'material_location'    => 'required|string',
            'material_description' => 'required'
        ]);

        $material_images      = array();
        $material_description = array();

        if ($files = $request->file('material_images')) {
            foreach ($files as $image) {
                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

                $image->storeAs('public/Material_Images', $fileNameToStore);

                array_push($material_images, $fileNameToStore);
            }
        }

        foreach ($request->input('material_description') as $description) {
            array_push($material_description, $description);
        }

        $listedscrap = new ListedScrap();

        $listedscrap->company_name         = $request->input('company_name');
        $listedscrap->first_name           = $request->input('first_name');
        $listedscrap->last_name            = $request->input('last_name');
        $listedscrap->phone                = $request->input('phone');
        $listedscrap->email                = $request->input('email');
        $listedscrap->material_images      = json_encode($material_images);
        $listedscrap->material_location    = $request->input('material_location');
        $listedscrap->material_description = json_encode($material_description);
        $listedscrap->save();

        return $this->successResponse("Scrap listed succesfully.", 201, true);
    }

    /**
     * Display the specified scrap.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(ListedScrap $listedscrap)
    {
        return $this->showOne($listedscrap);
    }

    /**
     * Remove the specified scrap from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(ListedScrap $listedscrap)
    {
        $listedscrap->delete();
        return $this->successResponse("Scrap deleted Succesfully.", 201, true);
    }
}
