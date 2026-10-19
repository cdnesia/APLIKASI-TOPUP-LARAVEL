<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Header keamanan dasar untuk semua respons */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->add([
            // Cegah situs dimuat di <iframe> situs lain (clickjacking)
            'X-Frame-Options' => 'SAMEORIGIN',
            // Browser tidak boleh menebak tipe file (mencegah file unggahan dijalankan sebagai skrip)
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ]);

        if ($request->isSecure()) {
            // Paksa HTTPS selama 1 tahun setelah kunjungan pertama
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
