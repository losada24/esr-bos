<?php

    namespace App\Http\Controllers;

    use App\Models\CrmCall;
    use App\Models\CrmEvent;
    use App\Models\Note;
    use App\Models\Order;
    use App\Services\CrmNotificationService;
    use App\Traits\Snapshot;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;

class OrderNoteController extends Controller
{
    use Snapshot;

    // GET /order/{order}/notes
    public function index(Order $order)
    {
        $directNotes = $order->notes()
            ->with(['user:id,name', 'attachments'])
            ->get();

        if (!request()->boolean('include_related')) {
            return $directNotes
                ->sortByDesc('created_at')
                ->map(fn (Note $note) => $this->notePayload($note))
                ->values();
        }

        $callIds = CrmCall::query()->where('order_id', $order->id)->pluck('id');
        $eventIds = CrmEvent::query()->where('order_id', $order->id)->pluck('id');

        $activityNotes = Note::query()
            ->with(['user:id,name', 'attachments', 'noteable'])
            ->where(function ($query) use ($callIds, $eventIds) {
                $query->where(function ($callQuery) use ($callIds) {
                    $callQuery->where('noteable_type', CrmCall::class)
                        ->whereIn('noteable_id', $callIds);
                })->orWhere(function ($eventQuery) use ($eventIds) {
                    $eventQuery->where('noteable_type', CrmEvent::class)
                        ->whereIn('noteable_id', $eventIds);
                });
            })
            ->get();

        return $directNotes
            ->concat($activityNotes)
            ->sortByDesc('created_at')
            ->map(fn (Note $note) => $this->notePayload($note, $this->noteContextLabel($note)))
            ->values();
    }

    // POST /order/{order}/notes
    public function store(Request $request, Order $order)
    {
        $data = $request->validate([
            'content' => ['required','string'],
            'type'    => ['nullable','string','max:120'],
        ]);

        $note = $order->notes()->create([
            'content' => $data['content'],
            'type'    => $data['type'] ?? 'order_note',
            'user_id' => $request->user()->id,
        ]);

        $this->createSnapshot($order->fresh());

        $note->load('user:id,name');
        app(CrmNotificationService::class)->recordOrderFeed(
            $order->fresh(),
            $request->user(),
            'Order note added',
            ($request->user()?->name ?? 'Someone') . ' added a note to order ' . ($order->name ?? ('#' . $order->id))
        );

        $note->load('attachments');

        return response()->json($this->notePayload($note), Response::HTTP_CREATED);
    }

    // PUT /order/{order}/notes/{note}
    public function update(Request $request, Order $order, Note $note)
    {
        // Pertenece a la orden?
        abort_unless(
            $note->noteable_type === Order::class && (int) $note->noteable_id === (int) $order->id,
            Response::HTTP_NOT_FOUND
        );

        // Es el autor?
        abort_if($note->user_id !== $request->user()->id, Response::HTTP_FORBIDDEN);

        $data = $request->validate([
            'content' => ['sometimes','required','string'],
            'type'    => ['nullable','string','max:120'],
        ]);

        $note->update($data);
        $note->load(['user:id,name', 'attachments']);

        return $this->notePayload($note);
    }

    // DELETE /order/{order}/notes/{note}
    public function destroy(Request $request, Order $order, Note $note)
    {
        // Pertenece a la orden?
        abort_unless(
            $note->noteable_type === Order::class && (int) $note->noteable_id === (int) $order->id,
            Response::HTTP_NOT_FOUND
        );

        // Es el autor?
        abort_if($note->user_id !== $request->user()->id, Response::HTTP_FORBIDDEN);

        $note->delete();
        return response()->noContent();
    }

    private function notePayload(Note $note, ?string $contextLabel = null): array
    {
        $isOrderNote = $note->noteable_type === Order::class;
        $isAuthor = $note->user_id === auth()->id();

        return [
            'id'         => $note->id,
            'content'    => $note->content,
            'type'       => $note->type,
            'context_label' => $contextLabel,
            'created_at' => optional($note->created_at)->toISOString(),
            'user'       => $note->user ? ['name' => $note->user->name] : null,
            'can'        => [
                'update' => $isOrderNote && $isAuthor,
                'delete' => $isOrderNote && $isAuthor,
            ],
            'audio_attachments' => $note->attachments
                ->where('file_type', \App\Enum\AttachmentsFileTypeEnum::NOTE_AUDIO->value)
                ->map(fn ($attachment) => $this->audioPayload($note, $attachment))
                ->values()
                ->all(),
        ];
    }

    private function audioPayload(Note $note, $attachment): array
    {
        return [
            'id' => $attachment->id,
            'filename' => $attachment->filename,
            'mime_type' => $attachment->mime_type,
            'duration_seconds' => $attachment->duration_seconds,
            'transcription_status' => $attachment->transcription_status,
            'url' => route('notes.audio.show', ['note' => $note->id, 'attachment' => $attachment->id]),
            'created_at' => optional($attachment->created_at)->toISOString(),
            'can' => [
                'delete' => $attachment->user_id === auth()->id() || $note->user_id === auth()->id(),
            ],
        ];
    }

    private function noteContextLabel(Note $note): ?string
    {
        if ($note->noteable instanceof CrmCall) {
            return trim('Call - Call scheduled with ' . $note->noteable->to_from);
        }

        if ($note->noteable instanceof CrmEvent) {
            return trim('Event - ' . $note->noteable->title);
        }

        return null;
    }
}
