<?php

namespace App\Http\Controllers;

use App\LocationData;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Importer;

class LocationDataController extends Controller
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
       for($i = 0; $i < sizeof($collection); $i++)
       {
           try {
               $row = $collection[$i];
               if($row[0] != '')
               {
                   $locations->{$row[0]} = (object) [];
                   $this->lastlocation = $row[0];
               }else if($row[0] == '' && $row[1] != '')
               {
                   $locations->{$this->lastlocation}->{$row[1]} = (object)[];
                   $this->lastsublocation = $row[1];
               }else if($row[0] == ''  && $row[1] == '' && $row[2] != '')
               {
                    $sublocation = $locations->{$this->lastlocation}->{$this->lastsublocation};
                    $sublocation->{$this->in} = $row[2];
                    $this->in++;
                //    array_push($sublocation, 'abcs');
                    $i++;
               }
           } catch (\Exception $e) {
               $this->console_log($e->getMessage());
               return redirect()->back()->with([ 'errors' => $e->getMessage() ]);
           }
       }
       
       $locs = LocationData::find(1);
        if($locs)
        {
            $locs->locations = json_encode($locations);
            $locs->save();
        }else{
            $newlocs = new LocationData;
            $newlocs->locations = json_encode($locations);
            $newlocs->save();
        }

        return redirect()->back()->with([ 'success' => 'Location Data Uploaded Successfully.' ]);
        $this->console_log($locations);
   }

    public function getLocations()
    {
        $locations = LocationData::find(1)->first()->pluck('locations');
        return response()->json(json_decode($locations[0]), Response::HTTP_OK);
    }

   public function console_log($output, $with_script_tags = true) {
        $js_code = 'console.log(' . json_encode($output, JSON_HEX_TAG) . 
    ');';
        if ($with_script_tags) {
            $js_code = '<script>' . $js_code . '</script>';
        }
        echo $js_code;
    }
}
