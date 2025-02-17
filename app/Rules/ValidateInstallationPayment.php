<?php

namespace App\Rules;

use App\Models\Order;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\DataAwareRule;

class ValidateInstallationPayment implements DataAwareRule, ValidationRule
{
      public function __construct()
      {
        
      }

      protected $data = [];

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
    
  /** 
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Buscar la orden con sus pagos relacionados
        $order = Order::with('installationPayments', 'paymentExtraFields')->find($this->data['order_id']);
        //dd($order->paymentExtraFields->);
        if (!$order) {
            $fail('Order not found.');
            return;
        }

        $extra_work = $order->paymentExtraFields->extra_work ?? 0;
        $other_cost_installer = $order->paymentExtraFields->other_cost_installer ?? 0;
        $extra_discount = $order->paymentExtraFields->extra_discount ?? 0;
         
        // Obtener el total disponible (GetGrandTotalPrice + extra_work)
        $totalAvailable = ($order->GetGrandTotalPrice() + $extra_work + $other_cost_installer) - $extra_discount;

        // Sumar todos los pagos previos
        $totalPaid = $order->installationPayments->sum('installer_payment');

        // Verificar que el nuevo pago no supere el total permitido
        if (($totalPaid + $value) > $totalAvailable) {
            $fail('The installer payment exceeds the allowed total for this order.');
        }
    }
    
}

