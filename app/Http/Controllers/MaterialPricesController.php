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

        $prices = array();
        foreach ($request->input('material_name') as $name) {
           $pricestobesent = (object) [
                'name' => $name,
                'price' => '',
                'image' => ''
            ];
            
            array_push($prices, $pricestobesent);
        }
        $i = 0;
        foreach ($request->input('material_price') as $price) {
            $prices[$i]->price = $price;
            $i++;
        }
        $i = 0;
        if( $files = $request->file('material_img') ){
            foreach($files as $image){
                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // let get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName ."_" . time() .".".$fileExt;
    
                $path = $image->storeAs('public/material_list_images', $fileNameToStore);
                $prices[$i]->image = $fileNameToStore;
                $i++;
            }
        }

        foreach($prices as $price){
            $material = new materialPrices();
            
            $material->name = $price->name;
            $material->price = $price->price;
            $material->image = $price->image;
            $material->save();
        }

        return response()->json(['message' => 'New MMaterial Added Successfully.'], Response::HTTP_CREATED);
    }

    public function editMaterialPrices( Request $request, $id )
    {
        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

        foreach( materialPrices::all() as  $price)
        {
            Storage::delete('public/material_list_images/'.$price->$price->image);
            $price->delete();
        }

        $prices = array();
        foreach ($request->input('material_name') as $name) {
           $pricestobesent = (object) [
                'name' => $name,
                'price' => '',
                'image' => ''
            ];
            
            array_push($prices, $pricestobesent);
        }
        $i = 0;
        foreach ($request->input('material_price') as $price) {
            $prices[$i]->price = $price;
            $i++;
        }
        $i = 0;
        if( $files = $request->file('material_img') ){
            foreach($files as $image){
                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // let get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName ."_" . time() .".".$fileExt;
    
                $path = $image->storeAs('public/material_list_images', $fileNameToStore);
                $prices[$i]->image = $fileNameToStore;
                $i++;
            }
        }

        foreach($prices as $price){
            $material = new materialPrices();
            
            $material->name = $price->name;
            $material->price = $price->price;
            $material->image = $price->image;
            $material->save();
        }

        return response()->json(['message' => 'Material Prices Updated Successfully.'], Response::HTTP_CREATED);
    }

}
