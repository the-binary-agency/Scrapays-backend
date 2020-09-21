<?php

namespace App\Http\Controllers;

use App\Collector;
use App\Events\SendLocation;
use Illuminate\Http\Request;

class MapDataController extends Controller
{
    public function ping(Request $request)
    {
        $lat = $request->lat;
        $long = $request->lng;
        $id = $request->id;
        $location = ["lat"=>$lat, "long"=>$long, "id"=>$id];
        event(new SendLocation($location, $id));
        // $coll = Collector::find($id);
        // $coll->current_loc = (object)["lat"=>$lat, "long"=>$long];
        // $coll->save();
        // ->pluck('id', 'current-loc');
        return response()->json(['status'=>'success', 'data'=>$location]);
    }

    public function getAddressWithCoordinates($coordinates)
    {
        $loc = json_decode($coordinates);
        $lat = $loc->lat;
        $lng = $loc->lng;
  
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://maps.googleapis.com/maps/api/geocode/json?latlng=".$lat.",".$lng."&key=AIzaSyAWR6b3RW5bSWplASOkIJjh31bWfvfPsOA",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET"
        ));

        $encodedResponse = curl_exec($curl);
        $res = json_decode($encodedResponse);
        $full_address = $res->results[0]->formatted_address;
        $address_without_country = explode(', Nigeria',$full_address)[0];
        return response()->json(['address'=> $address_without_country,], 200);

        $encodedResponse = curl_exec($curl);
        // error_log(json_encode($res));
        $res = json_decode($encodedResponse);
        $full_address = $res->results[0]->formatted_address;
    }
}
