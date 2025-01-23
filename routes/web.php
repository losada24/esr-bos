<?php

use App\Enum\RoleEnum;
use App\Http\Controllers\BiginController;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallationTeamController;
use App\Http\Controllers\ReportController;
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

    Route::resource('user', UserController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ]);
    
    Route::resource('order', OrderController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ]);

    Route::get('order/get_delivery_and_installation_date/{payment_factory_date}/{type_of_housing}/{county_id}/{service}/{hasPermit}', [OrderController::class, 'getDeliveryAndInstallationDate'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value]);
    
    Route::get('order/get_delivery_and_pickup_date/{payment_factory_date}', [OrderController::class, 'getDeliveryAndPickupDate'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value]);
    
    Route::post('order/update-date-paid', [OrderController::class, 'updateDatePaid'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value])
      ->name('order.update_date_paid');
    
    Route::get('dashboard/get_events/{year}/{month}/{service}/{status}/{name?}', [DashboardController::class, 'getEvents'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value])
      ->name('dashboard.get_events');
    
    Route::get('dashboard/get_event/{order}', [DashboardController::class, 'getEvent'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value])
      ->name('dashboard.get_event');
    
    Route::post('order/update-from-modal/{order}', [OrderController::class, 'updateFromModal'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value .'|' . RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value])
      ->name('update.order.from.modal');
    
    Route::get('order/get_payment_list/{order}', [DashboardController::class, 'getPaymentList'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value ])
      ->name('order.get_payment_list');
    
    Route::put('dashboard/update_events/{id}', [DashboardController::class, 'updateEvent'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|'. RoleEnum::SUPERVISOR->value .'|' . RoleEnum::SERVICE_MANAGER->value])
      ->name('dashboard.update_event');

    Route::delete('order/drop_attachment/{id}', [OrderController::class, 'dropAttachment'])
      // ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|other_roles'])
      ->middleware('auth') 
      ->name('order.drop_attachment');
    
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
    Route::resource('client', ClientController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value . "|" . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value]);
    Route::get('client/is_unique/{phone}/{address?}', [ClientController::class, 'isUnique'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|' . RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value]);
    Route::get('client/document/{id}', [ClientController::class, 'document'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::FRONTDESK->value . '|' . RoleEnum::OWNER->value])
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
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value] )
      ->name('report.supervisor');

      Route::get('/report/installer', [ReportController::class, 'installer'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::INSTALLER->value] )
      ->name('report.installer');

      Route::get('/report/show_supervisor/{id}', [ReportController::class, 'showSupervisor'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value] )
      ->name('report.show_supervisor');

      Route::get('/report/excel-supervisor/{user}', [ReportController::class, 'export'])
      ->middleware(["role:" . RoleEnum::ADMIN->value . '|'. RoleEnum::ACCOUNT_MANAGER->value . '|' . RoleEnum::SUPERVISOR->value] )
      ->name('report.excel-supervisor');
  /*
    // ORDERS
   Route::get('/order', [OrderController::class, 'index'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$ACCOUNTING ."|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER])
      ->name('order.index');

    Route::post('/order/notes-update', [OrderController::class, 'noteUpdate'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER ])
      ->name('order.notes.update');
    
    Route::post('/order/status-update', [OrderController::class, 'statusUpdate'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER ])
      ->name('order.status.update');
    
    Route::get('/order/status/{order}', [OrderController::class, 'status'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER])
      ->name('order.status');

    Route::get('/order/status-filter', [OrderController::class, 'statusFilter'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|"  . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER . "|" . RoleEnum::$SUB_DEALER])
      ->name('order.status.filter');

    Route::get('/order/history/{order}', [OrderController::class, 'history'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER])
      ->name('order.history');

    Route::get('/order/show/{id}', [OrderController::class, 'show'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$PLANT_MANAGER ])
      ->name('order.show');*/
});

require __DIR__.'/auth.php';
// require __DIR__.'/socialite.php';
