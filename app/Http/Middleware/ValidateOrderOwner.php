<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Order;
use App\Enum\RoleEnum;

class ValidateOrderOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $order = $request->route('estimate');
        $order->load('user');
        
        if (auth()->user()->hasRole(RoleEnum::$ADMIN ) || 
          (auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN) && $order->user->isCreatedByLoggedUser()) || 
          ($order != null && $order->user_id == auth()->user()->id)) {
            return $next($request);
        } else {
            return redirect()->route('estimate.index')
                ->with('error', 'You are not authorized to access this page.');
        }
    }
}
