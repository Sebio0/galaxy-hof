<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportEternalRankingLegacyData extends Command
{
    protected $signature = 'eternal:import-legacy';
    protected $description = 'Importiere alle bisherigen Runden dynamisch und berechne Prozente.';

    public function handle()
    {
        $rounds = collect(DB::connection('legacy')->getSchemaBuilder()->getColumnListing('gn_hof'))
            ->filter(fn($col) => preg_match('/^runde(\d+)$/', $col, $m))
            ->map(fn($col) => (int)substr($col, 5))
            ->sort()
            ->values();

        foreach ($rounds as $num) {
            $this->call('eternal:add-round', ['roundNumber' => $num]);
        }
    }
}
