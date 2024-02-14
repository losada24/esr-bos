<?php

use App\Enum\OrderStatusEnum;
use App\Enum\RoleEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RawMaterialController;
use App\Http\Controllers\FixedWindowsController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\SingleHuntController;
use App\Http\Controllers\HorizontalRollerController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProductController;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

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

/*Route::get('/mailable', function () {
  $order = App\Models\Order::find(44);

  Mail::to('efrain@reylosglass.com', 'Efrain')->send(new App\Mail\EstimateCreated($order, [RoleEnum::$DEALER]));
  return new App\Mail\EstimateCreated($order, [RoleEnum::$DEALER]);
});*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // USERS
    Route::resource('user', UserController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER]);
    
    Route::resource('user', UserController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER, 'checkUserCreatedByField']);

    // COMPANIES
    Route::get('/company/profile', [CompanyController::class, 'profile'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER]) // TODO: Remove admin only DEALER
      ->name('company.profile');

    Route::put('/company/updateProfile/{company}', [CompanyController::class, 'updateProfile'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER]) // TODO: Remove admin only DEALER
      ->name('company.updateProfile');

    Route::resource('company', CompanyController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);

    // CLIENTS
    Route::resource('client', ClientController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER]);

    // RAW MATERIALS
    Route::resource('raw-material', RawMaterialController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);
    
    // ESTIMATES
    Route::resource('estimate', EstimateController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER]);
    
    Route::get('estimate/{id}/duplicate', [EstimateController::class, 'duplicate'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",id",
        "validate.estimate.owner:id"  
      ])
      ->name('estimate.duplicate');

    Route::resource('estimate', EstimateController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.owner:estimate"
      ]);

    Route::resource('estimate', EstimateController::class)
      ->only(['show'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.owner:estimate"
      ]);

    Route::get('/estimate/{id}/order', [EstimateController::class, 'order'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER,
        "validate.estimate.owner:id"
      ])
      ->name('estimate.order');

    Route::post('/estimate/order/store', [EstimateController::class, 'orderStore'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER])
      ->name('estimate.order.store');

    // FIXED WINDOWS
    Route::get('/fixed-windows/{id}/create', [FixedWindowsController::class, 'create'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",id",
        "validate.estimate.owner:id"
      ])
      ->name('fixed-windows.create');
    
    Route::post('/fix-windows/store', [FixedWindowsController::class, 'store'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
      ])
      ->name('fixed-windows.store');

    Route::get('/fixed-windows/edit/{product}', [FixedWindowsController::class, 'edit'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.owner:product",
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",product",
      ])
      ->name('fixed-windows.edit');

    Route::put('/fixed-windows/update/{product}', [FixedWindowsController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER]) // TODO: Validate if the user is the owner of the order
      ->name('fixed-windows.update');

    // SINGLE HUNT
    Route::get('/single-hung/{id}/create', [SingleHuntController::class, 'create'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",id",
        "validate.estimate.owner:id"
      ])
      ->name('single-hunt.create');
    
    Route::post('/single-hung/store', [SingleHuntController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER])
      ->name('single-hunt.store');
    
    Route::get('/single-hung/edit/{product}', [SingleHuntController::class, 'edit'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",product",
        "validate.estimate.owner:product"
      ])
      ->name('single-hunt.edit');

    Route::put('/single-hung/update/{product}', [SingleHuntController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER]) // TODO: Validate if the user is the owner of the order
      ->name('single-hunt.update');

    // HORIZONTAL ROLLER
    Route::get('/horizontal-roller/{id}/create', [HorizontalRollerController::class, 'create'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",id",
        "validate.estimate.owner:id"
      ])
      ->name('horizontal-roller.create');
    
    Route::post('/horizontal-roller/store', [HorizontalRollerController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER])
      ->name('horizontal-roller.store'); // TODO: Validate if the user is the owner of the order
    
    Route::get('/horizontal-roller/edit/{product}', [HorizontalRollerController::class, 'edit'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",product",
        "validate.estimate.owner:product"
      ])
      ->name('horizontal-roller.edit');

    Route::put('/horizontal-roller/update/{product}', [HorizontalRollerController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER]) // TODO: Validate if the user is the owner of the order
      ->name('horizontal-roller.update');

    // PRODUCT DELETE
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",product",
        "validate.estimate.owner:product"
      ])
      ->name('product.destroy');

    Route::post('product/duplicate/{product}', [ProductController::class, 'duplicate'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER,
        "validate.estimate.status:" . OrderStatusEnum::$ESTIMATE . "|" . OrderStatusEnum::$SUB_DEALER_ESTIMATE . ",product",
        "validate.estimate.owner:product"
      ])
      ->name('product.duplicate');
    
    // ORDERS
    Route::get('/order', [OrderController::class, 'index'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$ACCOUNTING ."|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$SHIPPING])
      ->name('order.index');

    Route::post('/order/status-update', [OrderController::class, 'statusUpdate'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$SHIPPING ])
      ->name('order.status.update');
    
    Route::get('/order/status/{order}', [OrderController::class, 'status'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SHIPPING])
      ->name('order.status');

    Route::get('/order/history/{order}', [OrderController::class, 'history'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SHIPPING])
      ->name('order.history');

    Route::get('/order/workOrder/{order}', [OrderController::class, 'workOrder'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('order.workOrder');

    Route::get('/order/show/{id}', [OrderController::class, 'show'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$SHIPPING  ])
      ->name('order.show');

    // PDF DOCUMENTS
    Route::get('/pdf/work-order/{order}', [PdfController::class, 'workOrder'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.work.order');

    Route::get('/pdf/cutting-list/{order}', [PdfController::class, 'cuttingList'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.cutting.list');

    Route::get('/pdf/material-consumption/{order}', [PdfController::class, 'materialConsumption'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.material.consumption');

    Route::get('/pdf/po-screen/{order}', [PdfController::class, 'poScreen'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.po.screen');

    Route::get('/pdf/po-glass/{order}', [PdfController::class, 'poGlass'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.po.glass');
    
    Route::get('/pdf/po-balance/{order}', [PdfController::class, 'poBalance'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.po.balance');
    
    Route::get('/pdf/estimate-with-prices/{order}', [PdfController::class, 'estimateWithPrices'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$ACCOUNTING ])
      ->name('pdf.estimate.with.prices');
    
    Route::get('/pdf/estimate-with-totals/{order}', [PdfController::class, 'estimateWithTotalPrices'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$ACCOUNTING ])
      ->name('pdf.estimate.with.total.prices');

    Route::get('/pdf/estimate-without/{order}', [PdfController::class, 'estimateWithoutPrices'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$SUB_DEALER . "|" . RoleEnum::$ACCOUNTING ])
      ->name('pdf.estimate.without.prices');
    
    Route::get('/pdf/report/{order}', [PdfController::class, 'report'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING ])
      ->name('pdf.report');

    Route::get('/pdf/sub-report/{order}', [PdfController::class, 'subDealerReport'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$DEALER . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$SUB_DEALER ])
      ->name('pdf.subreport');
    
    Route::get('/pdf/production/{order}', [PdfController::class, 'production'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNTING . "|" . RoleEnum::$ACCOUNT_MANAGER ])
      ->name('pdf.production');

    Route::get('/pdf/delivery/{order}', [PdfController::class, 'delivery'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$SHIPPING . "|" . RoleEnum::$ACCOUNT_MANAGER ])
      ->name('pdf.delivery');

    // LABELS
    Route::get('/label/pieces/{order}', [LabelController::class, 'labelsByPieces'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('labels.labelsByPieces');

    Route::get('/label/product/{order}', [LabelController::class, 'productLabels'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('labels.productLabels');
    
});

require __DIR__.'/auth.php';
// require __DIR__.'/socialite.php';
