<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Auditor;

class AksesAuditor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !($user instanceof Auditor) || $user->role !== 'auditor') {
            return response()->json([
                'message' => 'Auditor access required'
            ], 403);
        }

        return $next($request);
    }
}
