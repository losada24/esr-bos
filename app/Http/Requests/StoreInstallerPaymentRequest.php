<?php

namespace App\Http\Requests;

use App\Rules\ValidateInstallationPayment;
use Illuminate\Foundation\Http\FormRequest;

class StoreInstallerPaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
          'order_id' => ['required', 'numeric', 'exists:orders,id'],
          'installation_team_id' => ['required', 'numeric', 'exists:users,id'],
          'installer_payment' => ['required', 'numeric', new ValidateInstallationPayment],
          'percentage_payment' => ['required', 'numeric'],
          'payment_date' => ['required', 'date'],
        ];
    }
}
