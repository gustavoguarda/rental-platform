<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Multi-tenant middleware that scopes all queries to the authenticated operator.
 *
 * In a multi-product platform, this ensures data isolation between operators
 * while sharing the same database and API infrastructure.
 *
 * The operator_id is extracted from the authenticated API token.
 * This pattern supports the Strangler Fig migration: legacy systems
 * can authenticate with API keys while new services use JWT.
 */
class OperatorScope
{
    public function handle(Request $request, Closure $next)
    {
        $operatorId = $request->user()?->operator_id
            ?? $request->header('X-Operator-Id');

        if (! $operatorId) {
            return response()->json(['error' => 'Operator context required.'], 403);
        }

        // Make operator_id available throughout the request lifecycle
        app()->instance('operator_id', (int) $operatorId);

        return $next($request);
    }
}
