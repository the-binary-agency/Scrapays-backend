<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignUpRequest;
use App\Mail\registerMail;
use App\materialPrices;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['loginWithPhone', 'loginWithEmail', 'signup', 'unauthenticated']]);
    }

    public function unauthenticated()
    {
        return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
    }

  public function getUsers(Request $request, $id)
    {
        $user = User::find($id);
        if ($user->role != 'Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };
        $users = User::all();
        return json_encode($users);
    }

//   public function getUsers()
//     {
//         $users = DB::table('users')->where([
//                                                     ['role', 'Producer'],
//                                                     ['role', 'Vendor'],
//                                                     ['role', 'Collector'],
//                                                     ])->get();
//         return json_encode($users);
//     }

//   public function getAdmins()
//     {
//         $admins = User::all();
//         // DB::table('users')->where('role', 'Admin')->get();
//         return json_encode($admins);
//     }

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

    public function signup(SignUpRequest $request)
    {

        User::create($request->all());

        return $this->sendMail($request);
    }

    public function sendMail( $user ) 
    {
        Mail::to($user->email)->send(new registerMail($user));

        return response()->json(['data' => 'Your account has been created successfully. Please check your email for your details'], Response::HTTP_CREATED);
    }

    public function automatePickup( Request $request ) 
    {
        $user = User::find( $request->phone );
        $user->recoveryAutomated = true;
        $user->save(); 

        return response()->json(['data' => 'Your recovery has been automated successfully'], Response::HTTP_CREATED);
    }

    // public function registerVendor(Request $request)
    // {

    //     $id = $request->id;
    //     $vendorID = $request->vendorID;
    //     if (!$this->validateVendorID( $vendorID ) )
    //      {
    //        return response()->json([
    //         'error' => 'There is no Vendor with the supplied ID'
    //     ], Response::HTTP_NOT_FOUND);
    //     };

    //      $user = User::find($id);
    //      $user->vendorID = $vendorID;
    //      $user->vendorApproved = 'false';
    //      $user->save(); 

    //     return response()->json(['data' => 'Vendor Registered Successfully. Pending Vendor Approval'], Response::HTTP_CREATED);
    // }

    // public function getApprovedCollectors(Request $request)
    // {

    //     $id = $request->id;
    //     $user = User::where('vendorID', $id)->get();

    //     $users = array();
    //     foreach ($user as $us) {
    //        $usertobesent = (object) [
    //             'collectorName' => $us->firstName,
    //             'collectorID' => $us->id,
    //             'vendorApproved' => $us->vendorApproved
    //         ];
            
    //         array_push($users, $usertobesent);
    //     }

    //        return json_encode($users);

    // }

    // public function approveCollector(Request $request)
    // {

    //     $id = $request->collectorID;
    //     $user = User::find($id);
        
    //     $user->vendorApproved = 'true';
    //     $user->save();

    //     return response()->json(['data' => 'Collector Approved.'], Response::HTTP_CREATED);

    // }

    public function validateVendorID($id)
    {
        return !!User::find($id);
    }

    public function updateUser(Request $request, $id)
    {
        $this->validate($request, [
            'avatarImage' => 'file|max:2048'
        ]);
        
        $user = User::where('id', $id)->first();

        if( $request->avatarImage ){
            // get the File Name and Extension
            $fileNameWithExt = $request->file('avatarImage')->getClientOriginalName();
            // let get only the file name
            $fileName = pathinfo($fileNameWithExt, PATHINFO_FILENAME);  // Php function
            // get file extension
            $fileExt = $request->file('avatarImage')->getClientOriginalExtension();
            // rename the file
            $fileNameToStore = $fileName ."_" . time() .".".$fileExt;

            $path = $request->file('avatarImage')->storeAs('public/profile_pictures', $fileNameToStore);

            Storage::delete('public/Uploaded_Videos/'.$user->avatarImage);
        }

        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->address = $request->address;
        $user->companyName = $request->companyName;
        $user->companySize = $request->companySize;
        $user->industry = $request->industry;
        $user->sex = $request->sex;
        $user->requestAddress = $request->requestAddress;
        $user->hostAddress = $request->hostAddress;
        $user->hostDuration = $request->hostDuration;
        $user->spaceSize = $request->spaceSize;
        $user->hostStartDate = $request->hostStartDate;
        $user->collectionCoverageZone = $request->collectionCoverageZone;

        $user->save(); 

        return response()->json(['data' => 'Profile Updated Successfully.'], Response::HTTP_CREATED);
    }

    public function addRoleToToken()
    {
        return auth()->user();
    }

    public function validateToken($token)
    {
        
        if ( $user = User::where('remember_token', $token)->first() )
        {
            return true;
        }

        return false;
    }

    public function getUserWithToken(Request $request, $id)
    {
        $token = $request->token;
        $user = User::where('remember_token', $token)->first();

        return json_encode($user);
    }

    public function getUserWithID( $id )
    {
        $user =  User::find($id);

        return json_encode($user);
    }

    // public function getCollectorWithTonnage(Request $request, $id)
    // {
    //     $collectorToken = $request->input( 'token' );
    //     if ($user = DB::table('users')->where('remember_token', $collectorToken)->first()) {
    //         $tonnage = $this->getTonnage($user->id);
    //         return response()->json([
    //         'user' => $user,
    //         'tonnage' =>  $tonnage
    //         ]);
    //     }
    // }

    public function getUserWithTonnage( $id )
    {
        if ($user = User::find($id)) {
            $tonnage = $this->getProducedTonnage($user->id);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonnage
            ]);
        }
    }

    // public function getVendorWithTonnage(Request $request, $id)
    // {
    //     $vendorToken = $request->input( 'token' );
    //     if ($user = DB::table('users')->where('remember_token', $vendorToken)->first()) {
    //         $tonnage = $this->getTonnage($user->id);
    //         return response()->json([
    //         'user' => $user,
    //         'tonnage' =>  $tonnage
    //         ]);
    //     }
    // }

    public function getProducedTonnage($id)
    {
        if ($totaltonnage = DB::table('collected_scraps')->where('producerID', $id)->get()) {
            return $totaltonnage;
        }
    }
    public function getTonnage($id)
    {
        if ($totaltonnage = DB::table('collected_scraps')->where('collectorID', $id)->get()) {
            return $totaltonnage;
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
        if ($user->role != 'Admin') 
        {
            return response()->json(['error' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        };

       $producers = User::where('role', 'producer')->count();
       $vendors = User::where('role', 'vendor')->count();
       $collectors = User::where('role', 'collector')->count();

       return response()->json([
            'producers' => $producers,
            'vendors' =>  $vendors,
            'collectors' =>  $collectors
            ]);
    }
    
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
        if ($user->role != 'Admin') 
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
            $material = new materialPrices;
            
            $material->name = $price->name;
            $material->price = $price->price;
            $material->image = $price->image;
            $material->save();
        }

        return response()->json(['message' => 'Material Prices Updated Successfully.'], Response::HTTP_CREATED);
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
        $user->remember_token = $token;

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