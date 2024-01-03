<?php

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
use App\Http\Controllers\PdfController;
use App\Http\Controllers\ProductController;

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

  dd(config('custom', 'admin_emails'));

  $referrer = App\Models\Referred::find(1);

  return new App\Mail\NewReferred($referrer);
});*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // USERS
    Route::resource('user', UserController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]);
    
    Route::resource('user', UserController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN, 'checkUserCreatedByField']);

    // COMPANIES
    Route::get('/company/profile', [CompanyController::class, 'profile'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]) // TODO: Remove admin only CLIENT_ADMIN
      ->name('company.profile');

    Route::put('/company/updateProfile/{company}', [CompanyController::class, 'updateProfile'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]) // TODO: Remove admin only CLIENT_ADMIN
      ->name('company.updateProfile');

    Route::resource('company', CompanyController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);

    // CLIENTS
    Route::resource('client', ClientController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN]);

    // RAW MATERIALS
    Route::resource('raw-material', RawMaterialController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);
    
    // ESTIMATES
    Route::resource('estimate', EstimateController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]);

    Route::resource('estimate', EstimateController::class)
      ->only(['edit', 'update', 'destroy'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT,
        "validate.order.owner"
      ]);

    Route::resource('estimate', EstimateController::class)
      ->only(['show'])
      ->middleware([
        "role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT // TODO: Validate if the user is the owner of the order
      ]);

    Route::post('/estimate/order/store', [EstimateController::class, 'orderStore'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN])
      ->name('estimate.order.store');

    // FIXED WINDOWS
    Route::get('/fixed-windows/{id}/create', [FixedWindowsController::class, 'create'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('fixed-windows.create');
    
    Route::post('/fix-windows/store', [FixedWindowsController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT])
      ->name('fixed-windows.store');

    Route::get('/fixed-windows/edit/{product}', [FixedWindowsController::class, 'edit'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('fixed-windows.edit');

    Route::put('/fixed-windows/update/{product}', [FixedWindowsController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('fixed-windows.update');

    // SINGLE HUNT
    Route::get('/single-hung/{id}/create', [SingleHuntController::class, 'create'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('single-hunt.create');
    
    Route::post('/single-hung/store', [SingleHuntController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT])
      ->name('single-hunt.store');
    
    Route::get('/single-hung/edit/{product}', [SingleHuntController::class, 'edit'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('single-hunt.edit');

    Route::put('/single-hung/update/{product}', [SingleHuntController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('single-hunt.update');

    // HORIZONTAL ROLLER
    Route::get('/horizontal-roller/{id}/create', [HorizontalRollerController::class, 'create'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('horizontal-roller.create');
    
    Route::post('/horizontal-roller/store', [HorizontalRollerController::class, 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT])
      ->name('horizontal-roller.store');
    
    Route::get('/horizontal-roller/edit/{product}', [HorizontalRollerController::class, 'edit'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('horizontal-roller.edit');

    Route::put('/horizontal-roller/update/{product}', [HorizontalRollerController::class, 'update'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) // TODO: Validate if the user is the owner of the order
      ->name('horizontal-roller.update');

    // PRODUCT DELETE
    Route::delete('/product/{product}', [ProductController::class, 'destroy'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$CLIENT]) //TODO: Validate if the user is the owner of the order
      ->name('product.destroy');
    
    // ORDERS
    Route::get('/order', [OrderController::class, 'index'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$CLIENT_ADMIN . "|" . RoleEnum::$PRODUCTION . "|" . RoleEnum::$ACCOUNTING])
      ->name('order.index');

    Route::post('/order/status-update', [OrderController::class, 'statusUpdate'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNTING])
      ->name('order.status.update');
    
    Route::post('/order/complete-production', [OrderController::class, 'completeProduction'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION])
      ->name('order.complete.production');

    Route::get('/order/workOrder/{order}', [OrderController::class, 'workOrder'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('order.workOrder');

    Route::get('/order/show/{id}', [OrderController::class, 'show'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('order.show');

    // PDF DOCUMENTS
    Route::get('/pdf/work-order/{order}', [PdfController::class, 'workOrder'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.work.order');

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
    
    Route::get('/pdf/estimate/{order}', [PdfController::class, 'estimate'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.estimate');
    
    Route::get('/pdf/report/{order}', [PdfController::class, 'report'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$PRODUCTION ])
      ->name('pdf.report');
    
});

require __DIR__.'/auth.php';
// require __DIR__.'/socialite.php';
