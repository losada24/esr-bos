<?php

use App\Enum\RoleEnum;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallationTeamController;

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

/* Route::get('/mailable', function () {
  $order = App\Models\Order::find(47);

  // Mail::to('efrain@reylosglass.com', 'Efrain')->send(new App\Mail\EstimateCreated($order, [RoleEnum::$DEALER]));
  return new App\Mail\ProductionScheduled($order, 'This is a test');
}); */


    // ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER]);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // USERS
    /* Route::resource('user', UserController::class)
      ->only(['index', 'create', 'store'])
      ->middleware(["role:" . RoleEnum::$ADMIN . "|" . RoleEnum::$ACCOUNT_MANAGER . "|" . RoleEnum::$DEALER]); */
    
    

    Route::resource('user', UserController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value]);
    
    Route::resource('order', OrderController::class)
      ->middleware(["role:" . RoleEnum::ADMIN->value]);

    Route::resource('installation_team', InstallationTeamController::class)
      ->only(['index', 'create', 'store', 'update', 'edit', 'destroy'])
      ->middleware(["role:" . RoleEnum::ADMIN->value]);

    // CLIENTS
     /*Route::resource('client', ClientController::class)
      ->middleware(["role:" . RoleEnum::$ADMIN]);

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
