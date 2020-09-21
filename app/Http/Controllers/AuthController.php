<?php

namespace App\Http\Controllers;

use App\Admin;
use App\Agent;
use App\collectedScrap;
use App\Collector;
use App\Enterprise;
use App\Host;
use App\Household;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignUpRequest;
use App\Mail\registerHouseholdMail;
use App\Mail\registerMail;
use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Crypt;

class AuthController extends Controller
{
    // use Notifications;

    public function unauthenticated()
    {
        return response()->json(['error' => 'You are not authorised.'], Response::HTTP_UNAUTHORIZED);
    }

    public function saveUser($request)
    {
        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = '+234'.substr($request->phone, 1);
        $user->email = $request->email;
        $user->pin = Crypt::encryptString($request->pin);
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;

        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                'phone' => '+234'.substr($request->phone, 1),
                'pin' => $request->pin,
                'fullname' =>$request->firstName." ".$request->lastName,
            ],
        ];
        $fields_string = http_build_query($fields);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://apis.dcptap.website/w/public/v1/wallet/create",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            "token: Bearer ".$token,
            "publicKey: ".$publicKey
        ),
        ));

        $response = curl_exec($curl);
        $decoded = json_decode($response);
        $res = (object)[
            'user' => $user,
            'error' => ''
        ];
        if($decoded)
        {
            if($decoded->error->message)
            {
                $res->error = $decoded->error->message;
            }else{
                if($decoded->success->message)
                {
                    $user->save();
                }
            }
        }else
        {
            $res->error = 'A server error has occured, please try again.';
        }

        curl_close($curl);

        return $res;
    }

    public function registerAdmin(SignUpRequest $request)
    {
        $prevadmin = User::find($request->input('prevAdminPhone'));
        if( !$prevadmin || $prevadmin->userable_type != 'App\Admin'){
            return $this->unauthenticated();
        }

        $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json([$res->error], Response::HTTP_FORBIDDEN);
        }else{

            $admin = new Admin;
            $admin->save();

            $admin->user()->save($res->user);

        return response()->json(['data' => 'You have successfully created a new Admin'], Response::HTTP_CREATED);

        }
    }

    public function registerEnterprise(SignUpRequest $request)
    {
        $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json((object)['error' => $res->error], Response::HTTP_FORBIDDEN);
        }else{

            $enterprise = new Enterprise;
            
            $enterprise->companyName = $request->companyName;
            $enterprise->companySize = $request->companySize;
            $enterprise->industry = $request->industry;
            $enterprise->sex = $request->sex;
            $enterprise->recoveryAutomated = false;
            $enterprise->save();

            $enterprise->user()->save($res->user);

            return $this->sendSignupMail($request, 'Enterprise');

        }
    
    }

    public function registerwithussd(Request $request)
    {
        $this->validate($request, [
            'firstName' => 'required',
            'lastName' => 'required',
            'phone' => 'required|unique:users',
            'pin' => 'required',
        ]);

        $publicKey = env('WALLET_PUBLIC_KEY');
        $token = env('WALLET_TOKEN');
        $fields = [
            'data' => [
                'phone' => $request->phone,
                'pin' => $request->pin,
                'fullname' =>$request->firstName." ".$request->lastName,
            ],
        ];
        $fields_string = http_build_query($fields);
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => "https://apis.dcptap.website/w/public/v1/wallet/create",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "POST",
        CURLOPT_POSTFIELDS => $fields_string,
        CURLOPT_HTTPHEADER => array(
            "token: Bearer ".$token,
            "publicKey: ".$publicKey
        ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        $decoded = json_decode($response);

        if($decoded->error->message)
        {
            return response()->json($decoded->error->message, Response::HTTP_FORBIDDEN);
        }else{
            if($decoded->success->message)
            {
                $user = new User;
                $user->firstName = $request->firstName;
                $user->lastName = $request->lastName;
                $user->phone = $request->phone;
                $user->email = null;
                $user->password = '123456';
                $user->pin = Crypt::encryptString($request->pin);
                $user->inviteCode = null;
                $user->save();

                $household = new Household;
                $household->requestAddress = null;
                $household->save();

                $household->user()->save($user);

                return response()->json(['Success' => 'Household Account created successfully'], 200);
            }
        }

    }

    public function registerHousehold(Request $request)
    {
        $this->validate($request, [
            'firstName' => 'required',
            'lastName' => 'required',
            'phone' => 'required|unique:users',
            'email' => 'required|email|unique:users',
            'requestAddress' => 'required',
            'password' => 'required|confirmed' 
        ]);

         $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json((object)['error' => $res->error], Response::HTTP_FORBIDDEN);
        }else{

            $household = new Household;
            $household->requestAddress = $request->requestAddress;
            $household->save();

            $household->user()->save($res->user);

            return $this->sendSignupMail($request, 'Household');

        }

       
    }

    public function registerHost(SignUpRequest $request)
    {

         $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json((object)['error' => $res->error], Response::HTTP_FORBIDDEN);
        }else{

            $host = new Host;
            $host->hostAddress = $request->hostAddress;
            $host->spaceSize = $request->spaceSize;
            $host->hostDuration = $request->hostDuration;
            $host->hostStartDate = $request->hostStartDate;
            $host->save();

            $host->user()->save($res->user);

            return $this->sendSignupMail($request, 'Host');

        }


    }

    public function registerCollector(SignUpRequest $request)
    {

       $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json((object)['error' => $res->error], Response::HTTP_FORBIDDEN);
        }else{

            $collector = new Collector;
            $collector->collectionCoverageZone = $request->collectionCoverageZone;
            $collector->approvedAsCollector = false;
            $collector->save();

            $collector->user()->save($res->user);

            return $this->sendSignupMail($request, 'Collector');

        }
    }

    public function registerAgent(SignUpRequest $request)
    {

        $res = $this->saveUser($request);

        if($res->error)
        {
            return response()->json((object)['error' => $res->error], Response::HTTP_FORBIDDEN);
        }else{

            $agent = new Agent;
            $agent->collectorCoverageZone = $request->collectorCoverageZone;
            $agent->approvedAsCollector = $request->approvedAsCollector;
            $agent->save();

            $agent->user()->save($res->user);

            return $this->sendSignupMail($request,'Agent');

        }
    }

    /**
     * Get a JWT token via given credentials.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function loginWithPhone(Request $request)
    {
        $credentials = [
            'phone' => '+234'.substr($request->phone, 1),
            'password' => $request->password
        ];

       if($collector = User::find('+234'.substr($request->phone, 1)))
       {
           if($collector->userable_type == 'App\Collector')
           {
               if($collector->userable->approvedAsCollector == false)
               {
                   return response()->json(['error' => 'You have not been approved as a collector, please contact Scrapays for further enquiries.'],  Response::HTTP_UNAUTHORIZED);
               }
           }
       }
        if ($token = $this->guard()->attempt($credentials)) {
            $collector->lastLogin = Carbon::now()->addHour();
            $collector->save();
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Credentials do not match our records.'], 401);
    }


    public function loginWithEmail(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email',
            'password' => 'required',
        ]);
        $credentials = $request->only('email', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
            $u = DB::table('users')->where('email', $request->email)->first();
            $user = User::find($u->phone);
            $user->lastLogin = Carbon::now()->addHour();
            $user->save();
            return $this->respondWithToken($token);
        }

        return response()->json(['error' => 'Credentials do not match our records.'], 401);
    }

    public function sendSignupMail( $user, $type ) 
    {
        if($type == 'Enterprise')
        {
            Mail::to($user->email)->send(new registerMail($user));
        } else if($type == 'Household')
        {
            Mail::to($user->email)->send(new registerHouseholdMail($user));
        }
        // else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }else if($type == 'Household')
        // {
        //     Mail::to($user->email)->send(new registerHouseholdMail($user));
        // }

        return response()->json(['data' => 'Your account has been created successfully. Please check your email for your details'], Response::HTTP_CREATED);
    }

  public function getUsers(Request $request)
    {
        $user = User::find($request->id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };
        if($request->userType)
        {
            $users = User::whereHasMorph(
                'userable',
                ['App\\'.$request->userType]
              
            )->latest()->get();
        }else
        {
             $users = DB::table('users')->get()->latest();
        }
        return json_encode($users);
    }

    public function getUserWithToken(Request $request, $id)
    {
        $token = $request->token;
        $user = User::where('api_token', $token)->first();

        return json_encode($user);
    }

    public function getUserWithID( Request $request )
    {
         $admin = User::find($request->adminPhone);
        if ($admin->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };
         $user = User::find($request->userID);
        //  $user = User::whereHasMorph(
        //         'userable',
        //         ['App\\'.$request->userType],
        //         function (Builder $query) use($request) {
        //             $query->where('id', $request->userID);
        //         }
        //     )->get();
        //     error_log($user);

        return response()->json($user, Response::HTTP_OK);
    }

    public function getUserDetails( $phone )
    {
         $user = User::find($phone);

        return response()->json($user, Response::HTTP_OK);
    }

    public function toggleCollectorStatus(Request $request)
    {
        if($admin = User::find($request->adminPhone))
        {
            if ($admin->userable_type != 'App\Admin') 
            {
                return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
            };
            $collector = User::find($request->collectorPhone);
            $collector->userable->update($request->all());
            return response()->json(['success' => 'Collector status was successfully toggled.'], Response::HTTP_OK);
        }
    }

    public function deleteUser(Request $request)
    {
        if($admin = User::find($request->adminPhone))
        {
            if ($admin->userable_type != 'App\Admin') 
            {
                return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
            };
            $userToBeDeleted = User::find($request->deletePhone);
            $userToBeDeleted->delete();
            return response()->json(['success' => 'User has been deleted successfully.'], Response::HTTP_OK);
        }
    }

    public function getUserWithTonnage( $id )
    {
        if ($user = User::find($id)) {
            $tonnage = $this->getProducedTonnage($user);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonnage
            ]);
        }
    }

    public function getCollectorWithTonnage( $id )
    {
        if ($user = User::find($id)) {
            $tonnage = $this->getTonnage($id);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonnage
            ]);
        }
    }

    public function getUserWithNotifications( $id )
    {
        if ($user = User::find($id)) {
            $notifications = DB::table('notifications')->where('user_id', $id)->get();
            return response()->json([
            'user' => $user,
            'notifications' =>  $notifications
            ]);
        }
    }

    public function getProducedTonnage($user)
    {
        $id = $user->id;
        if($user->userable->recoveryAutomated == true){
            $scrap = array();
            if ($totaltonnage = DB::table('collected_scraps')->where('producerPhone', $id)->pluck('materials')) {
                foreach ($totaltonnage as $tt) {
                    foreach(json_decode($tt) as $mat){
                            array_push($scrap, $mat);
                    }
                }

                $holder = (object) [];

                array_filter($scrap, function ($d) use ($holder) {
                    if (property_exists($holder, $d->name)) {
                        $holder->{$d->name} = $holder->{$d->name} + $d->weight;
                    } else {
                        $holder->{$d->name} = $d->weight;
                    }
                });

                $obj2 = array();

                foreach($holder as $prop => $value) {
                    array_push($obj2, (object)[
                        'name' => $prop,
                        'weight' => $holder->{$prop}
                        ]);
                }

                return $obj2;
            }else
            {
                $totaltonnage = DB::table('collected_scraps')->where('producerPhone', $user->phone)->pluck('materials');
                foreach ($totaltonnage as $tt) {
                    foreach(json_decode($tt) as $mat){
                            array_push($scrap, $mat);
                    }
                }

                $holder = (object) [];

                array_filter($scrap, function ($d) use ($holder) {
                    if (property_exists($holder, $d->name)) {
                        $holder->{$d->name} = $holder->{$d->name} + $d->weight;
                    } else {
                        $holder->{$d->name} = $d->weight;
                    }
                });

                $obj2 = array();

                foreach($holder as $prop => $value) {
                    array_push($obj2, (object)[
                        'name' => $prop,
                        'weight' => $holder->{$prop}
                        ]);
                }

                return $obj2;
            }
        }else{
            $inventories = DB::table('inventories')->where('enterpriseID', $user->phone)->get();
            if(count($inventories) > 0){
                error_log('should not get heere');
                $holder = (object) [];

                foreach($inventories as $inventory){
                    if (property_exists($holder, $inventory->material)) {
                        $holder->{$inventory->material} = $holder->{$inventory->material} + $inventory->volume;
                    } else {
                        $holder->{$inventory->material} = $inventory->volume;
                    }
                }

                $obj2 = array();

                foreach($holder as $prop => $value) {
                    array_push($obj2, (object)[
                        'name' => $prop,
                        'weight' => (int)$holder->{$prop}
                        ]);
                }

                return $obj2;
            }else
            {
                $totaltonnage = DB::table('collected_scraps')->where('producerPhone', '0'.explode('+234', $user->phone)[1])->pluck('materials');
                $scrap = array();
                foreach ($totaltonnage as $tt) {
                    foreach(json_decode($tt) as $mat){
                            array_push($scrap, $mat);
                    }
                }

                $holder = (object) [];

                array_filter($scrap, function ($d) use ($holder) {
                    if (property_exists($holder, $d->name)) {
                        $holder->{$d->name} = $holder->{$d->name} + $d->weight;
                    } else {
                        $holder->{$d->name} = $d->weight;
                    }
                });

                $obj2 = array();

                foreach($holder as $prop => $value) {
                    array_push($obj2, (object)[
                        'name' => $prop,
                        'weight' => $holder->{$prop}
                        ]);
                }

                return $obj2;
            }
            
        }
    }

    public function getTonnage($id)
    {
        $scrap = array();
        if ($totaltonnage = DB::table('collected_scraps')->where('collectorID', $id)->pluck('materials')) {

            foreach ($totaltonnage as $tt) {
                foreach(json_decode($tt) as $mat){
                        array_push($scrap, $mat);
                }
            }

             $holder = (object) [];

            array_filter($scrap, function ($d) use ($holder) {
                if (property_exists($holder, $d->name)) {
                    $holder->{$d->name} = $holder->{$d->name} + $d->weight;
                } else {
                    $holder->{$d->name} = $d->weight;
                }
            });

             $obj2 = array();

            foreach($holder as $prop => $value) {
                array_push($obj2, (object)[
                    'name' => $prop,
                    'weight' => $holder->{$prop}
                    ]);
            }

            return $obj2;
        }
    }

    public function getDisposedTonnage(Request $request, $id)
    {
        if ($totaltonnage = DB::table('collected_scraps')->where('collectorID', $id)->get()) {
            return $totaltonnage;
        }
    }

    public function getUserCount( $id )
    {
        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

       $producers = User::where('userable_type', 'App\Enterprise')->count() + User::where('userable_type', 'App\Household')->count();
       $vendors = User::where('userable_type', 'App\Vendor')->count();
       $collectors = User::where('userable_type', 'App\Collector')->count();

       return response()->json([
            'producers' => $producers,
            'vendors' =>  $vendors,
            'collectors' =>  $collectors
            ]);
    }

    public function updateUser(Request $request, $id)
    {
        $this->validate($request, [
            'firstName' => 'required|string',
            'lastName' => 'required|string',
            'email' => 'required|email',
        ]);
        
        $user = User::find($id);
    
        if( $request->file('avatarImage') ){

            if($user->avatarImage)
            {
                Storage::delete('public/profile_pictures/'.$user->avatarImage);
            }
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatarImage')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);
            // get file extension
            $fileExt = $request->file('avatarImage')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName ."_" . time() .".".$fileExt;

            $user->avatarImage = $fileNameToStore;

            $request->file('avatarImage')->storeAs('public/profile_pictures', $fileNameToStore);

        }

        $user->firstName = $request->input('firstName');
        $user->lastName = $request->input('lastName');
        $user->email = $request->input('email');

        $user->save(); 

        $user->userable->update($request->all());

        return response()->json(['Profile Updated Successfully.'], Response::HTTP_CREATED);

    }

    public function updateUssdUser(Request $request, $id)
    {
        $this->validate($request, [
            'requestAddress' => 'required|string',
        ]);
        
        $user = User::find($id);

        $user->userable->update($request->all());

        return response()->json(['User profile updated Successfully.'], Response::HTTP_CREATED);

    }

    public function automatePickup( Request $request ) 
    {
        $user = User::find( $request->phone );
        if($user->userable->adminAutomated == null)
        {
            $user->userable->recoveryAutomated = true;
            $user->push(); 
            return response()->json(['data' => 'Your recovery has been automated successfully'], Response::HTTP_CREATED);
        }else{
            return response()->json(['error' => 'Your recovery automation has been explicitly set. Contact Scrapays for more info.'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function unAutomatePickup( Request $request ) 
    {
        $user = User::find( $request->phone );
         if($user->userable->adminAutomated == null)
        {
            $user->userable->recoveryAutomated = false;
            $user->push(); 
            return response()->json(['data' => 'You have successfully unautomated your pickup'], Response::HTTP_CREATED);
        }else
        {
            return response()->json(['error' => 'Your recovery automation has been explicitly set. Contact Scrapays for more info.'], Response::HTTP_UNAUTHORIZED);
        }
    }

    public function getUserName(Request $request)
    {
        $collector =  User::find( $request->collectorID );

        if ($collector->userable_type != 'App\Collector')
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }

        if( $user = User::find(  '+234'.substr($request->producerPhone, 1) ) )
        {
            $pickup = DB::table('pickup_requests')->where('userID', $user->phone)->first();
           if ($user->userable_type == 'App\Enterprise' || $user->userable_type == 'App\Household') {
               if($pickup)
               {
                    return response()->json([
                    'Name' => $user->firstName . ' ' .$user->lastName,
                    'pickupID' => $pickup->id
                    ], Response::HTTP_OK);
               }else{
                    return response()->json([
                    'Name' => $user->firstName . ' ' .$user->lastName,
                    'pickupID' => null
                    ], Response::HTTP_OK);
               }
           }else{
               return response()->json(['Name' => 'Not found'], 404);
           }
        }else if( $user = User::where('id', $request->producerPhone)->first() )
        {
            $pickup = DB::table('pickup_requests')->where('userID', $user->phone)->first();
            if ($user->userable_type == 'App\Enterprise' || $user->userable_type == 'App\Household') {
               if($pickup)
               {
                    return response()->json([
                    'Name' => $user->firstName . ' ' .$user->lastName,
                    'pickupID' => $pickup->id
                    ], Response::HTTP_OK);
               }else{
                    return response()->json([
                    'Name' => $user->firstName . ' ' .$user->lastName,
                    'pickupID' => null
                    ], Response::HTTP_OK);
               }
           }else{
               return response()->json(['Name' => 'Not found'], 404);
           }
        }else 
        {
             return response()->json(['Name' => 'Not found'], 404);
        }
    }

    public function validateVendorID($id)
    {
        return !!User::find($id);
    }

     public function getMaterialsSalesHistory($phone){
        $user = User::find($phone);
        if(!$user || $user->userable_type != 'App\Enterprise' || $user->userable_type != 'App\Household')
        {
            $this->unauthenticated();
        }

        $scrap = collectedScrap::where('producerPhone', $phone);
        return response()->json([
                'history' => $scrap
            ], Response::HTTP_OK);
    }

    public function addRoleToToken()
    {
        return auth()->user();
    }

    public function validateToken($token)
    {
        
        if ( $user = User::where('api_token', $token)->first() )
        {
            return true;
        }

        return false;
    }
    
    public function payload( $token )
    {
        $payload = JWTAuth::getPayload($token)->toArray();
        return  $payload;
    }


    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json($this->guard()->user());
    }

    /**
     * Log the user out (Invalidate the token)
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->guard()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken($this->guard()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        $user = auth()->user();
        $user->api_token = $token;

        $user->save();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $this->guard()->factory()->getTTL() * 60,
            'User' => auth()->user()
        ]);
    }

    /**
     * Get the guard to be used during authentication.
     *
     * @return \Illuminate\Contracts\Auth\Guard
     */
    public function guard()
    {
        return Auth::guard();
    }
}