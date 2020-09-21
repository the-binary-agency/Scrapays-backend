<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class CheckAdminStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = DB::table('users')->where('api_token', $request->bearerToken());

        if ($user->userable_type  != 'App\Admin')
        {
            return route('unauthenticated');
        }
        return $next($request);
    }
}
