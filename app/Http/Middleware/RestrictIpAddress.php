<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictIpAddress
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $allowedIpsString = config('app.allowed_ips');

        // If no IPs are defined, we might want to fail open or closed. 
        // Failing closed (deny all) is safer for a restriction middleware.
        if (empty($allowedIpsString)) {
             // Optional: Allow local if nothing is set, to prevent locking yourself out dev side
             // return $next($request);
             // For now, let's allow localhost always to prevent accidents
        }

        $allowedIps = array_map('trim', explode(',', $allowedIpsString));
        
        // Always allow localhost
        $allowedIps[] = '127.0.0.1';
        $allowedIps[] = '::1';

        foreach ($allowedIps as $allowedIp) {
            if (\Illuminate\Support\Str::is($allowedIp, $request->ip())) {
                return $next($request);
            }
        }

        abort(403, 'Unauthorized access from your IP address.');
    }
}
