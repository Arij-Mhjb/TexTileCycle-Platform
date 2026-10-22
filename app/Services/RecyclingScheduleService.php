<?php

namespace App\Services;

use App\Models\RecyclingLoop;
use App\Models\RecyclingSchedule;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Collectes programmées : à chaque échéance, une demande de recyclage est envoyée automatiquement au fournisseur. */
class RecyclingScheduleService
{
    public function __construct(private RecyclingLoopService $loops) {}

    /** @return int nombre de demandes créées */
    public function runDue(): int
    {
        $created = 0;
        RecyclingSchedule::with(['brand', 'supplier'])->where('active', true)->whereDate('next_run_on', '<=', now()->toDateString())->get()
            ->each(function (RecyclingSchedule $schedule) use (&$created) {
                try {
                    $this->run($schedule);
                    $created++;
                } catch (Throwable $exception) {
                    Log::warning('Collecte programmée '.$schedule->id.' non lancée : '.$exception->getMessage());
                }
            });

        return $created;
    }

    /** Lance la collecte maintenant et calcule l'échéance suivante. */
    public function run(RecyclingSchedule $schedule, ?User $by = null): RecyclingLoop
    {
        $schedule->loadMissing(['brand.user', 'supplier']);
        $loop = $this->loops->request(
            $schedule->brand,
            $schedule->supplier,
            $schedule->items,
            [],
            ['mode' => $schedule->mode, 'brand_message' => trim('Collecte programmée ('.mb_strtolower($schedule->frequencyLabel()).'). '.$schedule->brand_message)],
            $by ?? $schedule->brand->user,
        );
        $loop->update(['recycling_schedule_id' => $schedule->id]);
        $schedule->update([
            'last_run_at' => now(),
            'last_loop_id' => $loop->id,
            'runs_count' => $schedule->runs_count + 1,
            'next_run_on' => $schedule->nextDateAfter(now()),
        ]);

        return $loop;
    }
}
