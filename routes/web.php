<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\BiginController;
use App\Http\Controllers\BiweeklyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Commission\CommissionPeriodController;
use App\Http\Controllers\Commission\CommissionHistoryController;
use App\Http\Controllers\Commission\CommissionReportController;
use App\Http\Controllers\CompanyContactController;
use App\Http\Controllers\AuthorizeNetHostedPaymentController;
use App\Http\Controllers\AuthorizeNetWebhookController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\CrmNotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\EsrProcessController;
use App\Http\Controllers\FrontdeskController;
use App\Http\Controllers\InstallationTeamController;
use App\Http\Controllers\NoteAudioController;
use App\Http\Controllers\OrderNoteController;
use App\Http\Controllers\OrderProcessingController;
use App\Http\Controllers\OrderStorageController;
use App\Http\Controllers\OrderSearchController;
use App\Http\Controllers\OrderPaymentController;
use App\Http\Controllers\OverdueReportEmailScheduleController;
use App\Http\Controllers\PaymentInstallmentMovementController;
use App\Http\Controllers\PaymentScheduleController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\ServiceControlController;
use App\Http\Controllers\StockMaterialController;
use App\Http\Controllers\SourceController;
use App\Models\Biweekly;
use Barryvdh\DomPDF\Facade\Pdf;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
use App\Traits\TwilioWhatsAppMessage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::post('/webhook/authorize-net/payments', [AuthorizeNetWebhookController::class, 'payments'])
  ->name('authorize-net.webhooks.payments');

Route::get('/payments/authorize-net/complete', [AuthorizeNetHostedPaymentController::class, 'complete'])
  ->name('authorize-net.payments.complete');

Route::get('/payments/authorize-net/cancel', [AuthorizeNetHostedPaymentController::class, 'cancel'])
  ->name('authorize-net.payments.cancel');

Route::get('/payments/authorize-net/intent/{token}', [AuthorizeNetHostedPaymentController::class, 'showIntent'])
  ->name('authorize-net.payments.intent.show');

Route::get('/payments/authorize-net/{paymentType}/{paymentId}', [AuthorizeNetHostedPaymentController::class, 'show'])
  ->middleware(['auth', 'role:admin'])
  ->whereIn('paymentType', ['quota', 'change-order'])
  ->name('authorize-net.payments.show');

Route::get('/pdf', function () {
  $order = App\Models\Order::with([
    'orderProducts.productCategory',
    'orderProducts.productConfig',
    'orderProducts.orderProductExtraWorks',
  ])->find(33);

  //dd($order);
  /*$pdf = Pdf::loadView('pdf.payment-list', ['order' => $order]);
  $pdfName = 'payment-list-' . $order->order_number . '.pdf';
  $pdf->save('../storage/app/public/pdf/' . $pdfName);
  echo 'pdf salvado';*/
  return view('pdf.payment-list', ['order' => $order]);
});

/*Route::get('/mailable', function () {
  $order = App\Models\Order::with(['orderProducts'])->find(69);

  dd($order->orderProducts->where('product_category_id', 2)->sum('qty'));
  //dd($order);
  // Mail::to('efrain@reylosglass.com', 'Efrain')->send(new App\Mail\EstimateCreated($order, [RoleEnum::$DEALER]));
  return new App\Mail\EmailAccounting($order);
});*/


    // ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER]);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/crm-notifications', [CrmNotificationController::class, 'index'])->name('crm-notifications.index');
    Route::post('/crm-notifications/read-all', [CrmNotificationController::class, 'markAllRead'])->name('crm-notifications.read-all');
    Route::post('/crm-notifications/{notification}/read', [CrmNotificationController::class, 'markRead'])->name('crm-notifications.read');

    Route::post('/notes/{note}/audio', [NoteAudioController::class, 'store'])->name('notes.audio.store');
    Route::get('/notes/{note}/audio/{attachment}', [NoteAudioController::class, 'show'])->name('notes.audio.show');
    Route::delete('/notes/{note}/audio/{attachment}', [NoteAudioController::class, 'destroy'])->name('notes.audio.destroy');

    // USERS
    /* Route::resource('user', UserController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER]); */

    /*Route::get('myphpinfo', function() {
      phpinfo();
    })->name('myphpinfo');*/

     Route::prefix('order/{order}')->name('order.')->group(function () {
        Route::get('notes',  [OrderNoteController::class, 'index'])->name('notes.index');
        Route::post('notes', [OrderNoteController::class, 'store'])->name('notes.store');
        Route::put('notes/{note}',    [OrderNoteController::class, 'update'])->name('notes.update');
        Route::delete('notes/{note}', [OrderNoteController::class, 'destroy'])->name('notes.destroy');
    });

    Route::get('user/referred-clients', [UserController::class, 'referredClients'])
      ->name('user.referred-clients');

    Route::resource('user', UserController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ]);

    Route::get('/administration/overdue-report-email-schedule', [OverdueReportEmailScheduleController::class, 'edit'])
      ->middleware(["role:" . RoleEnum::ADMIN->value])
      ->name('administration.overdue-report-email-schedule.edit');

    Route::put('/administration/overdue-report-email-schedule', [OverdueReportEmailScheduleController::class, 'update'])
      ->middleware(["role:" . RoleEnum::ADMIN->value])
      ->name('administration.overdue-report-email-schedule.update');

      Route::get('company_contact', [CompanyContactController::class, 'index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
      ->name('company_contact.index');
      Route::resource('company_contact', CompanyContactController::class)
      ->except(['index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]);
    
    Route::get('order/create-service', [OrderController::class, 'createService'])
      ->name('order.create_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::post('order/service', [OrderController::class, 'storeService'])
      ->name('order.store_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::put('order/{order}/service', [OrderController::class, 'updateService'])
      ->name('order.update_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::get('service-control/pdf', [ServiceControlController::class, 'pdf'])
      ->name('service-control.pdf')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::get('service-control/excel', [ServiceControlController::class, 'excel'])
      ->name('service-control.excel')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::get('service-control/calendar', [ServiceControlController::class, 'calendar'])
      ->name('service-control.calendar')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::SERVICE->value]);

    Route::get('service-control/calendar/events/{year}/{month}', [ServiceControlController::class, 'calendarEvents'])
      ->name('service-control.calendar.events')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::SERVICE->value]);

    Route::get('service-control/clients/search', [ServiceControlController::class, 'searchClients'])
      ->name('service-control.clients.search')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::get('service-control/external-service-orders/search', [ServiceControlController::class, 'searchExternalServiceOrders'])
      ->name('service-control.external-service-orders.search')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::post('service-control/{serviceControl}/attachments', [ServiceControlController::class, 'storeAttachment'])
      ->name('service-control.attachments.store')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::delete('service-control/{serviceControl}/attachments/{attachment}', [ServiceControlController::class, 'dropAttachment'])
      ->name('service-control.attachments.destroy')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::resource('service-control', ServiceControlController::class)
      ->except(['show'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::get('service-control/{serviceControl}', [ServiceControlController::class, 'show'])
      ->name('service-control.show')
      ->middleware(["role:" . implode('|', array_map(fn (RoleEnum $role) => $role->value, RoleEnum::cases()))]);

    Route::get('stock-material/pdf', [StockMaterialController::class, 'pdf'])
      ->name('stock-material.pdf')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]);

    Route::get('stock-material/excel', [StockMaterialController::class, 'excel'])
      ->name('stock-material.excel')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]);

    Route::resource('stock-material', StockMaterialController::class)
      ->except(['show'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]);

    Route::put('order/{order}/status-only', [OrderController::class, 'updateStatusOnly'])
      ->name('order.update_status_only')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|FRONTDESK_ADMIN|frondesk_admin|frondestk_admin|FRONDESK_ADMIN|FRONDESTK_ADMIN'] );

    Route::resource('order', OrderController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|FRONTDESK_ADMIN|frondesk_admin|frondestk_admin|FRONDESK_ADMIN|FRONDESTK_ADMIN'] );

    Route::get('orders/search', [OrderSearchController::class, 'index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value . '|' . RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value . '|' . RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::SERVICE->value . '|' . RoleEnum::PRODUCTION->value])
      ->name('order.search');

    Route::get('order/get_delivery_and_installation_date/{payment_factory_date}/{type_of_housing}/{county_id}/{service}/{hasPermit}', [OrderController::class, 'getDeliveryAndInstallationDate'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value]);
    
    Route::get('order/get_delivery_and_pickup_date/{payment_factory_date}', [OrderController::class, 'getDeliveryAndPickupDate'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value]);
    
    Route::post('order/update-date-paid', [OrderController::class, 'updateDatePaid'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value])
      ->name('order.update_date_paid');

      Route::post('order/update-status-payment', [OrderController::class, 'updateStatusPayment'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value])
      ->name('order.update-status-payment');

      Route::post('order/supervisor-close-all', [OrderController::class, 'supervisorCloseAll'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value])
      ->name('order.supervisor-close-all');
    
    Route::get('dashboard/get_events/{year}/{month}/{service}/{status}/{name?}', [DashboardController::class, 'getEvents'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
      ->name('dashboard.get_events');
    
    Route::get('dashboard/get_event/{order}', [DashboardController::class, 'getEvent'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
      ->name('dashboard.get_event');
    
    Route::post('order/update-from-modal/{order}', [OrderController::class, 'updateFromModal'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
      ->name('update.order.from.modal');
    
    Route::get('order/get_payment_list/{order}', [DashboardController::class, 'getPaymentList'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('order.get_payment_list');

      Route::get('order/get_supervisor_list/{order}', [DashboardController::class, 'getSupervisorList'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SUPERVISOR->value])
      ->name('order.get_supervisor_list');
    
    Route::put('dashboard/update_events/{id}', [DashboardController::class, 'updateEvent'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value])
      ->name('dashboard.update_event');

    Route::post('order/{order}/attachments', [OrderController::class, 'storeAttachment'])
      ->middleware('auth')
      ->name('order.attachments.store');

    Route::delete('order/drop_attachment/{id}', [OrderController::class, 'dropAttachment'])
      ->middleware('auth')
      ->name('order.drop_attachment');

      Route::delete('report/drop_payment/{id}', [ReportController::class, 'dropPayment'])
      // ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|other_roles'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::PAYMENT_COORDINATOR->value ]) 
      ->name('report.drop_payment');
    
      Route::get('/order/status_order/{id}', [OrderController::class, 'statusOrder'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value] )
      ->name('order.status_order');

    Route::resource('installation_team', InstallationTeamController::class)
      ->only(['index', 'create', 'store', 'update', 'edit', 'destroy'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value]);

      Route::get('/order/duplicate/{id}', [OrderController::class, 'duplicate'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ] )
      ->name('order.duplicate');

    // CLIENTS
    Route::get('client/search', [ClientController::class, 'search'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value ])
      ->name('client.search');
    Route::get('users/search-referrers', [UserController::class, 'searchReferrers'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value ])
      ->name('user.referrers.search');
    Route::get('referral/search', [ReferralController::class, 'search'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value ])
      ->name('referral.search');
    Route::get('client/is_unique/{phone}/{address?}', [ClientController::class, 'isUnique'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value ]);
    Route::get('client/phone-exists', [ClientController::class, 'phoneExists'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value ])
      ->name('client.phone_exists');
    Route::get('client', [ClientController::class, 'index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . "|" . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value ])
      ->name('client.index');
    Route::resource('client', ClientController::class)
      ->except(['index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . "|" . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value ]);
    Route::get('client/document/{id}', [ClientController::class, 'document'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value ])
      ->name('client.document');

    Route::prefix('activities')->name('activities.')->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value . '|' . RoleEnum::SUPERVISOR->value])->group(function () {
      Route::get('/', [ActivityController::class, 'index'])->name('index');
      Route::get('calendar/events/{year}/{month}', [ActivityController::class, 'calendarEvents'])->name('calendar.events');
      Route::get('context', [ActivityController::class, 'context'])->name('context');
      Route::get('orders/search', [ActivityController::class, 'searchOrders'])->name('orders.search');
      Route::get('clients/search', [ActivityController::class, 'searchClients'])->name('clients.search');
      Route::get('users/search', [ActivityController::class, 'searchUsers'])->name('users.search');
      Route::get('events/{event}', [ActivityController::class, 'showEvent'])->name('events.show');
      Route::get('events/{event}/notes', [ActivityController::class, 'eventNotes'])->name('events.notes.index');
      Route::post('events/{event}/notes', [ActivityController::class, 'storeEventNote'])->name('events.notes.store');
      Route::put('events/{event}/notes/{note}', [ActivityController::class, 'updateEventNote'])->name('events.notes.update');
      Route::delete('events/{event}/notes/{note}', [ActivityController::class, 'destroyEventNote'])->name('events.notes.destroy');
      Route::post('events', [ActivityController::class, 'storeEvent'])->name('events.store');
      Route::put('events/{event}', [ActivityController::class, 'updateEvent'])->name('events.update');
      Route::get('calls/{call}', [ActivityController::class, 'showCall'])->name('calls.show');
      Route::get('calls/{call}/notes', [ActivityController::class, 'callNotes'])->name('calls.notes.index');
      Route::post('calls/{call}/notes', [ActivityController::class, 'storeCallNote'])->name('calls.notes.store');
      Route::put('calls/{call}/notes/{note}', [ActivityController::class, 'updateCallNote'])->name('calls.notes.update');
      Route::delete('calls/{call}/notes/{note}', [ActivityController::class, 'destroyCallNote'])->name('calls.notes.destroy');
      Route::post('calls', [ActivityController::class, 'storeCall'])->name('calls.store');
      Route::put('calls/{call}', [ActivityController::class, 'updateCall'])->name('calls.update');
    });

    //BIGIN
    Route::get('/bigin/callback', [BiginController::class, 'callback'])
      ->middleware(['role:admin'])
      ->name('bigin.callback');

    Route::get('/bigin/index', [BiginController::class, 'index'])
      ->middleware(['role:admin'])
      ->name('bigin.index');

    Route::get('/dashboard/whatsapp', [DashboardController::class, 'whatsapp'])
      ->middleware(['role:admin'])
      ->name('dashboard.whatsapp');

      Route::get('/report/supervisor', [ReportController::class, 'supervisor'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('report.supervisor');

      Route::get('/report/installer', [ReportController::class, 'installer'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.installer');

      Route::get('/report/show_installer/{id?}', [ReportController::class, 'showInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('report.show_installer');

      Route::get('report/get-payment-list-installer/{id}/{biweekly}', [ReportController::class, 'getPaymentListInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.get-payment-list-installer');

      /* Route::get('/report/show_biweekly/{id}', [ReportController::class, 'showBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.show_biweekly');

      Route::get('/report/create_biweekly/{installation_team}', [ReportController::class, 'createBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.create_biweekly');

      Route::post('report/store_biweekly', [ReportController::class, 'storeBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.store_biweekly');
     
      Route::post('report/update_biweekly', [ReportController::class, 'updateBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.update_biweekly');

      Route::get('/report/edit_biweekly/{id}/{installation_team}', [ReportController::class, 'editBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.edit_biweekly'); */
      Route::resource('biweekly', BiweeklyController::class)
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value ]);

      Route::resource('frontdesk', FrontdeskController::class)->except(['show'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value  ]);

      Route::get('/frontdesk/tasks', [FrontdeskController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value ])
        ->name('frontdesk.tasks');

      Route::get('sales/calendar', [SalesController::class, 'calendar'])->name('sales.calendar');
      Route::get('sales/calendar/events/{year}/{month}', [SalesController::class, 'calendarEvents'])->name('sales.calendar.events');
      Route::get('/sales', [SalesController::class, 'index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('sales.index');
      Route::get('/sales/tasks', [SalesController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('sales.tasks');
      Route::resource('sales', SalesController::class)
        ->except(['index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value ]);
      Route::get('order-processing', [OrderProcessingController::class, 'index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value ])
        ->name('order-processing.index');
      Route::get('order-processing/tasks', [OrderProcessingController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('order-processing.tasks');
      Route::get('order-storage', [OrderStorageController::class, 'index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|FRONTDESK_ADMIN'])
        ->name('order-storage.index');
      Route::get('order-storage/tasks', [OrderStorageController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|FRONTDESK_ADMIN'])
        ->name('order-storage.tasks');
      Route::get('esr-process', [EsrProcessController::class, 'index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value])
        ->name('esr-process.index');
      Route::get('esr-process/tasks', [EsrProcessController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value])
        ->name('esr-process.tasks');
      Route::get('esr-process/orders/{order}/edit-data', [EsrProcessController::class, 'editData'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value])
        ->name('esr-process.orders.edit-data');
      Route::get('esr-process/create-order', [EsrProcessController::class, 'create'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.create-order');
      Route::get('esr-process/create-service', [EsrProcessController::class, 'createService'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.create-service');
      Route::post('esr-process/services', [EsrProcessController::class, 'storeService'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.store-service');
      Route::get('esr-process/orders/search-external', [EsrProcessController::class, 'searchExternalOrder'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.orders.search-external');
      Route::get('esr-process/orders/{order}/prefill', [EsrProcessController::class, 'bosOrderPrefill'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.orders.prefill');
      Route::get('esr-process/orders/{id}', [FrontdeskController::class, 'orderView'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value])
        ->name('esr-process.order-view');
      Route::post('esr-process/orders', [EsrProcessController::class, 'store'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.store-order');
      Route::post('esr-process/companies', [CompanyContactController::class, 'store'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.companies.store');
      Route::post('esr-process/clients', [ClientController::class, 'store'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.clients.store');
      Route::delete('esr-process/orders/{order}', [EsrProcessController::class, 'destroy'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
        ->name('esr-process.destroy-order');

      Route::get('/frontdesk/orders/{order}/sale-form', [FrontdeskController::class, 'saleFormPdf'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value  ])
        ->name('frontdesk.order.sale_form');

      Route::post('/sales/{order}/assign-estimate', [SalesController::class, 'assignEstimate'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('sales.assign_estimate');

      Route::post('/sales/{order}/assign-follow-up', [SalesController::class, 'assignFollowUp'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value. '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_follow_up');

      Route::post('/sales/{order}/assign-stand-by', [SalesController::class, 'assignStandBy'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_stand_by');

      Route::post('/sales/{order}/assign-request-reschedule', [SalesController::class, 'assignRequestReschedule'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_request_reschedule');

      Route::post('/sales/{order}/assign-pre-contract', [SalesController::class, 'assignPreContract'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_pre_contract');

      Route::post('/sales/{order}/assign-contract-signed', [SalesController::class, 'assignContractSigned'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_contract_signed');

      Route::patch('/payment-installments/{installment}', [PaymentScheduleController::class, 'updateInstallment'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('payment_installments.update');

      Route::post('/payment-installments/{installment}/movements', [PaymentInstallmentMovementController::class, 'store'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('payment_installment_movements.store');

      Route::patch('/payment-installment-movements/{movement}', [PaymentInstallmentMovementController::class, 'update'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('payment_installment_movements.update');

      Route::post('/payment-installment-movements/{movement}/void', [PaymentInstallmentMovementController::class, 'void'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('payment_installment_movements.void');

      Route::patch('/order-payments/{orderPayment}', [OrderPaymentController::class, 'update'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('order_payments.update');

      Route::post('/sales/{order}/assign-lost-contract', [SalesController::class, 'assignLostContract'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_lost_contract');

      Route::delete('/sales/{order}/delete-order', [SalesController::class, 'destroyOrder'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value])
        ->name('sales.destroy_order');

    Route::resource('source', SourceController::class)
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value ]);

      Route::get('/report/edit_report_installer/{id}/{installation_team}', [ReportController::class, 'editReportInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.edit_report_installer');

      Route::post('report/update_installer_report', [ReportController::class, 'updateInstallerReport'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.update_installer_report');

      Route::post('report/update_installer_payment', [ReportController::class, 'updateInstallerPayment'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
      ->name('report.update_installer_payment');

      Route::get('/report/excel-installer/{id}/{biweekly}', [ReportController::class, 'exportPaymentInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.excel-installer');

      Route::get('/report/biweekly-payment/{id}/{biweekly}', [ReportController::class, 'biweeklyPayment'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.payment');

      Route::get('/report/send-payment-installer/{id}/{biweekly}', [ReportController::class, 'sendPaymentInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.send-payment-installer');

      Route::get('/report/send-paid-installer/{id}/{biweekly}', [ReportController::class, 'sendPaidInstaller'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.send-paid-installer');

      Route::get('/report/show_supervisor/{id}', [ReportController::class, 'showSupervisor'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('report.show_supervisor');

      Route::get('/report/show-supervisor-report/{id}', [ReportController::class, 'showSupervisorReport'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('report.show-supervisor-report');

      Route::get('/report/excel-supervisor/{user}', [ReportController::class, 'export'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value] )
      ->name('report.excel-supervisor');

      Route::get('/report/excel-supervisor-filter/{id}', [ReportController::class, 'exportPaymentSupervisor'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
      ->name('report.excel-installer-filter');

      Route::get('/biweekly/show-installer-biweekly/{id}', [BiweeklyController::class, 'showInstallerBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-installer-biweekly');

      Route::get('/biweekly/show-pdf-biweekly/{biweeklyId}/{installerId}', [BiweeklyController::class, 'showPdfBiweekly'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-pdf-biweekly');
      

      Route::get('/biweekly/show-pdf-biweekly-payment/{installerId}{biweeklyId}', [BiweeklyController::class, 'showPdfBiweeklyPayment'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value  . '|' . RoleEnum::PAYMENT_COORDINATOR->value.'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-pdf-biweekly-payment');

      Route::get('/biweekly/export-biweekly-payment/{biweeklyId}/{installerId}', [BiweeklyController::class, 'exportBiweeklyPayment'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value  . '|' . RoleEnum::PAYMENT_COORDINATOR->value.'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.export-biweekly-payment');

      Route::get('/biweekly/show-pdf-biweekly-payment-resumen/{installerId}{biweeklyId}', [BiweeklyController::class, 'showPdfBiweeklyPaymentResumen'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-pdf-biweekly-payment-resumen');
      
     Route::get('/download/{id}', [DownloadController::class, 'secureDownload'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value.'|' . RoleEnum::INSTALLER->value.'|' . RoleEnum::OWNER->value .'|' . RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value] )
      ->name('download.file');

      Route::get('/download/image-download/{id}', [DownloadController::class, 'imageDownload'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value] )
      ->name('download.image-download');

      Route::get('/biweekly/show-pdf-biweekly-payment-resumen-general/{biweeklyId}', [BiweeklyController::class, 'showPdfBiweeklyPaymentResumenGeneral'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-pdf-biweekly-payment-resumen-general');

      Route::get('/biweekly/show-pdf-biweekly-payment-extra-work/{biweeklyId}', [BiweeklyController::class, 'showPdfBiweeklyPaymentExtraWork'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.show-pdf-biweekly-payment-extra-work');

      Route::get('/biweekly/uncollected-customer-payments-report/{biweeklyId}', [BiweeklyController::class, 'uncollectedCustomerPaymentsReport'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.uncollected-customer-payments-report');

      Route::get('/biweekly/uncollected-customer-payments-report-excel/{biweeklyId}', [BiweeklyController::class, 'exportUncollectedCustomerPaymentsReport'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value] )
      ->name('biweekly.uncollected-customer-payments-report-excel');


    Route::get('/report/product-summary', [ReportController::class, 'productSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.product-summary');

    Route::get('/report/order-status-summary', [ReportController::class, 'orderStatusSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.order-status-summary');

    Route::get('/report/planned-to-complete-average', [ReportController::class, 'plannedToCompleteAverage'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.planned-to-complete-average');

    Route::get('/report/planned-to-complete-average/pdf', [ReportController::class, 'plannedToCompleteAveragePdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value ] )
    ->name('report.planned-to-complete-average-pdf');

    Route::get('/report/planned-to-complete-average/excel', [ReportController::class, 'plannedToCompleteAverageExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.planned-to-complete-average-excel');

    Route::get('/report/accounting-status-summary', [ReportController::class, 'accountingStatusSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value] )
    ->name('report.accounting-status-summary');

    Route::get('/report/accounting-status-summary/pdf', [ReportController::class, 'accountingStatusSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value] )
    ->name('report.accounting-status-summary-pdf');

    Route::get('/report/accounting-status-summary/excel', [ReportController::class, 'accountingStatusSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value] )
    ->name('report.accounting-status-summary-excel');

    Route::get('/report/commissions', [CommissionReportController::class, 'index'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions');

    Route::get('/report/commissions/pdf', [CommissionReportController::class, 'pdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.pdf');

    Route::get('/report/commissions/excel', [CommissionReportController::class, 'excel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.excel');

    Route::get('/report/commissions/history', [CommissionHistoryController::class, 'index'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value])
    ->name('report.commissions.history');

    Route::get('/report/commissions/history/{commission}', [CommissionHistoryController::class, 'show'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value])
    ->name('report.commissions.history.show');

    Route::get('/report/commissions/paid-history', [CommissionHistoryController::class, 'paidHistory'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value])
    ->name('report.commissions.paid-history');

    Route::get('/report/commissions/order/{order}', [CommissionReportController::class, 'editOrder'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.edit-order');

    Route::post('/report/commissions', [CommissionReportController::class, 'storeCommission'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.store');

    Route::patch('/report/commissions/{commission}', [CommissionReportController::class, 'updateCommission'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.update');

    Route::delete('/report/commissions/{commission}', [CommissionReportController::class, 'destroyCommission'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.destroy');

    Route::post('/report/commissions/{commission}/payments', [CommissionReportController::class, 'storePayment'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.payments.store');

    Route::patch('/report/commission-payments/{payment}', [CommissionReportController::class, 'updatePayment'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.payments.update');

    Route::delete('/report/commission-payments/{payment}', [CommissionReportController::class, 'destroyPayment'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.payments.destroy');

    Route::post('/report/commission-payments/bulk-pay', [CommissionReportController::class, 'bulkPay'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('report.commissions.payments.bulk-pay');

    Route::get('/commission-periods', [CommissionPeriodController::class, 'index'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.index');

    Route::post('/commission-periods', [CommissionPeriodController::class, 'store'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.store');

    Route::patch('/commission-periods/{commissionPeriod}', [CommissionPeriodController::class, 'update'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.update');

    Route::delete('/commission-periods/{commissionPeriod}', [CommissionPeriodController::class, 'destroy'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.destroy');

    Route::get('/commission-periods/{commissionPeriod}', [CommissionPeriodController::class, 'show'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.show');

    Route::get('/commission-periods/{commissionPeriod}/pdf', [CommissionPeriodController::class, 'pdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.pdf');

    Route::get('/commission-periods/{commissionPeriod}/excel', [CommissionPeriodController::class, 'excel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.excel');

    Route::delete('/commission-periods/{commissionPeriod}/payments/{payment}', [CommissionPeriodController::class, 'unassignPayment'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.payments.unassign');

    Route::post('/commission-periods/{commissionPeriod}/close', [CommissionPeriodController::class, 'close'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.close');

    Route::post('/commission-periods/{commissionPeriod}/reopen', [CommissionPeriodController::class, 'reopen'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNTING->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value])
    ->name('commission-periods.reopen');

    Route::get('/report/daily-order-status-summary', [ReportController::class, 'dailyOrderStatusSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary');

    Route::get('/report/daily-order-status-summary/pdf', [ReportController::class, 'dailyOrderStatusSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary-pdf');

    Route::get('/report/daily-order-status-summary/excel', [ReportController::class, 'dailyOrderStatusSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary-excel');

    Route::get('/report/overdue-stage-orders', [ReportController::class, 'overdueStageOrders'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value] )
    ->name('report.overdue-stage-orders');

    Route::get('/report/overdue-stage-orders/pdf', [ReportController::class, 'overdueStageOrdersPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value] )
    ->name('report.overdue-stage-orders-pdf');

    Route::get('/report/overdue-stage-orders/excel', [ReportController::class, 'overdueStageOrdersExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value] )
    ->name('report.overdue-stage-orders-excel');

    Route::get('/report/marketing', [ReportController::class, 'marketingReport'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.marketing');

    Route::get('/report/marketing/pdf', [ReportController::class, 'marketingReportPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.marketing-pdf');

    Route::get('/report/marketing/excel', [ReportController::class, 'marketingReportExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.marketing-excel');

    Route::get('/report/sales-appointments-by-seller', [ReportController::class, 'salesAppointmentsBySeller'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.sales-appointments-by-seller');

    Route::get('/report/sales-appointments-by-seller/pdf', [ReportController::class, 'salesAppointmentsBySellerPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.sales-appointments-by-seller-pdf');

    Route::get('/report/sales-appointments-by-seller/excel', [ReportController::class, 'salesAppointmentsBySellerExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.sales-appointments-by-seller-excel');

    Route::get('/report/installer-confirmed-summary', [ReportController::class, 'installerConfirmedSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary');

    Route::get('/report/installer-confirmed-summary/pdf', [ReportController::class, 'installerConfirmedSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary-pdf');

    Route::get('/report/installer-confirmed-summary/excel', [ReportController::class, 'installerConfirmedSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary-excel');

    Route::get('/report/owner-assigned-summary', [ReportController::class, 'ownerAssignedSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.owner-assigned-summary');

    Route::get('/report/owner-assigned-summary/pdf', [ReportController::class, 'ownerAssignedSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.owner-assigned-summary-pdf');

    Route::get('/report/owner-assigned-summary/excel', [ReportController::class, 'ownerAssignedSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.owner-assigned-summary-excel');

    Route::get('/report/supervisor-assigned-summary', [ReportController::class, 'supervisorAssignedSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary');

    Route::get('/report/supervisor-assigned-summary/pdf', [ReportController::class, 'supervisorAssignedSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary-pdf');

    Route::get('/report/supervisor-assigned-summary/excel', [ReportController::class, 'supervisorAssignedSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary-excel');

    Route::get('/report/replanned-orders-summary', [ReportController::class, 'replannedOrdersSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.replanned-orders-summary');

    Route::get('/report/replanned-orders-summary/pdf', [ReportController::class, 'replannedOrdersSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.replanned-orders-summary-pdf');

    Route::get('/report/replanned-orders-summary/excel', [ReportController::class, 'replannedOrdersSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('report.replanned-orders-summary-excel');

    Route::post('/frontdesk/{order}/update-status', [FrontdeskController::class, 'updateStatus'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value]  )
    ->name('frontdesk.updateStatus');

    Route::post('/frontdesk/{order}/update-status-standby', [FrontdeskController::class, 'updateStatusStandBy'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value]  )
    ->name('frontdesk.updateStatusStandBy');

    Route::post('/frontdesk/{order}/update-status-lost', [FrontdeskController::class, 'updateStatusLost'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value] )
    ->name('frontdesk.updateStatusLost');

      Route::get('/frontdesk/show-quantified-modal/{order}', [FrontdeskController::class, 'showQuantifiedModal'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
    ->name('frontdesk.show-quantified-modal');

     Route::post('/frontdesk/update-status-quantified/{order}', [FrontdeskController::class, 'updateStatusQuantified'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
    ->name('frontdesk.update-status-quantified');

    Route::get('/frontdesk/create-qualified', [FrontdeskController::class, 'createQualified'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
    ->name('frontdesk.create-qualified');

     Route::post('/frontdesk/store-qualified-order', [FrontdeskController::class, 'storeQualifiedOrder'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
    ->name('frontdesk.store-qualified-order');

    
      Route::get('/frontdesk/order_view/{id}', [FrontdeskController::class, 'orderView'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::FRONTDESK_ESR->value ])
      ->name('frontdesk.order_view');

       Route::put('/frontdesk/orders/{order}/contact', [FrontdeskController::class, 'updateOrderContact'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value ])
      ->name('frontdesk.orders.update-contact');

      Route::put('/frontdesk/orders/{order}/qualified', [FrontdeskController::class, 'updateQualifiedOrder'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::ACCOUNTING->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value . '|'. RoleEnum::PRODUCTION->value ])
      ->name('frontdesk.orders.update-qualified');

      Route::patch('/frontdesk/tags_update/{order}', [FrontdeskController::class, 'tagsUpdate'])
    ->name('frontdesk.tags_update');

     /* Route::get('/email-test', function() {
        echo 'Start Email test <br/>';
          $order = App\Models\Order::with(['client', 'owners', 'supervisor', 'installationTeams.user'])->find(587);
          //$installationDateConfirmation = new App\Mail\InstallationDateConfirmation($order, true, true, false,true);
          //App\Jobs\SendGmailEmail::dispatch('katiuska28@gmail.com', $installationDateConfirmation)->onQueue('emails');

          $emailAccounting = new App\Mail\EmailAccounting($order);
          App\Jobs\SendGmailEmail::dispatch('katiuska28@gmail.com', $emailAccounting)->onQueue('emails');
        echo 'End Email test <br/>';
      })->name('email-test');*/
});

require __DIR__.'/auth.php';
// require __DIR__.'/socialite.php';
