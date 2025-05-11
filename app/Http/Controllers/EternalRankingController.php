<?php

namespace App\Http\Controllers;

use App\Models\EternalRanking;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;
use Spatie\RouteAttributes\Attributes\Get;

class EternalRankingController extends Controller
{
    #[Get(uri: '/eternal', name: 'ranking.eternal.index')]
    public function index()
    {
        $perPage = 100;
        $roundId = request('round_id');
        $currentPage = request('page', 1);

        // Spieler mit berechnetem Durchschnitt in SQL (kein Eager-Loading nötig)
        $players = EternalRankingPlayer::select('eternal_ranking_players.*')
            ->selectSub(function ($query) {
                $query->from('eternal_ranking_results')
                    ->selectRaw('AVG(pct)')
                    ->whereColumn('eternal_ranking_results.player_id', 'eternal_ranking_players.id')
                    ->whereNotNull('pct');
            }, 'avg_pct')
            ->orderByDesc('avg_pct')
            ->paginate($perPage, ['*'], 'page', $currentPage);

        // Alle Runden (Tabellen-Header)
        $rounds = EternalRanking::orderBy('round_number')->pluck('round_number');

        // Nur Ergebnisse für angezeigte Spieler (gefiltert optional nach round_id)
        $playerIds = $players->pluck('id');
        $resultsQuery = EternalRankingResult::with('ranking')
            ->whereIn('player_id', $playerIds);
        if ($roundId) {
            $resultsQuery->whereHas('ranking', function ($q) use ($roundId) {
                $q->where('id', $roundId);
            });
        }
        $allResults = $resultsQuery->get();

        // [playerId][roundNumber] => result
        $results = [];
        foreach ($allResults as $row) {
            $rid = $row->ranking->round_number;
            $results[$row->player_id][$rid] = $row;
        }

        return view('ranking.eternal.index', [
            'players' => $players,
            'rounds' => $rounds,
            'results' => $results,
            'roundId' => $roundId
        ]);
    }
}
