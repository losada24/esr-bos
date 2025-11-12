<?php

    namespace App\Http\Controllers;

    use App\Models\Note;
    use App\Models\Order;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;

class OrderNoteController extends Controller
{
    // GET /order/{order}/notes
    public function index(Order $order)
    {
        $notes = $order->notes()
            ->with(['user:id,name'])
            ->latest()
            ->get();

        return $notes->map(function (Note $n) {
            return [
                'id'         => $n->id,
                'content'    => $n->content,
                'type'       => $n->type,
                'created_at' => optional($n->created_at)->toISOString(),
                'user'       => $n->user ? ['name' => $n->user->name] : null,
                // Solo el autor puede editar/borrar
                'can'        => [
                    'update' => $n->user_id === auth()->id(),
                    'delete' => $n->user_id === auth()->id(),
                ],
            ];
        })->values();
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

        $note->load('user:id,name');

        return response()->json([
            'id'         => $note->id,
            'content'    => $note->content,
            'type'       => $note->type,
            'created_at' => optional($note->created_at)->toISOString(),
            'user'       => $note->user ? ['name' => $note->user->name] : null,
            'can'        => [
                'update' => true, // el autor es el usuario actual
                'delete' => true,
            ],
        ], Response::HTTP_CREATED);
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
        $note->load('user:id,name');

        return [
            'id'         => $note->id,
            'content'    => $note->content,
            'type'       => $note->type,
            'created_at' => optional($note->created_at)->toISOString(),
            'user'       => $note->user ? ['name' => $note->user->name] : null,
            'can'        => [
                'update' => true,
                'delete' => true,
            ],
        ];
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
}
