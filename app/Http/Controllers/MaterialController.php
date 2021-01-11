<?php

namespace App\Http\Controllers;

use App\Http\Controllers\ApiController;
use App\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MaterialController extends ApiController
{
    /**
     * Display a listing of the materials.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $materials = Material::all();
        return $this->showAll($materials);

    }

    /**
     * Store a newly created materials in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name'                 => 'required|string',
            'price'                => 'required|integer',
            'collector_commission' => 'required|integer',
            'host_commisssion'     => 'required|integer',
            'revenue_commisssion'  => 'required|integer',
            'image'                => 'required'
        ]);

        if ($image = $request->file('image')) {
            //  get the File Name and Extension
            $fileNameWithExt = $image->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $image->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName . "_" . time() . "." . $fileExt;

            $image->storeAs('public/material_list_images', $fileNameToStore);
        }

        $material = new Material();

        $material->name                 = $request->input('name');
        $material->price                = $request->input('price');
        $material->collector_commission = $request->input('collector_commission');
        $material->host_commisssion     = $request->input('host_commisssion');
        $material->revenue_commission   = $request->input('revenue_commission');
        $material->image                = $fileNameToStore;
        $material->save();

        return $this->successResponse('New Material Added Successfully.', 201, true);
    }

    /**
     * Display the specified materials.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Material $material)
    {
        return $this->showOne($material);
    }

    /**
     * Update the specified materials in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Material $material)
    {
        $this->validate($request, [
            'name'                 => 'required|string',
            'price'                => 'required|integer',
            'collector_commission' => 'required|integer',
            'host_commission'      => 'required|integer',
            'revenue_commission'   => 'required|integer'
        ]);

        if ($request->file('mat-image')) {

            Storage::delete('public/material_list_images/' . $material->image);

            //  get the File Name and Extension
            $fileNameWithExt = $request->file('mat-image')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('mat-image')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = "material-" . $fileName . "_" . time() . "." . $fileExt;

            $material->image = $fileNameToStore;

            $request->file('mat-image')->storeAs('public/material_list_images', $fileNameToStore);
        }

        $material->name                 = $request->input('name');
        $material->price                = $request->input('price');
        $material->collector_commission = $request->input('collector_commission');
        $material->host_commission      = $request->input('host_commission');
        $material->revenue_commission   = $request->input('revenue_commission');
        $material->save();

        return $this->successResponse('Material updated successfully.', 200, true);
    }

    /**
     * Remove the specified materials from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Material $material)
    {
        $material->delete();
        return $this->successResponse('Material deleted successfully.', 200, true);
    }
}
