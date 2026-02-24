<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('x-api-key');
        $validApiKey = env('RESEARCH_API_KEY');
        $environment = env('DEVELOPMENT', 'false');


        if (!$apiKey || $apiKey !== $validApiKey) {
            return response()->json(['message' => 'Bawal dito gegi ka.'], 401);
        }

        if (filter_var($environment, FILTER_VALIDATE_BOOLEAN)) {
            return response()->json(['message' => 'Production dito gegi ka.'], 401);
        }

        return $next($request);
    }
}
