<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ListedScrap;
use App\User;
use Illuminate\Support\Facades\Storage;

class ListedScrapController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'list',
            ]]);
    }

    public function index(){
        $listedscrap = ListedScrap::all();
        return json_encode($listedscrap);
    }

    public function getSingleScrap(Request $request){
        $req = $request->input('id');
        $Scrap = ListedScrap::find($req);
        $Scraparray = array();
        array_push($Scraparray, $Scrap); 
        return json_encode($Scraparray);
    }

    public function list(Request $request)
    {
        $this->validate($request, [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'phone' => 'required|string',
            'email' => 'required|email',
            'materialImages' => 'required',
            'materialLocation' => 'required|string',
            'materialDescription' => 'required',
        ]);

        $materialImages = array();
        $materialDescription = array();

        if( $files = $request->file('materialImages') ){
            foreach($files as $image){
                //  get the File Name and Extension
                $fileNameWithExt = $image->getClientOriginalName();
                // get only the file name
                $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  
                // get file extension
                $fileExt = $image->getClientOriginalExtension();
                // rename the file
                $fileNameToStore = $fileName ."_" . time() .".".$fileExt;
    
                $path = $image->storeAs('public/Material_Images', $fileNameToStore);

                array_push($materialImages, $fileNameToStore); 
            }
        }
        
            foreach($request->input('materialDescription') as $description)
            {
                array_push($materialDescription, $description); 
            }

            $listedscrap = new ListedScrap;
            
            $listedscrap->companyName = $request->input('companyName');
            $listedscrap->firstName = $request->input('firstName');
            $listedscrap->lastName = $request->input('lastName');
            $listedscrap->phone = $request->input('phone');
            $listedscrap->email = $request->input('email');
            $listedscrap->materialImages = json_encode($materialImages);
            $listedscrap->materialLocation = $request->input('materialLocation');
            $listedscrap->materialDescription = json_encode($materialDescription);
            $listedscrap->save();

        return response()->json(["message" => "Scrap Listed Succesfully"]);
    }

    public function splitString($string)
    {
        return explode("-", $string);
    }

}
