<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enum\FrameColorEnum;
use App\Enum\ServiceEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\PlaningDateSupervisorEnum;
use App\Enum\SupervisorPaymentStatusEnum;
use App\Enum\TypeOfFinancing;
use App\Rules\ValidateOrderStatus;
use Illuminate\Validation\Rule;

class PartialOrderRequest extends FormRequest
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
         
          'installation_teams' => 'nullable|array',
          'installation_teams.*' => 'required|integer|exists:installation_teams,id',
          'supervisor_id' => 'nullable|integer|exists:users,id',
          'initial_payment_percentage' => 'nullable|numeric',
          'payment_definition' => 'boolean',
          'status' =>  [
            'required',
            'string',
            Rule::in(
              OrderStatusEnum::PLANNED->value,
              OrderStatusEnum::CONFIRMED->value,
              OrderStatusEnum::EXECUTION->value,
              OrderStatusEnum::SUPERVISION->value,
              OrderStatusEnum::INSPECTION->value,
              OrderStatusEnum::FINISH->value,
              OrderStatusEnum::FINAL_INSPECTION->value,
              OrderStatusEnum::FINAL_COLLECT->value,
              OrderStatusEnum::ON_HOLD->value,
              OrderStatusEnum::DELIVERY_CONFIRMED->value,
              OrderStatusEnum::COMPLETE->value,
              OrderStatusEnum::RESCHEDULE->value,
            ),
          ],
            'supervisor_payment_status' => [
              'nullable',
              'string',
              Rule::in(
                SupervisorPaymentStatusEnum::OPEN->value,
                SupervisorPaymentStatusEnum::PENDING->value,
                SupervisorPaymentStatusEnum::NO_PAID->value,
                SupervisorPaymentStatusEnum::CLOSED->value,
              )
            ],
            'execution_planing_date' => [
              'nullable',
              'numeric',
              Rule::in(
                PlaningDateSupervisorEnum::PROJECTS_WITHOUT_PERMISSIONS->value,
                PlaningDateSupervisorEnum::PROJECTS_WITH_PERMISSIONS->value,
                PlaningDateSupervisorEnum::COMMERCIAL_PROJECTS->value,
              )
            ],
       
            'inspection_date' => [
              'nullable',
              Rule::when(
                fn($input) => $input['status']== OrderStatusEnum::INSPECTION->value
                , ['required', 'date_format:Y-m-d',]
              ),
            ],
          'contract_signing_date' => 'required|date_format:Y-m-d',
          'payment_factory_date' => 'required|date_format:Y-m-d',
          'eta_date' => 'required|date_format:Y-m-d',
          'installation_end_date' => 'nullable|date_format:Y-m-d',
          'delivery_date' => 'nullable|date_format:Y-m-d',
          'entry_date' => 'required|date_format:Y-m-d',
          'installation_date' => 'nullable|date_format:Y-m-d',
          'notes' => 'nullable|string|max:1000',
          'work_team_notes' => 'nullable|string|max:1000',
          'supervisor_commissions' => 'nullable|numeric',
          'supervisor_payment_percentage' => 'nullable|numeric',
          'supervisor_payment_date' => 'nullable|date_format:Y-m-d',
          'finish_date' => 'nullable|date_format:Y-m-d',
          'final_inspection_date' => 'nullable|date_format:Y-m-d',
          'complete_date' => 'nullable|date_format:Y-m-d',
          'attachments' => 'nullable|array',
          'attachments.*' => 'file|mimes:jpeg,png,jpg,pdf,docx,doc,xlsx|max:10240',
        ];
    }
}
