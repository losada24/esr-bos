<?php

namespace App\Http\Controllers;

use App\Support\AuthorizeNetWebhookProcessor;
use Illuminate\Http\Response;
use Illuminate\Http\Request;

class AuthorizeNetWebhookController extends Controller
{
    public function __construct(private readonly AuthorizeNetWebhookProcessor $processor)
    {
    }

    public function payments(Request $request): Response
    {
        $result = $this->processor->handle($request);

        return response($result['duplicate'] ? 'duplicate' : 'ok', 200);
    }
}
