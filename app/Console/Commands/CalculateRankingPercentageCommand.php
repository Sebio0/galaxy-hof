<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateRankingPercentageCommand extends Command
{
    protected $signature = 'eternal:calculate-percentage {rankingId? : Optional die ID der Runde}';
    protected $description = 'Berechnet und aktualisiert die Prozentwerte für eine bestimmte Runde oder alle.';

    public function handle()
    {
        $rankingId = $this->argument('rankingId');
        $rankings = $rankingId
            ? [(int)$rankingId]
            : DB::table('eternal_rankings')->pluck('id')->toArray();

        $this->info('🔢 Starte Prozentberechnung...');
        foreach ($rankings as $rid) {
            $max = DB::table('eternal_ranking_results')
                ->where('ranking_id', $rid)
                ->max('score');

            DB::table('eternal_ranking_results')
                ->where('ranking_id', $rid)
                ->update([
                    'pct' => $max > 0
                        ? DB::raw("(score / {$max} * 100)")
                        : 0.00
                ]);

            $this->info("  • Runde {$rid}: Prozente aktualisiert (Max-Score: {$max}).");
        }
        $this->info('🎉 Prozentberechnung abgeschlossen.');
    }
}

