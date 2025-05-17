<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsPharmacist
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->role === PHARMACIST) {
            return $next($request);
        }

        return response()->json([
            'code'      => 403,
            'status'    => false,
            'message'   => 'You do not have permission to access this route.'
        ], 403);
    }
}
