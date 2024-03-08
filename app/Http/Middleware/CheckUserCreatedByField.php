<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enum\RoleEnum;
use App\Models\User;

class CheckUserCreatedByField
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->user()->hasRole(RoleEnum::$ADMIN) || $request->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) || ($request->user()->hasRole(RoleEnum::$DEALER) && $this->CheckCompanyAttribute($request))) {
            return $next($request);
        }

        return redirect()->route('user.index')
            ->with('error', 'You are not authorized to access this page.');
    }

    function CheckCompanyAttribute($request)
    {
        if ($request->user != null && $request->user->company_id == auth()->user()->company_id) {
            return true;
        } else {
            return false;
        }
    }
}
