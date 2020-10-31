<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use App\User;
use Closure;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckAdminStatus extends Middleware
{
    use ApiResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$guards)
    {
        $user = null;
        try {
            $token          = JWTAuth::getToken();
            $payload        = $this->getPayload($token);
            $user           = User::find($payload['id']);
            $request->admin = $user;

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }

        if ($user->userable_type != 'Admin') {
            return $this->errorResponse('Not authorized to access this route.', 401);
        }
        return $next($request);
    }

    private function getPayload($token)
    {
        $payload = JWTAuth::getPayload($token)->toArray();
        return $payload;
    }
}
