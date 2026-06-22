<?php

namespace App\Jobs;

use App\Enum\StatusUserEnum;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Bus\PendingDispatch;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Fluent;

class SendGmailEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email;

    protected $mailable;

    protected bool $allowInactiveUserRecipient;

    /**
     * Create a new job instance.
     */
    public function __construct($email, $mailable, bool $allowInactiveUserRecipient = false)
    {
        $this->email = $email;
        $this->mailable = $mailable;
        $this->allowInactiveUserRecipient = $allowInactiveUserRecipient;
    }

    /**
     * Dispatch the job only when at least one recipient is allowed.
     */
    public static function dispatch(...$arguments): PendingDispatch|Fluent
    {
        $job = new static(...$arguments);
        $job->email = $job->recipientsAllowedToReceiveEmail();

        return $job->email === null || $job->email === []
            ? new Fluent
            : new PendingDispatch($job);
    }

    /**
     * Execute the job.
     */
    public function handle(GmailService $gmailService): void
    {
        $recipients = $this->recipientsAllowedToReceiveEmail();

        if ($recipients === null || $recipients === []) {
            return;
        }

        $gmailService->sendEmail($recipients, $this->mailable->subject, $this->mailable);
    }

    private function recipientsAllowedToReceiveEmail(): string|array|null
    {
        $multipleRecipients = is_array($this->email);
        $recipients = collect($multipleRecipients ? $this->email : [$this->email])
            ->filter(fn ($email) => is_string($email) && trim($email) !== '')
            ->map(fn (string $email) => trim($email))
            ->values();

        if ($recipients->isEmpty()) {
            return $multipleRecipients ? [] : null;
        }

        if ($this->allowInactiveUserRecipient) {
            return $multipleRecipients ? $recipients->all() : $recipients->first();
        }

        $normalizedRecipients = $recipients
            ->map(fn (string $email) => mb_strtolower($email))
            ->all();

        $blockedUserEmails = User::withTrashed()
            ->whereIn(DB::raw('LOWER(email)'), $normalizedRecipients)
            ->where(function ($query) {
                $query
                    ->where('status', '!=', StatusUserEnum::ACTIVE->value)
                    ->orWhereNull('status')
                    ->orWhereNotNull('deleted_at');
            })
            ->pluck('email')
            ->map(fn (string $email) => mb_strtolower(trim($email)));

        $allowedRecipients = $recipients
            ->reject(fn (string $email) => $blockedUserEmails->contains(mb_strtolower($email)))
            ->values();

        return $multipleRecipients ? $allowedRecipients->all() : $allowedRecipients->first();
    }
}
