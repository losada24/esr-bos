<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\BiginController;
use App\Http\Controllers\BiweeklyController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CompanyContactController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FrontdeskController;
use App\Http\Controllers\InstallationTeamController;
use App\Http\Controllers\OrderNoteController;
use App\Http\Controllers\OrderProcessingController;
use App\Http\Controllers\OrderSearchController;
use App\Http\Controllers\PaymentScheduleController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SalesController;
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

    Route::resource('user', UserController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ]);

      Route::get('company_contact', [CompanyContactController::class, 'index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::FRONTDESK_ESR->value])
      ->name('company_contact.index');
      Route::resource('company_contact', CompanyContactController::class)
      ->except(['index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]);
    
    Route::get('order/create-service', [OrderController::class, 'createService'])
      ->name('order.create_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::post('order/service', [OrderController::class, 'storeService'])
      ->name('order.store_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::put('order/{order}/service', [OrderController::class, 'updateService'])
      ->name('order.update_service')
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value]);

    Route::resource('order', OrderController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value .'|'. RoleEnum::SERVICE_MANAGER->value .'|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value] );

    Route::get('orders/search', [OrderSearchController::class, 'index'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::OWNER_ADMIN->value . '|' . RoleEnum::OWNER->value . '|' . RoleEnum::FRONTDESK_ADMIN->value . '|' . RoleEnum::FRONTDESK_ESR->value])
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
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|'. RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value] )
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
      Route::get('/sales/tasks', [SalesController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('sales.tasks');
      Route::resource('sales', SalesController::class)
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value ]);
      Route::get('order-processing', [OrderProcessingController::class, 'index'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value ])
        ->name('order-processing.index');
      Route::get('order-processing/tasks', [OrderProcessingController::class, 'tasks'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('order-processing.tasks');

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
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('sales.assign_request_reschedule');

      Route::post('/sales/{order}/assign-pre-contract', [SalesController::class, 'assignPreContract'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_pre_contract');

      Route::post('/sales/{order}/assign-contract-signed', [SalesController::class, 'assignContractSigned'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_contract_signed');

      Route::patch('/payment-installments/{installment}', [PaymentScheduleController::class, 'updateInstallment'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
        ->name('payment_installments.update');

      Route::post('/sales/{order}/assign-lost-contract', [SalesController::class, 'assignLostContract'])
        ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::OWNER->value])
        ->name('sales.assign_lost_contract');

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
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::PAYMENT_COORDINATOR->value .'|'. RoleEnum::SERVICE_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value.'|' . RoleEnum::INSTALLER->value.'|' . RoleEnum::OWNER->value .'|' . RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value] )
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

    Route::get('/report/daily-order-status-summary', [ReportController::class, 'dailyOrderStatusSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary');

    Route::get('/report/daily-order-status-summary/pdf', [ReportController::class, 'dailyOrderStatusSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary-pdf');

    Route::get('/report/daily-order-status-summary/excel', [ReportController::class, 'dailyOrderStatusSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::FRONTDESK_ADMIN->value . '|'. RoleEnum::OWNER_ADMIN->value] )
    ->name('report.daily-order-status-summary-excel');

    Route::get('/report/installer-confirmed-summary', [ReportController::class, 'installerConfirmedSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary');

    Route::get('/report/installer-confirmed-summary/pdf', [ReportController::class, 'installerConfirmedSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary-pdf');

    Route::get('/report/installer-confirmed-summary/excel', [ReportController::class, 'installerConfirmedSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.installer-confirmed-summary-excel');

    Route::get('/report/supervisor-assigned-summary', [ReportController::class, 'supervisorAssignedSummary'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary');

    Route::get('/report/supervisor-assigned-summary/pdf', [ReportController::class, 'supervisorAssignedSummaryPdf'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary-pdf');

    Route::get('/report/supervisor-assigned-summary/excel', [ReportController::class, 'supervisorAssignedSummaryExcel'])
    ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value] )
    ->name('report.supervisor-assigned-summary-excel');

    Route::post('/frontdesk/{order}/update-status', [FrontdeskController::class, 'updateStatus'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SERVICE_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value]  )
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
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
    ->name('frontdesk.create-qualified');

     Route::post('/frontdesk/store-qualified-order', [FrontdeskController::class, 'storeQualifiedOrder'])
     ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value. '|'. RoleEnum::FRONTDESK_ADMIN->value])
    ->name('frontdesk.store-qualified-order');

    
      Route::get('/frontdesk/order_view/{id}', [FrontdeskController::class, 'orderView'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value. '|'. RoleEnum::FRONTDESK_ESR->value ])
      ->name('frontdesk.order_view');

       Route::put('/frontdesk/orders/{order}/contact', [FrontdeskController::class, 'updateOrderContact'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value ])
      ->name('frontdesk.orders.update-contact');

      Route::put('/frontdesk/orders/{order}/qualified', [FrontdeskController::class, 'updateQualifiedOrder'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::OWNER_ADMIN->value . '|'. RoleEnum::OWNER->value . '|'. RoleEnum::FRONTDESK_ADMIN->value ])
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
