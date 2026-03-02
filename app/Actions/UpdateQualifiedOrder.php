<?php

namespace App\Actions;

use App\Enum\OrderTypeEnum;
use App\Http\Requests\UpdateQualifiedOrderRequest;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\User;
use App\Support\OrderFinancialEventLogger;
use App\Support\QualifiedOrderDuplicateChecker;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateQualifiedOrder
{
    public function __construct(
        protected QualifiedOrderDuplicateChecker $qualifiedOrderDuplicateChecker
    ) {
    }

    public function handle(UpdateQualifiedOrderRequest $request, Order $order): Order
    {
        return DB::transaction(function () use ($request, $order) {
            $payload = [
                'client_id' => $request->client_id,
                'order_type' => $request->order_type,
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
            $projectAmount = $request->input('project_amount');
            $oldProjectAmount = (float) ($order->project_amount ?? 0);
            $payload['project_amount'] = ($projectAmount !== null && $projectAmount !== '')
                ? (float) $projectAmount
                : null;
            $newProjectAmount = (float) ($payload['project_amount'] ?? 0);

            if ($order->hasReachedContractSigned() && abs($newProjectAmount - $oldProjectAmount) > 0.01) {
                throw ValidationException::withMessages([
                    'project_amount' => 'Project amount cannot be edited after CONTRACT SIGNED BY CLIENT. Use Change Order instead.',
                ]);
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
                $addPair((int) $request->company_contact_id, (int) $request->client_id, (int) $request->company_source_id);
                $addPair((int) $request->associate_company_contact_id_1, (int) $request->associate_client_id_1, (int) $request->associate_source_id_1);
                $addPair((int) $request->associate_company_contact_id_2, (int) $request->associate_client_id_2, (int) $request->associate_source_id_2);
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

            if ($request->filled('owner_ids')) {
                $ownerIds = array_filter(
                    array_map('intval', $request->input('owner_ids', [])),
                    fn ($id) => $id > 0
                );
                $validOwners = User::query()
                    ->whereIn('id', $ownerIds)
                    ->pluck('id')
                    ->toArray();
                $order->owners()->sync($validOwners);
                $order->owner_ids = $validOwners;
            }

            $refreshedOrder = $order->refresh()->load(
                'tags:id,name,color,taggable_id,taggable_type',
                'client.companyContact',
                'user',
                'owners',
                'saleForm',
                'attachments.user',
                'orderStatus.user',
                'changeOrderPayment.paidBy',
                'financialEvents.user',
                'orderCompanyContacts.companyContact',
                'orderCompanyContacts.client',
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

        if ($force) {
            $client->update(['company_contact_id' => $companyId]);
            return;
        }

        if (empty($client->company_contact_id) || (int) $client->company_contact_id === (int) $companyId) {
            $client->update(['company_contact_id' => $companyId]);
            return;
        }

        throw ValidationException::withMessages([
            $fieldForError => 'This client is already associated with another company.',
        ]);
    }
}
