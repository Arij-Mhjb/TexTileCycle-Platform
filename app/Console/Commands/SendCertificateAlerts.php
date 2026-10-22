<?php

namespace App\Console\Commands;

use App\Services\CertificateAlertService;
use Illuminate\Console\Command;

class SendCertificateAlerts extends Command
{
    protected $signature = 'suppliers:certificate-alerts';

    protected $description = 'Prévient marques et fournisseurs des certificats qui expirent (30 jours, 7 jours, expiré)';

    public function handle(CertificateAlertService $alerts): int
    {
        $this->info($alerts->run().' alerte(s) envoyée(s).');

        return self::SUCCESS;
    }
}
