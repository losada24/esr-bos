<?php

use App\Enum\OrderStatusEnum;
use App\Http\Controllers\OrderStageOverdueExtensionController;
use App\Models\Order;
use App\Models\OrderStageOverdueExtension;
use App\Models\OrderStatus;
use App\Models\User;
use App\Support\OrderStageOverdueTracker;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
        'database.connections.sqlite.foreign_key_constraints' => false,
    ]);
    DB::purge('sqlite');
    Carbon::setTestNow(Carbon::parse('2026-08-31 10:00:00'));

    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->string('status');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('order_status', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id');
        $table->foreignId('user_id')->nullable();
        $table->string('status');
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('order_stage_overdues', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id');
        $table->foreignId('order_status_id')->nullable();
        $table->string('status');
        $table->dateTime('stage_started_at')->nullable();
        $table->unsignedSmallInteger('limit_business_days');
        $table->unsignedInteger('business_days_elapsed')->default(0);
        $table->dateTime('detected_at');
        $table->dateTime('resolved_at')->nullable();
        $table->unsignedInteger('resolved_business_days_elapsed')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });

    Schema::create('order_stage_overdue_extensions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('order_id');
        $table->foreignId('order_stage_overdue_id');
        $table->foreignId('user_id')->nullable();
        $table->string('status');
        $table->dateTime('stage_started_at')->nullable();
        $table->unsignedSmallInteger('business_days');
        $table->dateTime('extended_until');
        $table->text('note');
        $table->timestamps();
    });

    Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->text('content')->nullable();
        $table->string('type')->nullable();
        $table->unsignedBigInteger('noteable_id');
        $table->string('noteable_type');
        $table->foreignId('user_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () {
    Carbon::setTestNow();
    DB::purge('sqlite');
});

function createIsolatedOverdueOrder(string $status = OrderStatusEnum::PRODUCTION->value): Order
{
    $order = Order::withoutEvents(fn () => Order::query()->create(['status' => $status]));
    addIsolatedOverdueStatusEntry($order, $status, 40);

    return $order;
}

function addIsolatedOverdueStatusEntry(Order $order, string $status, int $weekdaysAgo): OrderStatus
{
    $entry = new OrderStatus;
    $entry->forceFill([
        'order_id' => $order->id,
        'status' => $status,
        'created_at' => now()->subWeekdays($weekdaysAgo),
        'updated_at' => now()->subWeekdays($weekdaysAgo),
    ]);
    $entry->save();

    return $entry;
}

function submitIsolatedOverdueExtension(Order $order, int $businessDays, string $note): void
{
    $user = new User;
    $user->forceFill(['id' => 1]);

    $request = Request::create('/stage-overdue-extensions', 'POST', [
        'business_days' => $businessDays,
        'note' => $note,
    ]);
    $request->setUserResolver(fn () => $user);

    app(OrderStageOverdueExtensionController::class)->store(
        $request,
        $order,
        app(OrderStageOverdueTracker::class)
    );
}

test('overdue extensions cannot exceed 30 cumulative business days in the same status entry', function () {
    $order = createIsolatedOverdueOrder();

    submitIsolatedOverdueExtension($order, 5, 'First extension.');
    $firstExtension = OrderStageOverdueExtension::query()->firstOrFail();

    submitIsolatedOverdueExtension($order, 25, 'Use the remaining allowance.');
    $secondExtension = OrderStageOverdueExtension::query()->latest('id')->firstOrFail();

    $expectedExtendedUntil = $firstExtension->extended_until->copy()->addWeekdays(25)->endOfDay();

    expect(OrderStageOverdueExtension::query()->sum('business_days'))->toBe(30)
        ->and($secondExtension->extended_until->toDateTimeString())->toBe($expectedExtendedUntil->toDateTimeString());

    try {
        submitIsolatedOverdueExtension($order, 1, 'This must be rejected.');
        $this->fail('The controller accepted more than 30 cumulative business days.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['business_days'] ?? [])->toContain(
            'The 30 business day extension limit has been reached for this status overdue.'
        );
    }

    expect(OrderStageOverdueExtension::query()->count())->toBe(2);
});

test('the 30 day extension allowance resets when the order enters another status', function () {
    $order = createIsolatedOverdueOrder();
    submitIsolatedOverdueExtension($order, 30, 'Use the production allowance.');

    $newStatus = OrderStatusEnum::PENDING_GLASS_INVOICE->value;
    Order::withoutEvents(fn () => $order->update(['status' => $newStatus]));
    addIsolatedOverdueStatusEntry($order, $newStatus, 10);
    $order->unsetRelation('orderStatus');

    submitIsolatedOverdueExtension($order, 30, 'Use the new status allowance.');

    expect(OrderStageOverdueExtension::query()->count())->toBe(2)
        ->and(OrderStageOverdueExtension::query()->sum('business_days'))->toBe(60)
        ->and(OrderStageOverdueExtension::query()->where('status', $newStatus)->sum('business_days'))->toBe(30);
});
