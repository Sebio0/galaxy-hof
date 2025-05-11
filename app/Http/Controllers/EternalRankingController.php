<?php

namespace App\Http\Controllers;

use App\Models\EternalRanking;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;
use Illuminate\Http\Request;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class EternalRankingController extends Controller
{
    #[Get(uri: '/eternal', name: 'ranking.eternal.index')]
    public function index(Request $request)
    {
        // Default: alle ohne Filter
        [$players, $rounds, $results] = $this->buildQuery(
            $request->input('round_id'),
            $request->input('search'),
            $request->input('page', 1)
        );
        return view('ranking.eternal.index', compact('players', 'rounds', 'results'));
    }

    #[Post(uri: '/eternal/table', name: 'ranking.eternal.table')]
    public function table(Request $request)
    {
        [$players, $rounds, $results] = $this->buildQuery(
            $request->input('round_id'),
            $request->input('search'),
            $request->input('page', 1)
        );
        return view('ranking.eternal.partials.table', compact('players', 'rounds', 'results'));
    }

    #[Post(uri: '/eternal/search', name: 'ranking.eternal.search')]
    public function search(Request $request)
    {
        return $this->table($request);
    }

    #[Post(uri: '/eternal/round', name: 'ranking.eternal.round')]
    public function filterRound(Request $request)
    {
        return $this->table($request);
    }

    protected function buildQuery($roundId, $search, $page)
    {
        $perPage = 100;
        $playerQuery = EternalRankingPlayer::select('eternal_ranking_players.*')
            ->selectSub(fn($q) => $q->from('eternal_ranking_results')
                ->selectRaw('AVG(pct)')
                ->whereColumn('eternal_ranking_results.player_id', 'eternal_ranking_players.id')
                ->whereNotNull('pct')
                , 'avg_pct');

        if ($search) {
            $playerQuery->where('nickname', 'like', "%{$search}%");
        }

        $players = $playerQuery
            ->orderByDesc('avg_pct')
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends(['round_id' => $roundId, 'search' => $search]);

        $rounds = EternalRanking::orderBy('round_number')->pluck('round_number');
        $playerIds = $players->pluck('id');

        $allResults = EternalRankingResult::with('ranking')
            ->whereIn('player_id', $playerIds)
            ->when($roundId, fn($q) => $q->whereHas('ranking', fn($q2) => $q2->where('id', $roundId)))
            ->get();

        $results = [];
        foreach ($allResults as $row) {
            $rid = $row->ranking->round_number;
            $results[$row->player_id][$rid] = $row;
        }

        return [$players, $rounds, $results];
    }
}
