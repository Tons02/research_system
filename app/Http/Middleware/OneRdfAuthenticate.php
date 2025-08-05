<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OneRdfAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        $apiKey = $request->header('api-key');
        $hasValidApiKey = $apiKey === env('ONE_CHARGING_ONE_RDF_SYNC');

        if (!$hasValidApiKey) {
            return response()->json([
                'message' => 'Unauthorized. Must provide a valid API key or be authenticated.'
            ], 401);
        }

        return $next($request);
    }
}
