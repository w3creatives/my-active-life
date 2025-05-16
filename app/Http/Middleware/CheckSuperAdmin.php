<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$request->user()) {
            return redirect()->route('login');
        }

        // Check if user is a super admin
        if (!$request->user()->isSuperAdmin()) {
            // Redirect to home with an error message
            return redirect()->route('home')->with('error', 'You do not have permission to access this area.');
        }

        return $next($request);
    }
}
