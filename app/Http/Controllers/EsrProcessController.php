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
use App\Enum\ServiceControlPriorityEnum;
use App\Enum\ServiceControlCreationSourceEnum;
use App\Enum\ServiceControlRequestOriginEnum;
use App\Enum\ServiceControlSourceEnum;
use App\Enum\ServiceControlStatusEnum;
use App\Enum\ServiceControlTypeEnum;
use App\Enum\StatusUserEnum;
use App\Enum\TypeOfFinancing;
use App\Http\Requests\StoreEsrOrderRequest;
use App\Models\Client;
use App\Models\CompanyContact;
use App\Models\Order;
use App\Models\OrderCompanyContact;
use App\Models\PaymentSchedule;
use App\Models\ServiceControl;
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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class EsrProcessController extends OrderStorageController
{
    private function normalizePhone(?string $phone): string
    {
        return preg_replace('/\D+/', '', (string) $phone) ?? '';
    }

    public function editData(Order $order, OrderClientEmailManager $emailManager): JsonResponse
    {
        if ($order->is_post_sale_service && $order->service_origin === ServiceControlRequestOriginEnum::SERVICE->value) {
            return response()->json([
                'message' => 'Post-sale services can only be moved between statuses from the ESR PROCESS pipeline.',
            ], 422);
        }

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
            'order_types' => [
                OrderTypeEnum::COMMERCIAL->value,
                OrderTypeEnum::RESIDENTIAL->value,
            ],
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
        return Inertia::render('EsrProcess/Create', $this->createFormData());
    }

    public function createService(): Response
    {
        return Inertia::render('EsrProcess/Create', $this->createFormData(true));
    }

    private function createFormData(bool $isServiceForm = false): array
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

        return [
            'clients' => $clients,
            'owners' => $owners,
            'companies' => CompanyContact::visibleTo(auth()->user())
                ->select('id', 'name', 'email')
                ->orderBy('name')
                ->get(),
            'sources' => Source::query()
                ->orderBy('name')
                ->get(),
            'order_types' => [
                OrderTypeEnum::COMMERCIAL->value,
                OrderTypeEnum::RESIDENTIAL->value,
            ],
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
            'statuses' => $this->createOrderStatuses(),
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
            'is_service_form' => $isServiceForm,
            'page_title' => $isServiceForm ? 'New Service' : 'Create ESR Order',
            'submit_route' => $isServiceForm ? 'esr-process.store-service' : 'esr-process.store-order',
        ];
    }

    public function searchExternalOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['required', 'string', 'max:255'],
            'service_only' => ['nullable', 'boolean'],
            'sales_only' => ['nullable', 'boolean'],
        ]);

        $baseUrl = rtrim((string) config('services.esr_orders.base_url'), '/');
        $token = (string) config('services.esr_orders.token');

        if ($token === '') {
            return response()->json([
                'message' => 'ESR orders token is not configured.',
            ], 422);
        }

        $response = Http::withToken($token)
            ->acceptJson()
            ->timeout(15)
            ->get($baseUrl . '/crm/orders', [
                'search' => $validated['search'],
            ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'Unable to search the ESR order service.',
            ], 502);
        }

        $serviceOnly = (bool) ($validated['service_only'] ?? false);
        $salesOnly = (bool) ($validated['sales_only'] ?? false);
        $orders = collect($response->json('data', []))
            ->when($serviceOnly, function ($items) {
                return $items->filter(function ($item) {
                    $orderType = Str::lower((string) data_get($item, 'order_type', ''));
                    $serviceValue = Str::lower((string) data_get($item, 'service', ''));

                    return Str::contains($orderType . ' ' . $serviceValue, 'service');
                });
            })
            ->when($salesOnly, function ($items) {
                return $items->reject(function ($item) {
                    $orderType = Str::lower((string) data_get($item, 'order_type', ''));
                    $serviceValue = Str::lower((string) data_get($item, 'service', ''));

                    return Str::contains($orderType . ' ' . $serviceValue, 'service');
                });
            })
            ->values();
        $search = trim((string) $validated['search']);
        $order = $orders->first(fn ($item) => (string) data_get($item, 'order_number') === $search)
            ?? $orders->first();

        if (!$order) {
            return response()->json([
                'message' => $serviceOnly
                    ? 'No ESR service found for that number.'
                    : ($salesOnly
                        ? 'No ESR sale order found for that number.'
                        : 'No ESR order found for that number.'),
            ], 404);
        }

        $glassType = Str::upper((string) data_get($order, 'glass_type', ''));
        $orderType = Str::lower((string) data_get($order, 'order_type', ''));
        $serviceValue = Str::lower((string) data_get($order, 'service', ''));
        $accountManagerEmail = Str::lower(trim((string) data_get($order, 'account_manager.email', '')));
        $companyEmail = Str::lower(trim((string) data_get($order, 'company.email', '')));
        $companyPhone = trim((string) data_get($order, 'company.phone', ''));
        $normalizedCompanyPhone = $this->normalizePhone($companyPhone);
        $owner = $accountManagerEmail !== ''
            ? User::assignableOrderOwner()
                ->select('id', 'name', 'email')
                ->where('status', StatusUserEnum::ACTIVE->value)
                ->when(
                    $this->isRestrictedOwner(),
                    fn ($query) => $query->where('id', auth()->id())
                )
                ->whereRaw('LOWER(email) = ?', [$accountManagerEmail])
                ->first()
            : null;
        $company = $companyEmail !== ''
            ? CompanyContact::visibleTo(auth()->user())
                ->select('id', 'name', 'email', 'phone')
                ->whereRaw('LOWER(email) = ?', [$companyEmail])
                ->first()
            : null;

        if (!$company && $normalizedCompanyPhone !== '') {
            $company = CompanyContact::visibleTo(auth()->user())
                ->select('id', 'name', 'email', 'phone')
                ->whereNotNull('phone')
                ->get()
                ->first(fn (CompanyContact $item) => $this->normalizePhone($item->phone) === $normalizedCompanyPhone);
        }

        return response()->json([
            'order' => [
                'name' => data_get($order, 'name'),
                'order_number' => (string) data_get($order, 'order_number', ''),
                'project_amount' => data_get($order, 'amount'),
                'esr_express' => $glassType === 'EXPRESS',
                'esr_reylos_glass' => (int) data_get($order, 'company.id') === 68,
                'esr_service' => Str::contains($orderType . ' ' . $serviceValue, 'service'),
                'owner_id' => $owner?->id,
                'account_manager_email' => $accountManagerEmail !== '' ? $accountManagerEmail : null,
                'company_contact_id' => $company?->id,
                'company_email' => $companyEmail !== '' ? $companyEmail : null,
                'company_phone' => $companyPhone !== '' ? $companyPhone : null,
            ],
        ]);
    }

    public function bosOrderPrefill(Order $order, OrderClientEmailManager $emailManager): JsonResponse
    {
        if ($this->isRestrictedOwner() && !$order->isAccessibleToOwner(auth()->user())) {
            return response()->json([
                'message' => 'You are not authorized to access this order.',
            ], 403);
        }

        if (
            $order->parent_order_id !== null
            || $order->is_post_sale_service
            || filled($order->service_origin)
            || $order->esr_service
        ) {
            return response()->json([
                'message' => 'Only original BOS orders can be associated to an ESW service.',
            ], 422);
        }

        $order->load([
            'client.companyContacts:id,name,email',
            'owners:id,name',
            'orderCompanyContacts.companyContact:id,name,email',
            'orderCompanyContacts.client:id,name,email,phone,other_phone,secondary_email,company_contact_id',
            'orderCompanyContacts.source:id,name',
            'paymentSchedule.installments',
        ]);

        $selectedContact = $order->orderCompanyContacts
            ->firstWhere('is_selected', true)
            ?? ($order->orderCompanyContacts->count() === 1 ? $order->orderCompanyContacts->first() : null);

        return response()->json([
            'order' => [
                'id' => $order->id,
                'original_order_number' => $order->order_number,
                'name' => $order->name,
                'product_line' => $order->product_line,
                'service' => $order->service,
                'project_amount' => $order->project_amount,
                'job_address' => $order->job_address,
                'city' => $order->city,
                'job_state' => $order->job_state,
                'job_zip' => $order->job_zip,
                'method_of_payment' => $order->method_of_payment,
                'type_of_financing' => $order->type_of_financing,
                'down_payment' => $order->down_payment,
                'payment_schedule_type' => $order->paymentSchedule?->schedule_type,
                'client_id' => $selectedContact?->client_id ?? $order->client_id,
                'company_contact_id' => $selectedContact?->company_contact_id,
                'client_email_selection' => $emailManager->selectionForOrder($order),
                'owner_ids' => $order->owners->pluck('id')->values()->all(),
                'company_pairs' => $order->orderCompanyContacts
                    ->map(fn (OrderCompanyContact $contact) => [
                        'company_contact_id' => $contact->company_contact_id,
                        'client_id' => $contact->client_id ?: $order->client_id,
                        'source_id' => $contact->source_id,
                        'is_selected' => (bool) $contact->is_selected,
                    ])
                    ->values()
                    ->all(),
            ],
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
        $parentOrder = null;
        if (! empty($validated['parent_order_id'])) {
            $parentOrder = Order::query()
                ->when(
                    $this->isRestrictedOwner(),
                    fn ($query) => $query->accessibleToOwner($request->user())
                )
                ->findOrFail((int) $validated['parent_order_id']);
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

        DB::transaction(function () use ($request, $validated, $ownerIds, $emailManager, $client, $clientCompanyContactManager, $crmNotificationService, $parentOrder) {
            $sourceId = Source::firstOrCreate([
                'name' => ContactSourceEnum::ESR_REFER->value,
            ])->id;

            $order = Order::create([
                'client_id' => $validated['client_id'],
                'parent_order_id' => $parentOrder?->id,
                'root_order_id' => $parentOrder ? ($parentOrder->root_order_id ?: $parentOrder->id) : null,
                'user_id' => auth()->id(),
                'order_type' => $validated['order_type'],
                'product_line' => $validated['product_line'],
                'service' => $validated['service'] ?? null,
                'esr_design' => $request->boolean('esr_design'),
                'esr_express' => $request->boolean('esr_express'),
                'esr_reylos_glass' => $request->boolean('esr_reylos_glass'),
                'esr_service' => $request->boolean('esr_service'),
                'service_origin' => $request->boolean('esr_service') ? 'OWNER' : null,
                'is_post_sale_service' => false,
                'method_of_payment' => $validated['method_of_payment'] ?? null,
                'type_of_financing' => in_array(($validated['method_of_payment'] ?? null), [
                    MethodOfPayment::FINANCED->value,
                    MethodOfPayment::FINANCEDCASH->value,
                ], true) ? ($validated['type_of_financing'] ?? null) : null,
                'down_payment' => ($validated['method_of_payment'] ?? null) === MethodOfPayment::FINANCEDCASH->value
                    ? ($validated['down_payment'] ?? null)
                    : null,
                'name' => $validated['name'],
                'job_address' => $validated['job_address'] ?? null,
                'city' => $validated['city'] ?? null,
                'job_state' => $validated['job_state'] ?? null,
                'job_zip' => $validated['job_zip'] ?? null,
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

            if ($request->boolean('esr_service')) {
                $this->createInitialServiceControlForEsrOrder($order, $validated);
            }

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

    public function storeService(
        StoreEsrOrderRequest $request,
        OrderClientEmailManager $emailManager,
        ClientCompanyContactManager $clientCompanyContactManager,
        CrmNotificationService $crmNotificationService
    ): RedirectResponse {
        return $this->store($request, $emailManager, $clientCompanyContactManager, $crmNotificationService);
    }

    private function createInitialServiceControlForEsrOrder(Order $order, array $validated): void
    {
        $serviceControl = ServiceControl::create([
            'order_id' => $order->id,
            'service_name' => $order->name,
            'service_id' => $order->order_number,
            'is_bm' => false,
            'service_source' => $validated['service_source'] ?? ServiceControlSourceEnum::ESR->value,
            'creation_source' => ServiceControlCreationSourceEnum::MANUAL->value,
            'request_origin' => ServiceControlRequestOriginEnum::OWNER->value,
            'service_type' => [ServiceControlTypeEnum::GLASS->value],
            'description' => null,
            'requires_part' => false,
            'requested_parts' => false,
            'parts_available' => false,
            'service_status' => ServiceControlStatusEnum::ORDER_IN_REVIEW->value,
            'priority' => ServiceControlPriorityEnum::MEDIUM->value,
            'service_created_date' => now()->format('Y-m-d'),
            'opened_at' => now()->format('Y-m-d'),
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        $serviceControl->histories()->create([
            'user_id' => auth()->id(),
            'event_type' => 'CREATED',
            'summary' => 'Service control created from ESR service order.',
            'new_values' => [
                'order_id' => $order->id,
                'service_name' => $serviceControl->service_name,
                'service_id' => $serviceControl->service_id,
                'service_source' => $serviceControl->service_source,
                'creation_source' => $serviceControl->creation_source,
                'request_origin' => $serviceControl->request_origin,
                'service_status' => $serviceControl->service_status,
                'priority' => $serviceControl->priority,
            ],
        ]);
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
        if ($order->is_post_sale_service && $order->service_origin === ServiceControlRequestOriginEnum::SERVICE->value) {
            return response()->json([
                'message' => 'Post-sale services cannot be deleted from the ESR PROCESS pipeline.',
            ], 422);
        }

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
            OrderStatusEnum::SERVICE_IN_REVIEW->value,
            OrderStatusEnum::ACCOUNT_RECEIPT->value,
            OrderStatusEnum::PRODUCTION->value,
            OrderStatusEnum::PENDING_GLASS_INVOICE->value,
            OrderStatusEnum::PRODUCTION_SERVICES->value,
            OrderStatusEnum::PRE_COORDINATION_ACCOUNTING->value,
            OrderStatusEnum::PENDING_MAT_REYLOS->value,
            OrderStatusEnum::PENDING_MATERIALS->value,
            OrderStatusEnum::PENDING_MATERIALS_EWS->value,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED->value,
            OrderStatusEnum::MATERIAL_ORDER_COMPLETED_FINANCED->value,
            OrderStatusEnum::STORAGE_MATERIAL->value,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED_FINANCED->value,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED_BACKORDER->value,
            OrderStatusEnum::MATERIALS_PICK_UP_OR_DELIVERED->value,
            OrderStatusEnum::PENDING_PAYMENT->value,
            OrderStatusEnum::COMPLETE->value,
            OrderStatusEnum::LOST->value,
        ];
    }

    private function createOrderStatuses(): array
    {
        return [
            OrderStatusEnum::DEALER_REQUEST->value,
            OrderStatusEnum::FOLLOW_UP_PROJECTS->value,
            OrderStatusEnum::REVIEW->value,
        ];
    }

    protected function paginatedStorageStatuses(): array
    {
        return $this->storageStatuses();
    }

    protected function orderStoragePageSize(): int
    {
        return 10;
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

    protected function showNewService(): bool
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

    protected function tracksStageOverdues(): bool
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
