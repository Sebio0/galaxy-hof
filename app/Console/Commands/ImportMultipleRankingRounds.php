<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\RankingImportService;
use App\Models\EternalRanking;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;

class ImportMultipleRankingRounds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eternal:import-multiple-rounds';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importiert mehrere Runden aus Datenbanken, die in databases.txt definiert sind';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $filePath = base_path('databases.txt');

        if (!file_exists($filePath)) {
            $this->error("Die Datei databases.txt wurde nicht gefunden: {$filePath}");
            return;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (empty($lines)) {
            $this->warn('Die Datei databases.txt ist leer. Es wurden keine Runden importiert.');
            return;
        }

        $importService = new RankingImportService();
        $stats = [];

        foreach ($lines as $lineNumber => $line) {
            if (!str_contains($line, '|')) {
                $this->warn("Zeile " . ($lineNumber + 1) . " ist ungültig und wird übersprungen: {$line}");
                continue;
            }

            [$roundName, $database] = array_map('trim', explode('|', $line, 2));

            if (empty($roundName) || empty($database)) {
                $this->warn("Zeile " . ($lineNumber + 1) . " enthält unvollständige Daten und wird übersprungen: {$line}");
                continue;
            }

            // Rundennummer extrahieren
            if (preg_match('/Runde\s*(\d+)/i', $roundName, $matches)) {
                $roundNumber = (int)$matches[1];
            } else {
                $this->warn("⚠️ Konnte keine Rundennummer aus '{$roundName}' extrahieren – Zeile wird übersprungen.");
                continue;
            }

            $this->info("\n⏳ Importiere Runde: '{$roundName}' (#{$roundNumber}) aus Datenbank '{$database}' ...");

            try {
                $importService->importFromDatabase($database, $roundName, $roundNumber);
                $ranking = EternalRanking::where('round_number', $roundNumber)->first();

                $importedCount = EternalRankingResult::where('ranking_id', $ranking->id)->count();
                $newPlayersCount = EternalRankingPlayer::whereHas('results', function ($q) use ($ranking) {
                    $q->where('ranking_id', $ranking->id);
                })->count();

                $stats[] = [
                    'Runde' => $roundName,
                    'Datenbank' => $database,
                    'Importierte Spielergebnisse' => $importedCount,
                    'Betroffene Spieler' => $newPlayersCount
                ];

                $this->info("✅ Erfolgreich importiert: {$roundName} aus {$database} ({$importedCount} Ergebnisse, {$newPlayersCount} Spieler)");
            } catch (\Exception $e) {
                $this->error("❌ Fehler beim Importieren von {$roundName} aus {$database}: " . $e->getMessage());
            }
        }

        $this->info("\n🎉 Alle definierten Runden wurden verarbeitet.");

        if (!empty($stats)) {
            $this->table(
                ['Runde', 'Datenbank', 'Importierte Spielergebnisse', 'Betroffene Spieler'],
                $stats
            );
        }
    }
}
