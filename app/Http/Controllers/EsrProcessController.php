<?php

namespace App\Http\Controllers;

use App\Enum\ContactSourceEnum;
use App\Enum\FrameColorEnum;
use App\Enum\GlassCoatingEnum;
use App\Enum\GlassColorEnum;
use App\Enum\GlassTypeEnum;
use App\Enum\LanguageEnum;
use App\Enum\MethodOfPayment;
use App\Enum\OrderStatusEnum;
use App\Enum\OrderTypeEnum;
use App\Enum\PaymentScheduleTypeEnum;
use App\Enum\ProductLineEnum;
use App\Enum\RoleEnum;
use App\Enum\ServiceEnum;
use App\Enum\StatusUserEnum;
use App\Enum\TypeOfFinancing;
use App\Http\Requests\StoreEsrOrderRequest;
use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\PaymentSchedule;
use App\Models\Source;
use App\Models\User;
use App\Support\ClientCompanyContactManager;
use App\Support\OrderClientEmailManager;
use App\Support\OrderFinancialEventLogger;
use App\Support\PaymentInstallmentPresenter;
use App\Support\PaymentScheduleCalculator;
use App\Support\PaymentScheduleTemplates;
use App\Services\CrmNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EsrProcessController extends OrderStorageController
{
    public function editData(Order $order, OrderClientEmailManager $emailManager): JsonResponse
    {
        if (!in_array($order->status, $this->storageStatuses(), true)) {
            return response()->json([
                'message' => 'This order does not belong to ESR PROCESS.',
            ], 422);
        }

        if ($this->isRestrictedOwner() && !$order->isAccessibleToOwner(auth()->user())) {
            return response()->json([
                'message' => 'You are not authorized to access this order.',
            ], 403);
        }

        $order->load([
            'client.companyContact:id,name,email',
            'client.companyContacts:id,name,email',
            'user',
            'owners',
            'attachments.user',
            'paymentSchedule.installments.movements.paidBy',
            'orderCompanyContacts.companyContact',
            'orderCompanyContacts.client.companyContacts',
            'orderCompanyContacts.source',
        ]);

        $selectedContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

        $orderData = $order->toArray();
        $orderData['contact_email'] = $emailManager->resolveRecipient($order);
        $orderData['client_email_selection'] = $emailManager->selectionForOrder($order);
        $orderData['client_email_override'] = $order->client_email_override;
        $orderData['client_email_options'] = $emailManager->optionsForOrder($order, $selectedContact);
        $orderData['payment_schedule'] = PaymentInstallmentPresenter::schedule($order->paymentSchedule);
        $orderData['has_contract_signed'] = $order->hasReachedContractSigned();
        $orderData['order_company_contacts'] = $order->orderCompanyContacts
            ->map(function (OrderCompanyContact $item) use ($order, $emailManager) {
                $data = $item->toArray();
                $data['client_email_options'] = $emailManager->optionsForOrder($order, $item);

                return $data;
            })
            ->values()
            ->all();

        $clients = Client::visibleTo(auth()->user())
            ->with(['companyContact:id,name,email', 'companyContacts:id,name,email'])
            ->select('id', 'name', 'phone', 'email', 'other_phone', 'secondary_email', 'source', 'vip_clients', 'vip_notes', 'company_contact_id')
            ->orderBy('name')
            ->get();

        $owners = User::assignableOrderOwner()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->when(
                $this->isRestrictedOwner(),
                fn ($query) => $query->where('id', auth()->id())
            )
            ->orderBy('name')
            ->get();

        return response()->json([
            'order' => $orderData,
            'clients' => $clients,
            'owners' => $owners,
            'companies' => CompanyContact::visibleTo(auth()->user())
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'sources' => Source::select('id', 'name')->orderBy('name')->get(),
            'sources_clients' => array_map(
                static fn (ContactSourceEnum $source) => $source->value,
                ContactSourceEnum::cases()
            ),
            'statuses' => $this->storageStatuses(),
            'order_types' => [OrderTypeEnum::COMMERCIAL->value],
            'services' => array_map(
                static fn (ServiceEnum $service) => $service->value,
                [
                    ServiceEnum::DELIVERY,
                    ServiceEnum::PICKUP,
                ]
            ),
            'methods_of_payment' => $this->esrPaymentMethods(),
            'type_of_financing' => array_map(
                static fn (TypeOfFinancing $financing) => $financing->value,
                TypeOfFinancing::cases()
            ),
            'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
            'frame_colors' => [
                FrameColorEnum::BLACK->value,
                FrameColorEnum::WHITE->value,
                FrameColorEnum::BRONZE->value,
                FrameColorEnum::CLEAR_ANODIZED->value,
                FrameColorEnum::OTHERS->value,
            ],
            'glass_colors' => [
                GlassColorEnum::BRONZE->value,
                GlassColorEnum::CLEAR->value,
                GlassColorEnum::GRAY->value,
                GlassColorEnum::GREEN->value,
                GlassColorEnum::OTHERS->value,
            ],
            'glass_types' => [
                GlassTypeEnum::LAMINATED->value,
                GlassTypeEnum::INSULATED->value,
                GlassTypeEnum::INSULATED_LAMINATED->value,
            ],
            'glass_coatings' => [
                GlassCoatingEnum::LOWE70->value,
                GlassCoatingEnum::LOWE60->value,
            ],
            'languages' => array_map(
                static fn (LanguageEnum $language) => $language->value,
                LanguageEnum::cases()
            ),
        ]);
    }

    public function create(): Response
    {
        $owners = User::assignableOrderOwner()
            ->select('id', 'name')
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->when(
                $this->isRestrictedOwner(),
                fn ($query) => $query->where('id', auth()->id())
            )
            ->orderBy('name')
            ->get();

        $clients = Client::visibleTo(auth()->user())
            ->with(['companyContact:id,name,email', 'companyContacts:id,name,email'])
            ->select('id', 'name', 'phone', 'email', 'other_phone', 'secondary_email', 'source', 'vip_clients', 'vip_notes', 'company_contact_id')
            ->orderBy('name')
            ->get();

        return Inertia::render('EsrProcess/Create', [
            'clients' => $clients,
            'owners' => $owners,
            'companies' => CompanyContact::visibleTo(auth()->user())
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'sources' => Source::query()
                ->orderBy('name')
                ->get(),
            'order_types' => [OrderTypeEnum::COMMERCIAL->value],
            'services' => array_map(
                static fn (ServiceEnum $service) => $service->value,
                [
                    ServiceEnum::DELIVERY,
                    ServiceEnum::PICKUP,
                ]
            ),
            'product_lines' => array_map(
                static fn (ProductLineEnum $productLine) => $productLine->value,
                ProductLineEnum::cases()
            ),
            'statuses' => [
                OrderStatusEnum::DEALER_REQUEST->value,
                OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
                OrderStatusEnum::REVIEW->value,
            ],
            'methods_of_payment' => $this->esrPaymentMethods(),
            'type_of_financing' => array_map(
                static fn (TypeOfFinancing $financing) => $financing->value,
                TypeOfFinancing::cases()
            ),
            'payment_schedule_templates' => PaymentScheduleTemplates::templates(),
            'sources_clients' => array_map(
                static fn (\App\Enum\ContactSourceEnum $source) => $source->value,
                \App\Enum\ContactSourceEnum::cases()
            ),
        ]);
    }

    public function store(
        StoreEsrOrderRequest $request,
        OrderClientEmailManager $emailManager,
        ClientCompanyContactManager $clientCompanyContactManager,
        CrmNotificationService $crmNotificationService
    ): RedirectResponse {
        $validated = $request->validated();
        $ownerIds = $this->validatedOwnerIds($request, $validated['owner_ids']);
        $client = Client::visibleTo($request->user())
            ->with('companyContacts')
            ->findOrFail((int) $validated['client_id']);
        $company = CompanyContact::visibleTo($request->user())
            ->findOrFail((int) $validated['company_contact_id']);
        $requestedCompanyIds = collect([
            $validated['company_contact_id'],
            $validated['associate_company_contact_id_1'] ?? null,
            $validated['associate_company_contact_id_2'] ?? null,
        ])
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($requestedCompanyIds->isNotEmpty()) {
            $visibleCompanyCount = CompanyContact::visibleTo($request->user())
                ->whereIn('id', $requestedCompanyIds)
                ->count();

            if ($visibleCompanyCount !== $requestedCompanyIds->count()) {
                throw ValidationException::withMessages([
                    'company_contact_id' => 'You can only use companies associated with your owner account.',
                ]);
            }
        }
        $selectionError = $emailManager->validateSelectionForContext(
            $client,
            (string) $validated['client_email_selection'],
            $company
        );

        if ($selectionError !== null) {
            throw ValidationException::withMessages([
                'client_email_selection' => $selectionError,
            ]);
        }

        DB::transaction(function () use ($request, $validated, $ownerIds, $emailManager, $client, $clientCompanyContactManager, $crmNotificationService) {
            $sourceId = Source::firstOrCreate([
                'name' => ContactSourceEnum::ESR_REFER->value,
            ])->id;

            $order = Order::create([
                'client_id' => $validated['client_id'],
                'user_id' => auth()->id(),
                'order_type' => OrderTypeEnum::COMMERCIAL->value,
                'product_line' => $validated['product_line'],
                'service' => $validated['service'] ?? null,
                'esr_design' => $request->boolean('esr_design'),
                'esr_express' => $request->boolean('esr_express'),
                'esr_reylos_glass' => $request->boolean('esr_reylos_glass'),
                'esr_service' => $request->boolean('esr_service'),
                'method_of_payment' => $validated['method_of_payment'] ?? null,
                'type_of_financing' => in_array(($validated['method_of_payment'] ?? null), [
                    MethodOfPayment::FINANCED->value,
                    MethodOfPayment::FINANCEDCASH->value,
                ], true) ? ($validated['type_of_financing'] ?? null) : null,
                'down_payment' => ($validated['method_of_payment'] ?? null) === MethodOfPayment::FINANCEDCASH->value
                    ? ($validated['down_payment'] ?? null)
                    : null,
                'name' => $validated['name'],
                'job_address' => $validated['job_address'],
                'city' => $validated['city'],
                'job_state' => $validated['job_state'],
                'job_zip' => $validated['job_zip'],
                'status' => $validated['status'],
                'order_number' => $validated['order_number'],
                'project_amount' => $validated['project_amount'],
                'notes' => $validated['notes'] ?? null,
            ]);

            $order->owners()->sync($ownerIds);

            $emailManager->applySelection($order, (string) $validated['client_email_selection']);
            $order->save();

            $this->createPaymentSchedule($order, $validated);

            $pairs = [
                [
                    'company_id' => (int) $validated['company_contact_id'],
                    'client_id' => (int) $validated['client_id'],
                ],
                [
                    'company_id' => (int) ($validated['associate_company_contact_id_1'] ?? 0),
                    'client_id' => (int) ($validated['associate_client_id_1'] ?? 0),
                ],
                [
                    'company_id' => (int) ($validated['associate_company_contact_id_2'] ?? 0),
                    'client_id' => (int) ($validated['associate_client_id_2'] ?? 0),
                ],
            ];

            foreach ($pairs as $index => $pair) {
                if (!$pair['company_id'] || !$pair['client_id']) {
                    continue;
                }

                $pairClient = $index === 0
                    ? $client
                    : Client::visibleTo($request->user())->find($pair['client_id']);

                if ($pairClient) {
                    $clientCompanyContactManager->attach(
                        $pairClient,
                        $pair['company_id'],
                        true
                    );
                }

                OrderCompanyContact::create([
                    'order_id' => $order->id,
                    'company_contact_id' => $pair['company_id'],
                    'client_id' => $pair['client_id'],
                    'source_id' => $sourceId,
                    'is_selected' => $index === 0,
                    'selected_at' => $index === 0 ? now() : null,
                ]);
            }

            $order->orderStatus()->create([
                'status' => $validated['status'],
                'user_id' => auth()->id(),
                'notes' => $validated['status'] . ' created by ' . auth()->user()->name,
            ]);

            if (!empty($validated['notes'])) {
                $order->notes()->create([
                    'content' => $validated['notes'],
                    'type' => 'order_note',
                    'user_id' => auth()->id(),
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $fileName = time() . '_' . Str::replace(' ', '_', $file->getClientOriginalName());
                    $filePath = $file->storeAs('order_files', $fileName, 'public');

                    $order->attachments()->create([
                        'filename' => $file->getClientOriginalName(),
                        'file_path' => $filePath,
                        'file_type' => 'order_files',
                        'user_id' => auth()->id(),
                    ]);
                }
            }

            DB::afterCommit(function () use ($order, $request, $crmNotificationService): void {
                $crmNotificationService->recordEsrOrderCreated(
                    $order->fresh(['user', 'owners']),
                    $request->user()
                );
            });
        });

        return redirect()
            ->route('esr-process.index')
            ->with('success', 'Order created successfully.');
    }

    private function createPaymentSchedule(Order $order, array $validated): void
    {
        $methodOfPayment = (string) ($validated['method_of_payment'] ?? '');
        $paymentScheduleType = (string) ($validated['payment_schedule_type'] ?? '');
        $isCashAndFinanced = $methodOfPayment === MethodOfPayment::FINANCEDCASH->value;
        $requiresSchedule = in_array($methodOfPayment, [
            MethodOfPayment::CASH->value,
            MethodOfPayment::FINANCEDCASH->value,
        ], true);

        if (!$requiresSchedule || $paymentScheduleType === '') {
            return;
        }

        $totalAmount = $isCashAndFinanced
            ? (float) ($validated['down_payment'] ?? 0)
            : (float) ($validated['project_amount'] ?? 0);

        if ($paymentScheduleType === PaymentScheduleTypeEnum::CUSTOMIZED->value) {
            $customSchedule = $validated['custom_schedule'] ?? [];
            $installments = [];
            $runningPercent = 0.0;
            $count = count($customSchedule);

            foreach ($customSchedule as $index => $item) {
                $amount = round((float) ($item['amount'] ?? 0), 2);
                $percentage = $totalAmount > 0
                    ? round(($amount / $totalAmount) * 100, 2)
                    : 0.0;

                if ($index === $count - 1 && $totalAmount > 0) {
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
            $scheduleItems = PaymentScheduleTemplates::itemsFor($paymentScheduleType);
            $installments = PaymentScheduleCalculator::withAmounts($scheduleItems, $totalAmount);
        }

        $paymentSchedule = PaymentSchedule::create([
            'order_id' => $order->id,
            'schedule_type' => $paymentScheduleType,
            'total_amount' => $totalAmount,
        ]);

        foreach ($installments as $index => $installment) {
            $paymentSchedule->installments()->create([
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
            "Payment schedule configured as {$paymentScheduleType}",
            [
                'schedule_type' => $paymentScheduleType,
                'total_amount' => $totalAmount,
                'installments' => $installments,
            ]
        );
    }

    public function destroy(Order $order): JsonResponse
    {
        if (!in_array($order->status, $this->storageStatuses(), true)) {
            return response()->json([
                'message' => 'This order does not belong to ESR PROCESS.',
            ], 422);
        }

        $order->delete();

        return response()->json([
            'message' => 'Order deleted successfully.',
        ]);
    }

    protected function storageStatuses(): array
    {
        return [
            OrderStatusEnum::DEALER_REQUEST->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
            OrderStatusEnum::REVIEW->value,
            OrderStatusEnum::ACCOUNT_RECEIPT->value,
            OrderStatusEnum::PRODUCTION->value,
            OrderStatusEnum::PRODUCTION_SERVICES->value,
            OrderStatusEnum::PRE_COORDINATION_ACCOUNTING->value,
            OrderStatusEnum::PENDING_MAT_REYLOS->value,
            OrderStatusEnum::PENDING_MATERIALS->value,
            OrderStatusEnum::PENDING_MATERIALS_EWS->value,
            OrderStatusEnum::PLANNED->value,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED->value,
            OrderStatusEnum::STORAGE_MATERIAL->value,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED->value,
            OrderStatusEnum::PENDING_PAYMENT->value,
            OrderStatusEnum::PENDING_MATCH->value,
            OrderStatusEnum::COMPLETE->value,
            OrderStatusEnum::LOST->value,
        ];
    }

    protected function paginatedStorageStatuses(): array
    {
        return [
            OrderStatusEnum::COMPLETE->value,
            OrderStatusEnum::LOST->value,
        ];
    }

    protected function boardTitle(): string
    {
        return 'ESR PROCESS';
    }

    protected function indexRoute(): string
    {
        return 'esr-process.index';
    }

    protected function tasksRoute(): string
    {
        return 'esr-process.tasks';
    }

    protected function sortableGroup(): string
    {
        return 'esr-process';
    }

    protected function searchOrigin(): string
    {
        return 'esr_process';
    }

    protected function showCreateOrder(): bool
    {
        return true;
    }

    protected function showEsrTaskActions(): bool
    {
        return !$this->isRestrictedOwner();
    }

    protected function orderViewRoute(): string
    {
        return 'esr-process.order-view';
    }

    protected function canReorderOrders(): bool
    {
        return true;
    }

    private function esrPaymentMethods(): array
    {
        return array_values(array_filter(
            array_map(static fn (MethodOfPayment $method) => $method->value, MethodOfPayment::cases()),
            static fn (string $method) => !in_array($method, [
                MethodOfPayment::AIA->value,
                MethodOfPayment::ZELLE->value,
                MethodOfPayment::CHECK->value,
            ], true)
        ));
    }

    private function isRestrictedOwner(): bool
    {
        $user = auth()->user();

        return $user
            && $user->hasRole(RoleEnum::OWNER->value)
            && !$user->hasAnyRole([
                RoleEnum::ADMIN->value,
                RoleEnum::ACCOUNT_MANAGER->value,
                RoleEnum::OWNER_ADMIN->value,
                RoleEnum::FRONTDESK_ADMIN->value,
            ]);
    }

    private function validatedOwnerIds(StoreEsrOrderRequest $request, array $requestedOwnerIds): array
    {
        if ($this->isRestrictedOwner()) {
            return [(int) $request->user()->id];
        }

        $ownerIds = collect($requestedOwnerIds)
            ->map(fn ($ownerId) => (int) $ownerId)
            ->filter(fn (int $ownerId) => $ownerId > 0)
            ->unique()
            ->values();

        $validOwnerIds = User::assignableOrderOwner()
            ->where('status', StatusUserEnum::ACTIVE->value)
            ->whereIn('id', $ownerIds)
            ->pluck('id')
            ->map(fn ($ownerId) => (int) $ownerId)
            ->values();

        if ($validOwnerIds->count() !== $ownerIds->count()) {
            throw ValidationException::withMessages([
                'owner_ids' => 'Select only active users with the owner or owner admin role.',
            ]);
        }

        return $validOwnerIds->all();
    }
}
