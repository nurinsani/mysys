<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogUserAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (!auth()->check() || str_starts_with($request->path(), 'telescope')) {
            return;
        }

        // CIF bisa datang dari parameter route ({cif} di URL), query string,
        // atau body request (GET/POST) — dicari di ketiganya supaya
        // "Perubahan"/deskripsi di menu Activity Log tetap menyebut CIF-nya
        // walau tidak muncul di path URL.
        $cif = $request->route('cif') ?? $request->input('cif');

        $properties = [
            'method' => $request->method(),
            'path' => '/' . ltrim($request->path(), '/'),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'session_id' => $request->session()->getId(),
        ];

        $description = $request->method() . ' ' . $request->path();

        if ($cif) {
            $properties['cif'] = $cif;
            $description .= ' (CIF: ' . $cif . ')';
        }

        activity('akses')
            ->causedBy(auth()->user())
            ->withProperties($properties)
            ->event('akses')
            ->log($description);
    }
}
