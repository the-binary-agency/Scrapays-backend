<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\SignUpRequest;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Config;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['loginWithPhone', 'loginWithEmail', 'signup', 'getUsers', 'getUserWithToken', 'getProducerWithToken','getCollectorWithToken', 'getVendorWithToken', 'getDisposedTonnage', 'updateUser', 'getUserWithID', 'registerVendor',
        'approveCollector', 'getApprovedCollectors']]);
    }

  public function getUsers()
    {
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

        return $this->loginWithPhone($request);
    }

    public function registerVendor(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };

        $id = $request->id;
        $vendorID = $request->vendorID;
        if (!$this->validateVendorID( $vendorID ) )
         {
           return response()->json([
            'error' => 'There is no Vendor with the supplied ID'
        ], Response::HTTP_NOT_FOUND);
        };

         $user = User::find($id);
         $user->vendorID = $vendorID;
         $user->vendorApproved = 'false';
         $user->save(); 

        return response()->json(['data' => 'Vendor Registered Successfully. Pending Vendor Approval'], Response::HTTP_CREATED);
    }

    public function getApprovedCollectors(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };

        $id = $request->id;
        $user = User::where('vendorID', $id)->get();

        $users = array();
        foreach ($user as $us) {
           $usertobesent = (object) [
                'collectorName' => $us->firstName,
                'collectorID' => $us->id,
                'vendorApproved' => $us->vendorApproved
            ];
            
            array_push($users, $usertobesent);
        }

           return json_encode($users);

    }

    public function approveCollector(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };

        $id = $request->collectorID;
        $user = User::find($id);
        
        $user->vendorApproved = 'true';
        $user->save();

        return response()->json(['data' => 'Collector Approved.'], Response::HTTP_CREATED);

    }

    public function validateVendorID($id)
    {
        return !!User::where('id', $id)->first();
    }

    public function updateUser(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };
        $form = $request->form;

        return json_encode($form->id);
        $user = User::find($form->id);

        $user->firstName = $form->firstName; 
        $user->lastName = $form->lastName; 
        $user->phone = $form->phone; 
        $user->email = $form->email;

        $user->save(); 

        return response()->json(['data' => 'Profile Updated Successfully.'], Response::HTTP_CREATED);
    }

    public function addRoleToToken()
    {
        return auth()->user();
    }

    public function validateApiKey($apikey)
    {
        if ( $apikey != config('apikey.apikey') )
        {
            return false;
        }

        return true;
    }

    public function getUserWithToken(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };
        $token = $request->token;
        $user = User::where('remember_token', $token)->first();

        return json_encode($user);
    }

    public function getUserWithID(Request $request)
    {
        $apikey = $request->apikey;
        if (!$this->validateApiKey( $apikey ) )
         {
           return response()->json(['error' => 'Api key Invalid or missing'], Response::HTTP_UNPROCESSABLE_ENTITY);
        };
        $id = $request->id;
        $user =  User::find($id);

        return json_encode($user);
    }

    public function getCollectorWithToken(Request $request)
    {
        
        $token = $request->input( 'token' );
        if ($user = DB::table('users')->where('remember_token', $token)->first()) {
            $tonage = $this->getDisposedTonnage($user->id);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonage
            ]);
        }
    }

    public function getProducerWithToken(Request $request)
    {
        
        $token = $request->input( 'token' );
        if ($user = DB::table('users')->where('remember_token', $token)->first()) {
            $tonage = $this->getProducedTonnage($user->id);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonage
            ]);
        }
    }

    public function getVendorWithToken(Request $request)
    {
        
        $token = $request->input( 'token' );
        if ($user = DB::table('users')->where('remember_token', $token)->first()) {
            $tonage = $this->getDisposedTonnage($user->id);
            return response()->json([
            'user' => $user,
            'tonnage' =>  $tonage
            ]);
        }
    }

    public function getProducedTonnage($id)
    {
        if ($totaltonnage = DB::table('collected_scraps')->where('producerID', $id)->get()) {
            return $totaltonnage;
        }
    }
    public function getDisposedTonnage($id)
    {
        if ($totaltonnage = DB::table('collected_scraps')->where('collectorID', $id)->get()) {
            return $totaltonnage;
        }
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