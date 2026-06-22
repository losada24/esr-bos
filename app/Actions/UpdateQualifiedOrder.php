<?php

namespace App\Actions;

use App\Enum\MethodOfPayment;
use App\Enum\OrderTypeEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Http\Requests\UpdateQualifiedOrderRequest;
use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\PaymentSchedule;
use App\Models\User;
use App\Support\ClientCompanyContactManager;
use App\Support\OrderClientEmailDeliveryLogger;
use App\Support\OrderFinancialEventLogger;
use App\Support\OrderClientEmailManager;
use App\Support\OrderOwnerChangeNotifier;
use App\Support\OrderPaymentInformationAuditLogger;
use App\Support\Commissions\CommissionCalculator;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentScheduleTemplates;
use App\Support\QualifiedOrderDuplicateChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateQualifiedOrder
{
    public function __construct(
        protected QualifiedOrderDuplicateChecker $qualifiedOrderDuplicateChecker,
        protected OrderOwnerChangeNotifier $orderOwnerChangeNotifier,
        protected ClientCompanyContactManager $clientCompanyContactManager,
        protected OrderClientEmailManager $orderClientEmailManager,
        protected OrderClientEmailDeliveryLogger $orderClientEmailDeliveryLogger,
        protected CommissionCalculator $commissionCalculator
    ) {
    }

    public function handle(UpdateQualifiedOrderRequest $request, Order $order): Order
    {
        return DB::transaction(function () use ($request, $order) {
            $order->loadMissing('paymentSchedule.installments');
            $beforeClientEmailDelivery = $this->orderClientEmailDeliveryLogger->capture($order);
            $previousOwnerIds = $this->orderOwnerChangeNotifier->normalizeOwnerIds(
                $order->owners()->pluck('users.id')->all()
            );
            $beforePaymentInformation = OrderPaymentInformationAuditLogger::snapshot($order);
            $payload = [
                'client_id' => $request->client_id,
                'order_type' => $request->order_type,
                'product_line' => $request->product_line,
                'name' => $request->name,
                'job_address' => $request->job_address,
                'city' => $request->city,
                'job_state' => $request->job_state,
                'job_zip' => $request->job_zip,
                'description' => $request->description,
                'notes' => $request->notes,
                'source' => $request->source ?? '',
                'bid_due_date' => $request->bid_due_date ?: null,
                'is_supply' => (bool) $request->is_supply,
                'schedule_appointment' => $request->schedule_appointment ?: null,
            ];
            if ($request->exists('invoice_number')) {
                $invoiceNumber = trim((string) $request->input('invoice_number'));
                $payload['invoice_number'] = $invoiceNumber !== '' ? $invoiceNumber : null;
            }
            $projectAmount = $request->exists('project_amount')
                ? $request->input('project_amount')
                : $order->project_amount;
            $oldProjectAmount = (float) ($order->project_amount ?? 0);
            $payload['project_amount'] = ($projectAmount !== null && $projectAmount !== '')
                ? (float) $projectAmount
                : null;
            $newProjectAmount = (float) ($payload['project_amount'] ?? 0);
            $hasRecordedSchedulePayments = $order->paymentSchedule
                ? $order->paymentSchedule->installments()->whereHas('movements')->exists()
                : false;
            if ($hasRecordedSchedulePayments && abs($newProjectAmount - $oldProjectAmount) > 0.01) {
                throw ValidationException::withMessages([
                    'project_amount' => 'Project amount cannot be changed after payments are recorded.',
                ]);
            }

            $touchesPaymentInformation = $request->exists('method_of_payment')
                || $request->exists('type_of_financing')
                || $request->exists('down_payment')
                || $request->exists('payment_schedule_type')
                || $request->exists('custom_schedule');
            $resolvedMethodOfPayment = $request->exists('method_of_payment')
                ? (string) ($request->input('method_of_payment') ?? '')
                : (string) ($order->method_of_payment ?? '');
            if ($touchesPaymentInformation) {
                $payload['method_of_payment'] = $resolvedMethodOfPayment !== '' ? $resolvedMethodOfPayment : null;
                $payload['type_of_financing'] = in_array(
                    $resolvedMethodOfPayment,
                    [MethodOfPayment::FINANCED->value, MethodOfPayment::FINANCEDCASH->value],
                    true
                )
                    ? ($request->input('type_of_financing') ?: null)
                    : null;
                $payload['down_payment'] = $resolvedMethodOfPayment === MethodOfPayment::FINANCEDCASH->value
                    ? ($request->input('down_payment') !== '' ? $request->input('down_payment') : null)
                    : null;
            }

            $statusChanged = false;
            if ($request->filled('status')) {
                $incomingStatus = $request->status;
                $payload['status'] = $incomingStatus;
                $statusChanged = !empty($incomingStatus) &&
                    strcasecmp((string) $order->status, (string) $incomingStatus) !== 0;
            }

            $companyClientPairs = [];
            $addPair = function (?int $companyId, ?int $clientId, ?int $sourceId) use (&$companyClientPairs) {
                if (!$companyId && !$clientId && !$sourceId) {
                    return;
                }
                if (!$companyId || !$clientId || !$sourceId) {
                    return;
                }
                $companyClientPairs[] = [
                    'company_contact_id' => $companyId,
                    'client_id' => $clientId,
                    'source_id' => $sourceId,
                ];
            };

            if ($request->order_type === OrderTypeEnum::COMMERCIAL->value) {
                $addPair(
                    (int) $request->input('company_contact_id'),
                    (int) $request->input('client_id'),
                    (int) $request->input('company_source_id')
                );
                foreach (range(1, 4) as $index) {
                    $addPair(
                        (int) $request->input("associate_company_contact_id_{$index}"),
                        (int) $request->input("associate_client_id_{$index}"),
                        (int) $request->input("associate_source_id_{$index}")
                    );
                }
            }

            $primaryClient = $request->filled('client_id')
                ? Client::with('companyContacts')->find((int) $request->input('client_id'))
                : null;
            $primaryCompany = $request->order_type === OrderTypeEnum::COMMERCIAL->value && $request->filled('company_contact_id')
                ? CompanyContact::find((int) $request->input('company_contact_id'))
                : null;
            $clientEmailSelection = (string) $request->input('client_email_selection', OrderClientEmailManager::PRIMARY_SELECTION);
            $selectionError = $this->orderClientEmailManager->validateSelectionForContext(
                $primaryClient,
                $clientEmailSelection,
                $primaryCompany
            );
            if ($selectionError !== null) {
                throw ValidationException::withMessages([
                    'client_email_selection' => $selectionError,
                ]);
            }

            if ($request->order_type === OrderTypeEnum::COMMERCIAL->value) {
                $selectedClientId = null;
                if ($order->client_id && collect($companyClientPairs)->contains(fn ($pair) => (int) $pair['client_id'] === (int) $order->client_id)) {
                    $selectedClientId = (int) $order->client_id;
                } elseif (count($companyClientPairs) === 1) {
                    $selectedClientId = (int) $companyClientPairs[0]['client_id'];
                }
                $payload['client_id'] = $selectedClientId;
            }

            $duplicateClientIds = collect()
                ->when(isset($payload['client_id']) && !empty($payload['client_id']), fn ($c) => $c->push((int) $payload['client_id']))
                ->when($request->filled('client_id'), fn ($c) => $c->push((int) $request->input('client_id')))
                ->merge(collect($companyClientPairs)->pluck('client_id')->map(fn ($id) => (int) $id))
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values();

            foreach ($duplicateClientIds as $duplicateClientId) {
                $this->qualifiedOrderDuplicateChecker->ensureNoDuplicateUnlessForced(
                    $payload['name'] ?? $request->input('name'),
                    (int) $duplicateClientId,
                    $request->boolean('force_duplicate'),
                    (int) $order->id
                );
            }

            $order->fill($payload);
            $order->save();

            if (abs($newProjectAmount - $oldProjectAmount) > 0.01) {
                OrderFinancialEventLogger::log(
                    $order,
                    'PROJECT_AMOUNT_UPDATED',
                    'Project amount updated',
                    [
                        'before_amount' => $oldProjectAmount,
                        'after_amount' => $newProjectAmount,
                    ]
                );
            }

            $hasScheduleTypeInput = $request->exists('payment_schedule_type');
            $hasCustomScheduleInput = $request->exists('custom_schedule');
            $requiresSchedule = in_array(
                $resolvedMethodOfPayment,
                [MethodOfPayment::CASH->value, MethodOfPayment::FINANCEDCASH->value],
                true
            );
            $scheduleTotalAmount = $resolvedMethodOfPayment === MethodOfPayment::FINANCEDCASH->value
                ? (float) ($order->down_payment ?? 0)
                : (float) ($order->project_amount ?? 0);
            $existingSchedule = $order->paymentSchedule()->with('installments')->first();
            $shouldProcessSchedule = $touchesPaymentInformation || $hasScheduleTypeInput || $hasCustomScheduleInput;
            $scheduleType = $requiresSchedule
                ? ($hasScheduleTypeInput
                    ? (string) ($request->input('payment_schedule_type') ?? '')
                    : (string) ($existingSchedule?->schedule_type ?? ''))
                : '';
            $customSchedule = $hasCustomScheduleInput ? $request->input('custom_schedule', []) : [];
            $hasRecordedPayments = $existingSchedule
                ? $existingSchedule->installments()->whereHas('movements')->exists()
                : false;

            if ($requiresSchedule && !$hasCustomScheduleInput && $scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value && $existingSchedule) {
                $customSchedule = $existingSchedule->installments
                    ->sortBy('position')
                    ->values()
                    ->map(fn ($item) => [
                        'label' => $item->label,
                        'amount' => (float) $item->amount,
                    ])->all();
            }

            if ($shouldProcessSchedule && $hasRecordedPayments) {
                if (!$requiresSchedule || !$existingSchedule || $scheduleType === '' || $scheduleType !== (string) $existingSchedule->schedule_type) {
                    throw ValidationException::withMessages([
                        'payment_schedule_type' => 'Payment schedule cannot be changed after payments are recorded.',
                    ]);
                }

                if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                    $incomingItems = collect($customSchedule)
                        ->map(function ($item) {
                            return [
                                'label' => trim((string) ($item['label'] ?? '')),
                                'amount' => round((float) ($item['amount'] ?? 0), 2),
                            ];
                        })
                        ->filter(fn ($item) => $item['label'] !== '')
                        ->values()
                        ->all();

                    $existingItems = $existingSchedule->installments
                        ->sortBy('position')
                        ->values()
                        ->map(fn ($item) => [
                            'label' => trim((string) $item->label),
                            'amount' => round((float) $item->amount, 2),
                        ])
                        ->all();

                    if ($incomingItems !== $existingItems) {
                        throw ValidationException::withMessages([
                            'payment_schedule_type' => 'Payment schedule cannot be changed after payments are recorded.',
                        ]);
                    }
                }
            } elseif ($shouldProcessSchedule) {
                if (!$requiresSchedule || $scheduleType === '') {
                    if ($existingSchedule) {
                        $previousScheduleType = $existingSchedule->schedule_type;
                        $previousTotalAmount = (float) $existingSchedule->total_amount;
                        $existingSchedule->installments()->delete();
                        $existingSchedule->delete();

                        OrderFinancialEventLogger::log(
                            $order,
                            'PAYMENT_SCHEDULE_REMOVED',
                            'Payment schedule removed',
                            [
                                'before_schedule_type' => $previousScheduleType,
                                'before_total_amount' => $previousTotalAmount,
                            ]
                        );
                    }
                } else {
                    if ($scheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
                        $installments = [];
                        $runningPercent = 0.0;
                        $count = count($customSchedule);
                        foreach ($customSchedule as $index => $item) {
                            $amount = round((float) ($item['amount'] ?? 0), 2);
                            $percentage = $scheduleTotalAmount > 0
                                ? round(($amount / $scheduleTotalAmount) * 100, 2)
                                : 0;

                            if ($index === $count - 1 && $scheduleTotalAmount > 0) {
                                $percentage = round(100 - $runningPercent, 2);
                            }

                            $runningPercent += $percentage;
                            $installments[] = [
                                'label' => trim((string) ($item['label'] ?? '')),
                                'percentage' => $percentage,
                                'amount' => $amount,
                            ];
                        }
                    } else {
                        $scheduleItems = PaymentScheduleTemplates::itemsFor($scheduleType);
                        $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $scheduleTotalAmount);
                    }

                    $beforeScheduleType = $existingSchedule?->schedule_type;
                    $beforeTotalAmount = $existingSchedule ? (float) $existingSchedule->total_amount : null;
                    $beforeInstallments = $existingSchedule
                        ? $existingSchedule->installments
                            ->sortBy('position')
                            ->values()
                            ->map(fn ($item) => [
                                'label' => $item->label,
                                'percentage' => round((float) $item->percentage, 2),
                                'amount' => round((float) $item->amount, 2),
                            ])->all()
                        : [];

                    $afterInstallments = collect($installments)
                        ->map(fn ($item) => [
                            'label' => $item['label'],
                            'percentage' => round((float) $item['percentage'], 2),
                            'amount' => round((float) $item['amount'], 2),
                        ])
                        ->values()
                        ->all();

                    $scheduleChanged =
                        $beforeScheduleType !== $scheduleType
                        || abs((float) ($beforeTotalAmount ?? 0) - $scheduleTotalAmount) > 0.01
                        || $beforeInstallments !== $afterInstallments;

                    if ($scheduleChanged) {
                        if (!$existingSchedule) {
                            $existingSchedule = PaymentSchedule::create([
                                'order_id' => $order->id,
                                'schedule_type' => $scheduleType,
                                'total_amount' => $scheduleTotalAmount,
                            ]);
                        } else {
                            $existingSchedule->update([
                                'schedule_type' => $scheduleType,
                                'total_amount' => $scheduleTotalAmount,
                            ]);
                            $existingSchedule->installments()->delete();
                        }

                        foreach ($installments as $index => $installment) {
                            $existingSchedule->installments()->create([
                                'position' => $index + 1,
                                'label' => $installment['label'],
                                'percentage' => $installment['percentage'],
                                'amount' => $installment['amount'],
                                'status' => 'PENDING',
                            ]);
                        }

                        OrderFinancialEventLogger::log(
                            $order,
                            'PAYMENT_SCHEDULE_DEFINED',
                            "Payment schedule configured as {$scheduleType}",
                            [
                                'schedule_type' => $scheduleType,
                                'total_amount' => $scheduleTotalAmount,
                                'before_schedule_type' => $beforeScheduleType,
                                'before_total_amount' => $beforeTotalAmount,
                                'before_installments' => $beforeInstallments,
                                'installments' => $afterInstallments,
                            ]
                        );
                    }
                }
            }

            $order->load('paymentSchedule.installments');
            OrderPaymentInformationAuditLogger::logIfChanged(
                $order,
                $beforePaymentInformation,
                'FRONTDESK_EDIT_MODAL',
                $request
            );

            if ($order->hasReachedContractSigned() && $request->has('change_order_enabled')) {
                $changeOrderEnabled = filter_var($request->input('change_order_enabled'), FILTER_VALIDATE_BOOLEAN);
                $changeOrderPayment = $order->orderPayments()->where('type', 'CHANGE_ORDER')->first();

                if ($changeOrderEnabled) {
                    $changeOrderPayload = [
                        'amount' => $request->input('change_order_amount') ?? 0,
                        'note' => $request->input('change_order_note'),
                    ];

                    if ($changeOrderPayment) {
                        $before = [
                            'amount' => (float) $changeOrderPayment->amount,
                            'note' => $changeOrderPayment->note,
                            'status' => $changeOrderPayment->status,
                        ];

                        $changeOrderPayment->update($changeOrderPayload);

                        if (
                            abs((float) $before['amount'] - (float) ($changeOrderPayment->amount ?? 0)) > 0.01 ||
                            (string) $before['note'] !== (string) ($changeOrderPayment->note ?? '')
                        ) {
                            OrderFinancialEventLogger::log(
                                $order,
                                'CHANGE_ORDER_UPDATED',
                                'Change order payment updated',
                                [
                                    'order_payment_id' => $changeOrderPayment->id,
                                    'before' => $before,
                                    'after' => [
                                        'amount' => (float) $changeOrderPayment->amount,
                                        'note' => $changeOrderPayment->note,
                                        'status' => $changeOrderPayment->status,
                                    ],
                                ]
                            );
                        }
                    } else {
                        $createdChangeOrder = $order->orderPayments()->create([
                            'type' => 'CHANGE_ORDER',
                            'status' => 'PENDING',
                            ...$changeOrderPayload,
                        ]);

                        OrderFinancialEventLogger::log(
                            $order,
                            'CHANGE_ORDER_CREATED',
                            'Change order payment created',
                            [
                                'order_payment_id' => $createdChangeOrder->id,
                                'amount' => (float) $createdChangeOrder->amount,
                                'note' => $createdChangeOrder->note,
                                'status' => $createdChangeOrder->status,
                            ]
                        );
                    }
                } elseif ($changeOrderPayment) {
                    OrderFinancialEventLogger::log(
                        $order,
                        'CHANGE_ORDER_REMOVED',
                        'Change order payment removed',
                        [
                            'order_payment_id' => $changeOrderPayment->id,
                            'amount' => (float) $changeOrderPayment->amount,
                            'note' => $changeOrderPayment->note,
                            'status' => $changeOrderPayment->status,
                        ]
                    );

                    $changeOrderPayment->delete();
                }

                $this->commissionCalculator->refreshOrderCommissions($order->fresh());
            }

            if ($statusChanged) {
                $order->orderStatus()->create([
                    'status' => $order->status,
                    'user_id' => auth()->id(),
                    'notes' => "{$order->status} updated via frontdesk edit by " . (auth()->user()->name ?? 'System'),
                ]);
            }

            $hasAnySaleFormData =
                $request->boolean('sale') ||
                $request->boolean('installation') ||
                $request->boolean('permit') ||
                $request->boolean('replacement') ||
                $request->boolean('new_construction') ||
                $request->boolean('financing') ||
                $request->boolean('screen') ||
                $request->boolean('design') ||
                $request->boolean('mountin') ||
                $request->boolean('bar') ||
                $request->boolean('shutter_hole') ||
                $request->boolean('floor_cutting') ||
                $request->boolean('interior_finish') ||
                $request->boolean('hoa') ||
                $request->filled('floor') ||
                $request->filled('frame_color') ||
                $request->filled('glass_color') ||
                $request->filled('glass_type') ||
                $request->filled('glass_coating') ||
                $request->filled('language') ||
                ((int) $request->input('door_quantity', 0) > 0) ||
                ((int) $request->input('window_quantity', 0) > 0);

            $saleFormPayload = [
                'sale'             => $request->boolean('sale'),
                'installation'     => $request->boolean('installation'),
                'permit'           => $request->boolean('permit'),
                'replacement'      => $request->boolean('replacement'),
                'new_construction' => $request->boolean('new_construction'),
                'financing'        => $request->boolean('financing'),
                'screen'           => $request->boolean('screen'),
                'design'           => $request->boolean('door_design') ?: $request->boolean('design'),
                'mountin'          => $request->boolean('mountin'),
                'bar'              => $request->boolean('bar'),
                'shutter_hole'     => $request->boolean('shutter_hole'),
                'floor_cutting'    => $request->boolean('floor_cutting'),
                'interior_finish'  => $request->boolean('interior_finish'),
                'hoa'              => $request->boolean('hoa'),
                'floor'            => $request->input('floor', ''),
                'frame_color'      => $request->input('frame_color', ''),
                'glass_color'      => $request->input('glass_color', ''),
                'glass_type'       => $request->input('glass_type', ''),
                'glass_coating'    => $request->input('glass_coating', ''),
                'language'         => $request->input('language', ''),
                'door_quantity'    => (int) $request->input('door_quantity', 0),
                'window_quantity'  => (int) $request->input('window_quantity', 0),
            ];

            if ($hasAnySaleFormData) {
                $order->saleForm()->updateOrCreate([], $saleFormPayload);
            } elseif ($order->saleForm) {
                $order->saleForm()->delete();
            }

            if ($request->order_type === OrderTypeEnum::COMMERCIAL->value) {
                foreach ($companyClientPairs as $pair) {
                    $this->applyCompanyToClient(
                        (int) $pair['client_id'],
                        (int) $pair['company_contact_id'],
                        'company_contact_id',
                        true
                    );
                }
                OrderCompanyContact::withTrashed()
                    ->where('order_id', $order->id)
                    ->forceDelete();
                $selectedClientId = $order->client_id ? (int) $order->client_id : null;
                foreach ($companyClientPairs as $pair) {
                    $isSelected = $selectedClientId && (int) $pair['client_id'] === (int) $selectedClientId;
                    OrderCompanyContact::create([
                        'order_id' => $order->id,
                        'company_contact_id' => $pair['company_contact_id'],
                        'client_id' => $pair['client_id'],
                        'source_id' => $pair['source_id'],
                        'is_selected' => $isSelected,
                        'selected_at' => $isSelected ? now() : null,
                    ]);
                }
            } else {
                OrderCompanyContact::withTrashed()
                    ->where('order_id', $order->id)
                    ->forceDelete();
            }

            $this->orderClientEmailManager->applySelection($order, $clientEmailSelection);
            $order->save();
            $this->orderClientEmailDeliveryLogger->logIfChanged($order, $beforeClientEmailDelivery);

            if ($request->exists('owner_ids')) {
                $ownerIds = $this->orderOwnerChangeNotifier->normalizeOwnerIds($request->input('owner_ids', []));
                $validOwners = User::query()
                    ->whereIn('id', $ownerIds)
                    ->pluck('id')
                    ->toArray();
                $order->owners()->sync($validOwners);
                $order->owner_ids = $validOwners;
                $this->orderOwnerChangeNotifier->notify($order, $previousOwnerIds, $validOwners);
            }

            $refreshedOrder = $order->refresh()->load(
                'tags:id,name,color,taggable_id,taggable_type',
                'client.companyContact',
                'client.companyContacts',
                'user',
                'owners',
                'saleForm',
                'attachments.user',
                'orderStatus.user',
                'changeOrderPayment.paidBy',
                'financialEvents.user',
                'paymentSchedule.installments.paidBy',
                'paymentSchedule.installments.movements.paidBy',
                'orderCompanyContacts.companyContact',
                'orderCompanyContacts.client.companyContacts',
                'orderCompanyContacts.source'
            );
            $refreshedOrder->setAttribute('has_contract_signed', $refreshedOrder->hasReachedContractSigned());

            return $refreshedOrder;
        });
    }

    protected function applyCompanyToClient(?int $clientId, ?int $companyId, string $fieldForError, bool $force = false): void
    {
        if (!$clientId || !$companyId) {
            return;
        }

        $client = Client::find($clientId);
        if (!$client) {
            return;
        }

        $this->clientCompanyContactManager->attach($client, $companyId, $force && empty($client->company_contact_id));
    }
}
