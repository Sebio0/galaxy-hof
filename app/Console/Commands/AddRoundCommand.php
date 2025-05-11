<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RankingImportService;

class AddRoundCommand extends Command
{
    protected $signature = 'eternal:add-round {roundNumber : Die Nummer der Runde}';
    protected $description = 'Importiere eine einzelne Runde und berechne Prozente.';

    private RankingImportService $service;

    public function __construct(RankingImportService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle()
    {
        $roundNumber = (int) $this->argument('roundNumber');
        $this->info("🚀 Starte Import für Runde {$roundNumber}...");

        try {
            $this->service->importRound($roundNumber);
            $this->info("🎉 Runde {$roundNumber} erfolgreich importiert und Prozentwerte berechnet.");
        } catch (\Exception $e) {
            $this->error("❌ Fehler beim Import von Runde {$roundNumber}: {$e->getMessage()}");
        }
    }
}
