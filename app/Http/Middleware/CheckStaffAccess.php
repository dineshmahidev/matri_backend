<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CheckStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->role === 'manager') {
            $allowed = Cache::remember('setting:manager_staff_access', 60, function () {
                return \App\Models\SiteSetting::where('key', 'manager_staff_access')->value('value');
            });
            if ($allowed !== '1' && $allowed !== 1 && $allowed !== true) {
                abort(403, 'Staff management is restricted for your role.');
            }
        }
        return $next($request);
    }
}
