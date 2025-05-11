<?php

// app/Jobs/ImportRankingJob.php

namespace App\Jobs;

use App\Services\RankingImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Bus\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportRankingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var string
     */
    public $roundName;

    /**
     * @var int
     */
    public $roundNumber;

    /**
     * Create a new job instance.
     *
     * @param string $roundName
     * @param int    $roundNumber
     */
    public function __construct(string $roundName, int $roundNumber)
    {
        $this->roundName = $roundName;
        $this->roundNumber = $roundNumber;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(RankingImportService $service): void
    {
        // Die Connection "legacy" nutzt immer die in game config definierte Quell-DB
        // Der Service importSingleRound wechselt intern die Connection und führt den Import durch
        $service->importSingleRound($this->roundNumber);
    }
}
