<?php

namespace App\Http\Middleware;

use App\Enums\StaffRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffUser
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->hasAnyRole([
            StaffRole::SuperAdmin->value,
            StaffRole::Admin->value,
            StaffRole::Production->value,
        ])) {
            return response()->json([
                'message' => 'Staff access required.',
            ], 403);
        }

        return $next($request);
    }
}
