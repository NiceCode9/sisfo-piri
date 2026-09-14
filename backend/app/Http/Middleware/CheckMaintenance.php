<?php

namespace App\Http\Middleware;

use App\Models\Pengaturan;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Pengaturan::nilai('maintenance_mode') === '1' && ! auth()->user()?->hasRole('super-admin')) {
            $pesan = Pengaturan::nilai('maintenance_pesan') ?: 'Sistem sedang dalam pemeliharaan. Coba lagi nanti.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $pesan], 503);
            }

            return response()->view('errors.maintenance', compact('pesan'), 503);
        }

        return $next($request);
    }
}
