<?php

namespace App\Http\Controllers;

use App\Support\StrictlyZeroWebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StrictlyZeroWebhookController extends Controller
{
    public function __construct(private readonly StrictlyZeroWebhookProcessor $processor)
    {
    }

    public function payments(Request $request): Response
    {
        $result = $this->processor->handle($request);

        if (!$result['authorized']) {
            return response('unauthorized', 401);
        }

        return response($result['duplicate'] ? 'duplicate' : 'ok', 200);
    }
}
