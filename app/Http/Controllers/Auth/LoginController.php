<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\ApiController;
use App\Traits\ApiResponse;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends ApiController
{
    use ApiResponse;

    public function phone(Request $request)
    {
        $this->validate($request, [
            'phone'    => 'required',
            'password' => 'required'
        ]);

        $credentials = [
            'phone'    => '+234' . substr($request->phone, 1),
            'password' => $request->password
        ];

        $approval = $this->isCollectorApproved($request);

        if ($approval->approved == false) {
            return $this->errorResponse('You have not been approved as a collector, please contact Scrapays for further enquiries.', 401);
        }

        $user = $approval->user;

        if ($token = $this->guard()->attempt($credentials)) {
            $user->last_login = Carbon::now()->addHour();
            $user->save();
            return $this->respondWithToken($token);
        }

        return $this->errorResponse('Credentials do not match our records.', 401);

    }

    public function email(Request $request)
    {
        $this->validate($request, [
            'email'    => 'required|email',
            'password' => 'required'
        ]);
        $credentials = $request->only('email', 'password');
        $user        = User::where('email', $request->email)->first();

        if ($user->userable_type !== 'Admin' && $user->userable_type !== 'Enterprise') {
            return $this->errorResponse('You cannot login with your email, please login with your phone number instead.', 401);
        }

        if ($token = $this->guard()->attempt($credentials)) {
            $user->last_login = Carbon::now()->addHour();
            $user->save();
            return $this->respondWithToken($token);
        }

        return $this->errorResponse('Credentials do not match our records.', 401);
    }

    private function isCollectorApproved(Request $request)
    {
        $res = (object) [
            'approved' => false,
            'user'     => null
        ];

        $phone = '+234' . substr($request->phone, 1);
        if ($user = User::where('phone', $phone)->first()) {
            $res->user = $user;
            if ($user->userable_type == 'Collector') {
                if ($user->userable->approved_as_collector == false) {
                    return $res;
                }
            }
        }
        $res->approved = true;
        return $res;
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
        $user            = auth()->user();
        $user->api_token = $token;

        $user->save();

        return $this->successResponse([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => $this->guard()->factory()->getTTL() * 60,
            'user'         => auth()->user()
        ], 200, true);
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
