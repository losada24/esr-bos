<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'dashboard/update_events/*',
        'frontdesk/*/update-status',
        'frontdesk/*/update-status-standby',
        'frontdesk/*/update-status-lost',
        'frontdesk/update-status-quantified/*',
        'frontdesk/orders/*/qualified',
        'order/*/notes',
        'order/*/notes/*',
        'sales/*/assign-estimate',
        'sales/*/assign-follow-up',
        'sales/*/assign-stand-by',
        'sales/*/assign-request-reschedule',
        'sales/*/assign-pre-contract',
        'sales/*/assign-contract-signed',
        'sales/*/assign-lost-contract',
        'webhook/authorize-net/payments',
        'webhook/strictly-zero/debug',
        'webhook/strictly-zero/payments',
    ];
}
