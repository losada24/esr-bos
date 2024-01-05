<?php

namespace App\Http\Requests;

use App\Enum\PaymentMethodEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEstimateToOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'order_id' => 'required|exists:orders,id',
            'method' => [
              'required',
              'string',
              Rule::in(array_values(PaymentMethodEnum::$PAYMENT_METHOD))
            ],
            'phone_number' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:20']
              ),
            ],
            'street_address' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:255']
              ),
            ],
            'city' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:255']
              ),
            ],
            'state' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , [
                  'required',
                  'string',
                  'max:100',
                  Rule::in(array_values(PaymentMethodEnum::$PAYMENT_METHOD))
                ]
              ),
            ],
            'zip_code' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'numeric', 'max_digits:5', 'min_digits:5']
              ),
            ],
            'country' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:100']
              ),
            ],
            'first_name' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:255']
              ),
            ],
            'last_name' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'max:255']
              ),
            ],
            'email' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'email', 'max:255']
              ),
            ],
            'notes' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['nullable', 'max:500', 'string']
              ),
            ],
            'amount' => [
              Rule::when(
                fn($input) => $input->method == PaymentMethodEnum::$PAYMENT_METHOD['CREDIT'] || $input->amount >= config('custom.address_required_after_amount')
                , ['required', 'numeric']
              ),
            ]
        ];
    }
}
