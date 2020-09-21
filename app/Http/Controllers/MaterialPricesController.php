<?php

namespace App\Http\Controllers;

use App\materialPrices;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class MaterialPricesController extends Controller
{
    public function getMaterialPrices( $id )
    {
        $user = User::find($id);
        if (!$user) 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };
        $prices = materialPrices::all();

        return response()->json([ 'prices' => $prices ]);
    }

    public function setMaterialPrices( Request $request, $id )
    {
        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        if( $image = $request->file('image') ){
                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // let get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName ."_" . time() .".".$fileExt;
    
                $image->storeAs('public/material_list_images', $fileNameToStore);
        }

            $material = new materialPrices;
            
            $material->name = $request->input('name');
            $material->price = $request->input('price');
            $material->image = $fileNameToStore;
            $material->save();

        return response()->json(['message' => 'New Material Added Successfully.'], Response::HTTP_CREATED);
    }

    public function editMaterialPrices( Request $request, $id )
    {
        $this->validate($request, [
            'id' => 'required|string',
            'name' => 'required|string',
            'price' => 'required|string',
        ]);

        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        $oldMaterial = materialPrices::find($request->input('id'));

        if( $image = $request->file('image') ){
            
                Storage::delete('public/material_list_images/'.$oldMaterial->image);

                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // let get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName ."_" . time() .".".$fileExt;
    
                $image->storeAs('public/material_list_images', $fileNameToStore);
                $oldMaterial->image = $fileNameToStore;
            }
            
            $oldMaterial->name = $request->input('name');
            $oldMaterial->price = $request->input('price');
            $oldMaterial->save();

        return response()->json(['message' => 'Material Price Updated Successfully.'], Response::HTTP_CREATED);
    }

    public function deleteMaterialPrices( Request $request, $id )
    {
        $this->validate($request, [
            'id' => 'required|string',
            'name' => 'required|string',
            'price' => 'required|string',
        ]);

        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        $oldMaterial = materialPrices::find($request->input('id'));

        if( $oldMaterial->image ){
            Storage::delete('public/material_list_images/'.$oldMaterial->image);
        }
            
        $oldMaterial->delete();

        return response()->json(['message' => 'Material Deleted Successfully.'], Response::HTTP_CREATED);
    }

}
