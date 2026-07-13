<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->role !== UserRole::Admin) {
            return response()->json([
                'message' => 'Acceso denegado. Se requiere rol de administrador.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
