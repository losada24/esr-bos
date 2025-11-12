<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;

class OrderWorkTeamNotesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Order::whereNotNull('work_team_notes')
            ->where('work_team_notes', '<>', '')
            ->chunkById(100, function ($orders) {
                foreach ($orders as $order) {
                    $content = trim((string) $order->work_team_notes);
                    if ($content === '') {
                        continue;
                    }

                    $alreadyExists = $order->notes()
                        ->where('type', 'work_team_note')
                        ->where('content', $content)
                        ->exists();

                    if (!$alreadyExists) {
                        $order->notes()->create([
                            'content' => $content,
                            'type' => 'work_team_note',
                            'user_id' => $order->user_id ?? 1,
                        ]);
                    }
                }
            });
    }
}
