<?php

namespace App\Http\Middleware;

use Closure;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $role = $request->user->role;
        if ($role != 'admin') {
            return response()->json([
                'code' => 6004,
                'message' => '无管理员权限'
            ]);
        } else {
            return $next($request);

        }
    }
}
