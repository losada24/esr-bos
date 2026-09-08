<?php

namespace App\Contracts;

use App\Models\CrmEvent;

interface GoogleCalendarGateway
{
    public function upsert(CrmEvent $event, string $userEmail, string $googleEventId): void;

    public function delete(string $userEmail, string $googleEventId): void;
}
