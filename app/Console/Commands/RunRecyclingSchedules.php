<?php

namespace App\Console\Commands;

use App\Services\RecyclingScheduleService;
use Illuminate\Console\Command;

class RunRecyclingSchedules extends Command
{
    protected $signature = 'recycling:run-schedules';

    protected $description = 'Envoie aux fournisseurs les collectes de rebuts programmées arrivées à échéance';

    public function handle(RecyclingScheduleService $schedules): int
    {
        $this->info($schedules->runDue().' collecte(s) programmée(s) envoyée(s).');

        return self::SUCCESS;
    }
}
