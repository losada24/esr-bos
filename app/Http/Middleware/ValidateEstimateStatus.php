<?php

namespace App\Http\Middleware;

use App\Models\Order;
use App\Models\Product;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateEstimateStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $status, string $routeParam): Response
    {
        $status = explode('|', $status);
        $estimate = $request->route($routeParam);
        if (is_numeric($estimate)) {
          $estimate = Order::findOrFail($estimate);  
        } else if (is_object($estimate) && get_class($estimate) == Product::class) {
          $estimate = Order::findOrFail($estimate->order_id);
        }
        
        if (!in_array($estimate->status, $status)) {
            return redirect()->route('estimate.index')
                ->with('error', 'You are not authorized to access this page. This estimate is not in the correct status.');
        }
        
        return $next($request);
    }
}
