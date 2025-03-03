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
        //dd($order->installationPayments);
        if (!$order) {
            $fail('Order not found.');
            return;
        }
        // Verificar si el monto actual ya existe en los pagos (indica que se está editando)
       // Obtener el total disponible (GetGrandTotalPrice + extra_work)
        $totalAvailable = $order->GetGrandTotalPrice();

        // Sumar todos los pagos previos (todos los pagos existentes)
        $paymentId = $this->data['id'] ?? 0;
        if ($paymentId == 0) {
            $totalPaid = $order->installationPayments->sum('installer_payment');
        } else {
            $totalPaid = $order->installationPayments->where('id', '!=', $paymentId)->sum('installer_payment');
        }

        // Validar que la suma de pagos no supere el total disponible
        if (($totalPaid + $value) > $totalAvailable) {
            $fail('The installer payment exceeds the allowed total for this order.');
        }
          
    }
    
}

