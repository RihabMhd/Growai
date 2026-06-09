<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role->value !== 'admin') {
            return response()->json(['message' => 'Non autorisé.'], 403);
        }

        return $next($request);
    }
}