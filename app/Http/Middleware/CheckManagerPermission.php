<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckManagerPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }

        // Admin always passes
        if ($user->role === 'admin') {
            return $next($request);
        }

        // "none" permission blocks all non-admin
        if ($permission === 'none') {
            abort(403, 'Access denied.');
        }

        // Only applies to manager role
        if ($user->role === 'manager') {
            $permissions = Cache::remember('manager_permissions', 60, function () {
                $raw = \App\Models\SiteSetting::where('key', 'manager_permissions')->value('value');
                return $raw ? json_decode($raw, true) : [];
            });

            if (isset($permissions[$permission]) && !$permissions[$permission]) {
                abort(403, "Access denied: '{$permission}' permission is required.");
            }
        }

        return $next($request);
    }
}
