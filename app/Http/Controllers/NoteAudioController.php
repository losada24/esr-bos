<?php

namespace App\Http\Controllers;

use App\Enum\AttachmentsFileTypeEnum;
use App\Enum\RoleEnum;
use App\Models\Attachment;
use App\Models\CrmCall;
use App\Models\CrmEvent;
use App\Models\InstallationTeam;
use App\Models\Note;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class NoteAudioController extends Controller
{
    public function store(Request $request, Note $note): JsonResponse
    {
        $this->authorizeNoteAccess($request->user(), $note);

        $data = $request->validate([
            'audio' => [
                'required',
                'file',
                'max:10240',
            ],
            'duration_seconds' => ['nullable', 'integer', 'min:0', 'max:86400'],
        ]);

        $file = $data['audio'];
        $this->validateAudioFile($file);

        $extension = $file->getClientOriginalExtension() ?: $this->extensionFromMime($file->getMimeType());
        $fileName = now()->format('YmdHis') . '_' . Str::uuid() . '.' . $extension;

        try {
            $filePath = $file->storeAs('note_audio', $fileName, 'public');

            if (!$filePath) {
                throw new \RuntimeException('The audio file could not be stored.');
            }

            $attachment = $note->attachments()->create([
                'filename' => $file->getClientOriginalName() ?: $fileName,
                'file_path' => $filePath,
                'file_type' => AttachmentsFileTypeEnum::NOTE_AUDIO->value,
                'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
                'size_bytes' => $file->getSize(),
                'duration_seconds' => $data['duration_seconds'] ?? null,
                'transcription_status' => 'pending',
                'user_id' => $request->user()->id,
            ]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'message' => 'The note was saved, but the audio could not be stored. Please verify production storage and the attachments migration.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response()->json([
            'audio' => $this->audioPayload($attachment),
        ], Response::HTTP_CREATED);
    }

    public function show(Request $request, Note $note, Attachment $attachment)
    {
        $this->authorizeAudio($request->user(), $note, $attachment);

        $disk = Storage::disk('public');

        try {
            return redirect()->away($disk->temporaryUrl($attachment->file_path, now()->addMinutes(10)));
        } catch (Throwable) {
            abort_unless(method_exists($disk, 'path') && $disk->exists($attachment->file_path), 404);

            return response()->file($disk->path($attachment->file_path), [
                'Content-Type' => $attachment->mime_type ?: 'audio/webm',
            ]);
        }
    }

    public function destroy(Request $request, Note $note, Attachment $attachment): JsonResponse
    {
        $this->authorizeAudio($request->user(), $note, $attachment);

        abort_unless(
            (int) $attachment->user_id === (int) $request->user()->id
            || (int) $note->user_id === (int) $request->user()->id,
            403
        );

        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function authorizeAudio(?User $user, Note $note, Attachment $attachment): void
    {
        $this->authorizeNoteAccess($user, $note);

        abort_unless(
            $attachment->attachable_type === Note::class
            && (int) $attachment->attachable_id === (int) $note->id
            && $attachment->file_type === AttachmentsFileTypeEnum::NOTE_AUDIO->value,
            404
        );
    }

    private function authorizeNoteAccess(?User $user, Note $note): void
    {
        abort_unless($user, 403);

        $note->loadMissing('noteable');
        $parent = $note->noteable;

        if ($note->user_id === $user->id || $this->hasWideAccess($user)) {
            return;
        }

        if ($parent instanceof Order) {
            abort_unless($this->canAccessOrder($user, $parent), 403);
            return;
        }

        if ($parent instanceof CrmEvent) {
            abort_unless(
                (int) $parent->host_id === (int) $user->id
                || ($parent->order && $this->canAccessOrder($user, $parent->order)),
                403
            );
            return;
        }

        if ($parent instanceof CrmCall) {
            abort_unless(
                (int) $parent->owner_id === (int) $user->id
                || ($parent->order && $this->canAccessOrder($user, $parent->order)),
                403
            );
            return;
        }

        abort(403);
    }

    private function hasWideAccess(User $user): bool
    {
        return $user->hasAnyRole([
            RoleEnum::ADMIN->value,
            RoleEnum::ACCOUNT_MANAGER->value,
            RoleEnum::OWNER_ADMIN->value,
            RoleEnum::FRONTDESK->value,
            RoleEnum::FRONTDESK_ADMIN->value,
            RoleEnum::FRONTDESK_ESR->value,
            RoleEnum::SERVICE_MANAGER->value,
            RoleEnum::PAYMENT_COORDINATOR->value,
        ]);
    }

    private function canAccessOrder(User $user, Order $order): bool
    {
        if ((int) $order->user_id === (int) $user->id || (int) $order->supervisor_id === (int) $user->id) {
            return true;
        }

        if ($user->hasRole(RoleEnum::OWNER->value)) {
            return $order->owners()->whereIn('users.id', $user->accessibleOwnerIds())->exists();
        }

        if ($order->owners()->where('users.id', $user->id)->exists()) {
            return true;
        }

        if ($user->hasRole(RoleEnum::INSTALLER->value)) {
            $installationTeam = InstallationTeam::query()->where('user_id', $user->id)->first();

            return $installationTeam !== null
                && $order->installationTeams()->where('installation_teams.id', $installationTeam->id)->exists();
        }

        return false;
    }

    private function audioPayload(Attachment $attachment): array
    {
        return [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'mime_type' => $attachment->mime_type,
            'duration_seconds' => $attachment->duration_seconds,
            'transcription_status' => $attachment->transcription_status,
            'url' => route('notes.audio.show', [
                'note' => $attachment->attachable_id,
                'attachment' => $attachment->id,
            ]),
            'created_at' => optional($attachment->created_at)->toISOString(),
            'can' => [
                'delete' => $attachment->user_id === auth()->id(),
            ],
        ];
    }

    private function validateAudioFile(UploadedFile $file): void
    {
        $allowedExtensions = ['webm', 'mp3', 'mp4', 'm4a', 'ogg', 'wav'];
        $allowedMimeTypes = [
            'audio/webm',
            'video/webm',
            'audio/mpeg',
            'audio/mp3',
            'audio/mp4',
            'audio/ogg',
            'audio/wav',
            'audio/x-wav',
            'application/octet-stream',
        ];

        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = strtolower((string) ($file->getMimeType() ?: $file->getClientMimeType()));

        if (!in_array($extension, $allowedExtensions, true) && !in_array($mimeType, $allowedMimeTypes, true)) {
            throw ValidationException::withMessages([
                'audio' => ['The audio must be a webm, mp3, mp4, m4a, ogg, or wav file.'],
            ]);
        }
    }

    private function extensionFromMime(?string $mime): string
    {
        return match ($mime) {
            'audio/mpeg', 'audio/mp3' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/ogg' => 'ogg',
            'audio/wav', 'audio/x-wav' => 'wav',
            default => 'webm',
        };
    }
}
