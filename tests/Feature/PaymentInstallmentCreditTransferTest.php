<?php

use App\Enum\RoleEnum;
use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentInstallment;
use App\Models\PaymentSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

function createCreditTransferOrder(?Client $client = null): Order
{
    $user = User::factory()->create();
    $client ??= Client::factory()->create();
    Schema::disableForeignKeyConstraints();

    $orderId = DB::table('orders')->insertGetId([
        'order_number' => 'RG-CREDIT-1001',
        'name' => 'Credit Transfer Order',
        'job_address' => '123 Test St',
        'city_permits' => false,
        'association_permits' => false,
        'equipment_rental' => false,
        'equipment_rental_price' => null,
        'additional_travel_costs' => null,
        'entry_date' => now()->toDateString(),
        'installation_date' => null,
        'notes' => null,
        'client_id' => $client->id,
        'type_of_work_id' => 1,
        'type_of_housing_id' => 1,
        'installer_id' => $user->id,
        'supervisor_id' => $user->id,
        'travel_cost_id' => 1,
        'duration_of_work_id' => 1,
        'user_id' => $user->id,
        'method_of_payment' => 'CASH',
        'project_amount' => 200.00,
        'service' => 'new_order',
        'status' => 'NEW CUSTOMER REQUEST',
        'job_city' => 'Miami',
        'job_state' => 'FL',
        'job_zip' => '33101',
        'invoice_number' => 'INV-CREDIT-1001',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::enableForeignKeyConstraints();

    return Order::findOrFail($orderId);
}

test('admin can manually apply overpaid credit to another installment', function () {
    $admin = User::factory()->create();
    Role::findOrCreate(RoleEnum::ADMIN->value);
    $admin->assignRole(RoleEnum::ADMIN->value);

    $order = createCreditTransferOrder();
    $schedule = PaymentSchedule::create([
        'order_id' => $order->id,
        'schedule_type' => 'CUSTOMIZED',
        'total_amount' => 200.00,
    ]);

    $source = PaymentInstallment::create([
        'payment_schedule_id' => $schedule->id,
        'position' => 1,
        'label' => 'Deposit',
        'percentage' => 50.00,
        'amount' => 100.00,
        'status' => 'PENDING',
    ]);
    $target = PaymentInstallment::create([
        'payment_schedule_id' => $schedule->id,
        'position' => 2,
        'label' => 'Final',
        'percentage' => 50.00,
        'amount' => 100.00,
        'status' => 'PENDING',
    ]);

    $source->movements()->create([
        'amount' => 125.00,
        'paid_at' => now(),
        'paid_by' => $admin->id,
        'method' => 'CASH',
    ]);
    $source->syncPaymentState();

    $response = $this->actingAs($admin)->postJson(
        route('payment_installment_credit_transfers.store', $source),
        [
            'target_installment_id' => $target->id,
            'amount' => 25.00,
            'note' => 'Apply overpaid balance.',
        ]
    );

    $response->assertOk()
        ->assertJsonPath('source_installment.status', 'PAID')
        ->assertJsonPath('source_installment.credit', 0)
        ->assertJsonPath('target_installment.status', 'PARTIAL')
        ->assertJsonPath('target_installment.paid_amount', 25);

    expect((float) $source->movements()->sum('amount'))->toBe(100.0)
        ->and((float) $target->movements()->sum('amount'))->toBe(25.0)
        ->and((float) PaymentInstallment::query()
            ->where('payment_schedule_id', $schedule->id)
            ->whereHas('movements')
            ->withSum('movements', 'amount')
            ->get()
            ->sum('movements_sum_amount'))->toBe(125.0);
});
