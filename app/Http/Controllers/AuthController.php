<?php

namespace App\Http\Controllers;

use App\Agent;
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
use App\Notification;
use App\User;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Notifications;

class AuthController extends Controller
{
    // use Notifications;
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => [
            'loginWithPhone',
            'loginWithEmail',
            'registerwithussd',
            'registerEnterprise',
            'registerHousehold',
            'registerHost',
            'registerCollector',
            'registerAgent',
            'unauthenticated'
            ]]);
    }

    public function unauthenticated()
    {
        return response()->json(['error' => 'You are not authorised.'], Response::HTTP_UNAUTHORIZED);
    }

    public function registerEnterprise(SignUpRequest $request)
    {

        $user = new User;

        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $enterprise = new Enterprise;
        
        $enterprise->companyName = $request->companyName;
        $enterprise->companySize = $request->companySize;
        $enterprise->industry = $request->industry;
        $enterprise->sex = $request->sex;
        $enterprise->recoveryAutomated = false;
        $enterprise->save();

        $enterprise->user()->save($user);

        return $this->sendSignupMail($request, 'Enterprise');
    }

    public function registerwithussd(Request $request)
    {
        $this->validate($request, [
            'firstName' => 'required',
            'lastName' => 'required',
            'phone' => 'required|unique:users',
            'requestAddress' => 'required',
        ]);

        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = null;
        $user->password = '123456';
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $household = new Household;
        $household->requestAddress = $request->requestAddress;
        $household->save();

         $household->user()->save($user);

         return response()->json(['Success' => 'Household Account created successfully'], 200);
        // return $this->sendSignupMail($request);
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

        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $household = new Household;
        $household->requestAddress = $request->requestAddress;
        $household->save();

         $household->user()->save($user);

        return $this->sendSignupMail($request, 'Household');
    }

    public function registerHost(SignUpRequest $request)
    {

        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $host = new Host;
        $host->hostAddress = $request->hostAddress;
        $host->spaceSize = $request->spaceSize;
        $host->hostDuration = $request->hostDuration;
        $host->hostStartDate = $request->hostStartDate;
        $host->save();

         $host->user()->save($user);

        return $this->sendSignupMail($request, 'Host');
    }

    public function registerCollector(SignUpRequest $request)
    {

        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $collector = new Collector;
        $collector->collectionCoverageZone = $request->collectionCoverageZone;
        $collector->approvedAsCollector = false;
        $collector->save();

         $collector->user()->save($user);

        return $this->sendSignupMail($request, 'Collector');
    }

    public function registerAgent(SignUpRequest $request)
    {

        $user = new User;
        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = $request->password;
        $user->inviteCode = $request->inviteCode;
        $user->save();

        $agent = new Agent;
        $agent->collectorCoverageZone = $request->collectorCoverageZone;
        $agent->approvedAsCollector = $request->approvedAsCollector;
        $agent->save();

         $agent->user()->save($user);

        return $this->sendSignupMail($request,'Agent');
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
        $credentials = $request->only('phone', 'password');

        if ($token = $this->guard()->attempt($credentials)) {
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

  public function getUsers(Request $request, $id)
    {
        $user = User::find($id);
        if ($user->userable_type != 'App\Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };
        $users = User::all();
        return json_encode($users);
    }

    public function getUserWithToken(Request $request, $id)
    {
        $token = $request->token;
        $user = User::where('api_token', $token)->first();

        return json_encode($user);
    }

    public function getUserWithID( $id )
    {
        $user =  User::find($id);

        return response()->json($user, Response::HTTP_OK);
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
        if($user->recoveryAutomated == true){
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
            }
        }else{
            $inv = DB::table('inventories')->where('enterpriseID', $id);
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

    public function automatePickup( Request $request ) 
    {
        $user = User::find( $request->phone );
        $user->userable->recoveryAutomated = true;
        $user->push(); 

        return response()->json(['data' => 'Your recovery has been automated successfully'], Response::HTTP_CREATED);
    }

    public function unAutomatePickup( Request $request ) 
    {
        $user = User::find( $request->phone );
        $user->userable->recoveryAutomated = false;
        $user->push(); 

        return response()->json(['data' => 'You have successfully unautomated your pickup'], Response::HTTP_CREATED);
    }

    public function getUserName(Request $request)
    {
        $collector =  User::find( $request->collectorID );

        if ($collector->userable_type != 'App\Collector')
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }

        if( $user = User::find( $request->input( 'producerPhone' ) ) )
        {
            return response()->json([
                'Name' => $user->firstName . ' ' .$user->lastName,
                ], Response::HTTP_OK);
        }else if( $user = User::where('id', $request->input( 'producerPhone' ))->first() )
        {
            return response()->json([
                'Name' => $user->firstName . ' ' .$user->lastName,
                ], Response::HTTP_OK);
        }else 
        {
             return response()->json(['Name' => 'Not found'], 404);
        }
    }

    public function validateVendorID($id)
    {
        return !!User::find($id);
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