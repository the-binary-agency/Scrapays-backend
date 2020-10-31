<?php

namespace App\Http\Controllers;

use App\Events\SendLocation;
use App\Http\Controllers\ApiController;
use App\LocationData;
use Illuminate\Http\Request;
use Importer;

class LocationController extends ApiController
{
    public $lastlocation;
    public $lastsublocation;
    public $in = 0;

    public function import()
    {
        return view('LocationData.location');
    }

    public function upload(Request $request)
    {
        $file = $request->file('locationData');

        $excel = Importer::make('Excel');
        $excel->load($file);
        $collection = $excel->getCollection();

        $locations = (object) [];
        for ($i = 0; $i < sizeof($collection); $i++) {
            try {
                $row = $collection[$i];
                if ($row[0] != '') {
                    $locations->{$row[0]} = (object) [];
                    $this->lastlocation   = $row[0];
                } else if ($row[0] == '' && $row[1] != '') {
                    $locations->{$this->lastlocation}->{$row[1]} = (object) [];
                    $this->lastsublocation                       = $row[1];
                } else if ($row[0] == '' && $row[1] == '' && $row[2] != '') {
                    $sublocation              = $locations->{$this->lastlocation}->{$this->lastsublocation};
                    $sublocation->{$this->in} = $row[2];
                    $this->in++;
                    //    array_push($sublocation, 'abcs');
                    $i++;
                }
            } catch (\Exception $e) {
                $this->console_log($e->getMessage());
                return redirect()->back()->with(['errors' => $e->getMessage()]);
            }
        }

        $locs = LocationData::find(1);
        if ($locs) {
            $locs->locations = json_encode($locations);
            $locs->save();
        } else {
            $newlocs            = new LocationData();
            $newlocs->locations = json_encode($locations);
            $newlocs->save();
        }

        $this->console_log($locations);
        return redirect()->back()->with(['success' => 'Location Data Uploaded Successfully.']);
    }

    public function getLocations()
    {
        $raw_locations = LocationData::find(1)->first()->pluck('locations');
        $locations     = json_decode($raw_locations[0]);

        return $this->successResponse($locations, 200, true);
    }

    public function console_log($output, $with_script_tags = true)
    {
        $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) .
            ');';
        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }
        echo $js_code;
    }

    public function ping(Request $request)
    {
        $lat      = $request->lat;
        $long     = $request->lng;
        $id       = $request->id;
        $location = ["lat" => $lat, "long" => $long, "id" => $id];
        event(new SendLocation($location, $id));
        // $coll = Collector::find($id);
        // $coll->current_loc = (object)["lat"=>$lat, "long"=>$long];
        // $coll->save();
        // ->pluck('id', 'current-loc');
        return $this->successResponse($location, 200, true);
    }

    public function getAddressWithCoordinates(Request $request)
    {
        if (!$request->query()) {
            return $this->errorResponse('Latitude and longitude parameters are required.', 402);
        }

        $lat = $request->query('lat');
        $lng = $request->query('lng');

        // error_log($lat);
        // error_log($lng);

        $api_key = config('apikey.google');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL            => "https://maps.googleapis.com/maps/api/geocode/json?latlng={$lat},{$lng}&key={$api_key}",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => "",
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => "GET"
        ));

        $encodedResponse = curl_exec($curl);
        $res             = json_decode($encodedResponse);
        // error_log(json_encode($res));
        $address_without_country = 'Lagos, Nigeria.';
        if (array_key_exists(0, $res->results)) {
            $full_address            = $res->results[0]->formatted_address;
            $address_without_country = explode(', Nigeria', $full_address)[0];
        }
        return $this->successResponse($address_without_country, 200, true);
    }
}
