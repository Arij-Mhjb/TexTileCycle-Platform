<?php

namespace App\Jobs;

use App\Models\BrandApplication;
use App\Services\BrandLabelAdvisor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AnalyseBrandApplication implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public int $applicationId) {}

    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(BrandLabelAdvisor $advisor): void
    {
        $application = BrandApplication::find($this->applicationId);
        if (! $application || $application->status !== 'pending' || $application->analysis_status === 'completed') {
            return;
        }
        $advisor->analyse($application);
    }

    public function failed(?\Throwable $exception): void
    {
        BrandApplication::whereKey($this->applicationId)->where('status', 'pending')->update([
            'analysis_status' => 'failed', 'analysis_summary' => 'L’analyse IA a échoué. Relancez-la ou procédez à une revue manuelle.',
        ]);
    }
}
