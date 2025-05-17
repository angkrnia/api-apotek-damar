<?php

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $logData = [
            'method' => $request->getMethod(),
            'url' => $request->getPathInfo(),
            'user' => auth()->check() ? auth()->user()->fullname : 'Tamu',
            'ip_address' => $request->ip(),
            'body' => json_encode($request->all()),
            'created_at' => Carbon::now()->setTimezone('Asia/Jakarta')->toDateTimeString()
        ];

        if (env('APP_ENV') !== 'production') {
            Log::info('request', $logData);
        }
        DB::table('log_request')->insert($logData);
        return $next($request);
    }
}
