<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Order;
use App\Enum\RoleEnum;
use App\Models\Product;

class ValidateEstimateOwner
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $routeParam): Response
    {
        $order = $request->route($routeParam);
        if (is_numeric($order)) {
          $order = Order::findOrFail($order);  
        } else if (is_object($order) && get_class($order) == Product::class) {
          $order = Order::findOrFail($order->order_id);
        }

        if ((auth()->user()->hasRole(RoleEnum::$ADMIN) || auth()->user()->hasRole(RoleEnum::$ACCOUNT_MANAGER)) || 
          (auth()->user()->hasRole(RoleEnum::$DEALER) && $order->company_id == auth()->user()->company_id) ||
          (auth()->user()->hasRole(RoleEnum::$SUB_DEALER) && $order->user_id == auth()->user()->id)) {
            return $next($request);
        } else {
            return redirect()->route('estimate.index')
                ->with('error', 'You are not authorized to access this page. This estimate is not created by you.');
        }
    }
}
