<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth()->check()) {
            return redirect()->route('admin.login')->with('error', 'Silakan login terlebih dahulu untuk mengakses Admin Control Center.');
        }

        if (! auth()->user()->isAdmin()) {
            abort(403, 'Akses ditolak. Anda tidak memiliki izin Administrator.');
        }

        return $next($request);
    }
}
