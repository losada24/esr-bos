<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Order;
use App\Enum\RoleEnum;

class ValidateOrderOwnerById
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
      $order = $request->route('id');
      if (is_numeric($order)) {
        $order = Order::findOrFail($order);  
      }

      if (auth()->user()->hasRole(RoleEnum::$ADMIN ) || 
        ((auth()->user()->hasRole(RoleEnum::$CLIENT_ADMIN) || auth()->user()->hasRole(RoleEnum::$CLIENT)) && $order->company_id == auth()->user()->company_id)) {
          return $next($request);
      } else {
          return redirect()->route('estimate.index')
              ->with('error', 'You are not authorized to access this page.');
      }
    }
}
