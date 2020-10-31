<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponse;
use App\User;
use Closure;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckPermissions
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
            $token   = JWTAuth::getToken();
            $payload = $this->getPayload($token);
            $user    = User::find($payload['id']);

        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage(), 401);
        }

        $permissions = json_decode($user->userable->permissions);

        if (!$this->permitted($guards, $permissions)) {
            return $this->errorResponse('Not authorized to access this route.', 401);
        }

        return $next($request);
    }

    private function getPayload($token)
    {
        $payload = JWTAuth::getPayload($token)->toArray();
        return $payload;
    }

    private function permitted($guards, $permissions)
    {
        if ($permissions) {
            if (in_array('all', $permissions)) {
                return true;
            }
            foreach ($guards as $guard) {
                if (in_array($guard, $permissions)) {
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }
}
