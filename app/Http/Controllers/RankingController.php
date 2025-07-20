<?php

namespace App\Http\Controllers;

use App\Models\GameInstance;
use App\Models\HallOfFame;
use App\Models\HofUser;
use App\Models\InstanceRound;
use App\Models\Ranking;
use App\Models\RankingType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Spatie\RouteAttributes\Attributes\Get;
use Spatie\RouteAttributes\Attributes\Post;

class RankingController extends Controller
{
    /**
     * Clear all caches related to rankings
     * This method should be called when rankings are imported or updated
     *
     * @param HallOfFame|null $hallOfFame The hall of fame instance to clear cache for, or null to clear all caches
     * @return void
     */
    public static function clearCache(?HallOfFame $hallOfFame = null): void
    {
        if ($hallOfFame) {
            // Clear specific hall of fame caches
            Cache::forget("player_combined_scores:{$hallOfFame->id}");
            Cache::forget("ranking_types:{$hallOfFame->id}");
            Cache::forget("ranking_types:{$hallOfFame->id}:ids");
            Cache::forget("max_players:{$hallOfFame->id}");
            Cache::forget("hall_of_fame:{$hallOfFame->instance_round_id}");

            // Clear player-specific caches for this hall of fame
            // This is a bit more complex as we need to find all players in this hall of fame
            $playerIds = Ranking::where('hof_id', $hallOfFame->id)
                ->distinct()
                ->pluck('hof_user_id');

            foreach ($playerIds as $playerId) {
                Cache::forget("player_rankings:{$playerId}:{$hallOfFame->id}");
                Cache::forget("player_positions:{$playerId}:{$hallOfFame->id}");
            }
        } else {
            // Clear all caches
            Cache::flush();
        }
    }
    /**
     * Calculate the combined ranking score for a player based on their positions in different ranking types.
     * Lower score is better (like in golf).
     *
     * @param array $positions Array of player's positions in different ranking types
     * @return float Combined ranking score
     */
    private function calculateCombinedRankingScore(array $positions): float
    {
        if (empty($positions)) {
            return 0;
        }

        // Calculate the average position (arithmetic mean)
        $sum = array_sum($positions);
        $count = count($positions);

        return $sum / $count;
    }

    /**
     * Calculate combined ranking scores for all players in a hall of fame
     *
     * @param HallOfFame $hallOfFame The hall of fame instance
     * @return array Array of player IDs and their combined scores, sorted by score (ascending)
     */
    private function calculateAllPlayersCombinedScores(HallOfFame $hallOfFame): array
    {
        // Create a cache key based on the hall of fame ID
        $cacheKey = 'player_combined_scores:' . $hallOfFame->id;

        // Return cached result if available (cache for 1 hour)
        return Cache::remember($cacheKey, 3600, function () use ($hallOfFame) {
            // Get only ranking types that exist in this hall of fame
            $rankingTypes = Ranking::where('hof_id', $hallOfFame->id)
                ->distinct()
                ->pluck('ranking_type_id')
                ->toArray();

            // Calculate max players for each ranking type in a single query
            $maxPlayersByRankingType = Ranking::where('hof_id', $hallOfFame->id)
                ->selectRaw('ranking_type_id, COUNT(*) as count')
                ->groupBy('ranking_type_id')
                ->pluck('count', 'ranking_type_id')
                ->toArray();

            // Get all rankings with positions pre-calculated using window functions
            $rankings = \DB::table('rankings as r1')
                ->select([
                    'r1.hof_user_id',
                    'r1.ranking_type_id',
                    'r1.value',
                    \DB::raw('(SELECT COUNT(*) + 1 FROM rankings as r2
                               WHERE r2.hof_id = r1.hof_id
                               AND r2.ranking_type_id = r1.ranking_type_id
                               AND r2.value > r1.value) as position')
                ])
                ->where('r1.hof_id', $hallOfFame->id)
                ->get();

            // Group rankings by player
            $playerRankings = [];
            foreach ($rankings as $ranking) {
                if (!isset($playerRankings[$ranking->hof_user_id])) {
                    $playerRankings[$ranking->hof_user_id] = [];
                }
                $playerRankings[$ranking->hof_user_id][$ranking->ranking_type_id] = $ranking->position;
            }

            // Get all players in this hall of fame with eager loading
            $players = HofUser::whereHas('rankings', function($query) use ($hallOfFame) {
                $query->where('hof_id', $hallOfFame->id);
            })->get();

            $playerScores = [];

            foreach ($players as $player) {
                $positions = [];

                // Use pre-calculated positions for each ranking type
                foreach ($rankingTypes as $rankingTypeId) {
                    if (isset($playerRankings[$player->id][$rankingTypeId])) {
                        $positions[$rankingTypeId] = $playerRankings[$player->id][$rankingTypeId];
                    } else {
                        // Player is not in this ranking, assign max+1 position
                        $positions[$rankingTypeId] = ($maxPlayersByRankingType[$rankingTypeId] ?? 0) + 1;
                    }
                }

                // Calculate combined score
                $combinedScore = $this->calculateCombinedRankingScore($positions);

                $playerScores[$player->id] = [
                    'player' => $player,
                    'score' => $combinedScore,
                    'positions' => $positions
                ];
            }

            // Sort by score (ascending - lower is better)
            uasort($playerScores, function($a, $b) {
                return $a['score'] <=> $b['score'];
            });

            return $playerScores;
        });
    }

    /**
     * Calculate the player's position in the combined ranking without calculating scores for all players
     * This is an optimized version of the calculation that only gets the position for a single player
     *
     * @param string $hofUserId The player's ID
     * @param HallOfFame $hallOfFame The hall of fame instance
     * @param array $playerRankingPositions The player's positions in different ranking types
     * @param array $maxPlayersByRankingType Maximum number of players for each ranking type
     * @return array [position, totalPlayers]
     */
    private function calculatePlayerCombinedPosition(string $hofUserId, HallOfFame $hallOfFame, array $playerRankingPositions, array $maxPlayersByRankingType): array
    {
        // Create a cache key for this specific player's position
        $cacheKey = "player_combined_position:{$hofUserId}:{$hallOfFame->id}";

        // Return cached result if available (cache for 1 hour)
        return Cache::remember($cacheKey, 3600, function () use ($hofUserId, $hallOfFame, $playerRankingPositions, $maxPlayersByRankingType) {
            // Get the player's combined score
            $playerScore = $this->calculateCombinedRankingScore($playerRankingPositions);

            // Get total number of players
            $totalPlayers = \DB::table('rankings')
                ->where('hof_id', $hallOfFame->id)
                ->distinct('hof_user_id')
                ->count('hof_user_id');

            // For small datasets, we can use a simpler approach to estimate the position
            // This is much faster than calculating exact positions for all players
            if ($totalPlayers <= 100) {
                // For small datasets, we can calculate the exact position
                // Get all distinct player IDs for this hall of fame
                $playerIds = \DB::table('rankings')
                    ->where('hof_id', $hallOfFame->id)
                    ->distinct('hof_user_id')
                    ->pluck('hof_user_id');

                // Count players with better scores
                $betterPlayers = 0;

                foreach ($playerIds as $playerId) {
                    // Skip the current player
                    if ($playerId == $hofUserId) {
                        continue;
                    }

                    // Get cached positions for this player
                    $otherPlayerPositionsCacheKey = "player_positions:{$playerId}:{$hallOfFame->id}";
                    $otherPlayerPositions = Cache::remember($otherPlayerPositionsCacheKey, 3600, function () use ($playerId, $hallOfFame) {
                        $positions = \DB::table('rankings as r1')
                            ->select([
                                'r1.ranking_type_id',
                                \DB::raw('(SELECT COUNT(*) + 1 FROM rankings as r2
                                           WHERE r2.hof_id = r1.hof_id
                                           AND r2.ranking_type_id = r1.ranking_type_id
                                           AND r2.value > r1.value) as position')
                            ])
                            ->where('r1.hof_id', $hallOfFame->id)
                            ->where('r1.hof_user_id', $playerId)
                            ->get();

                        $result = [];
                        foreach ($positions as $position) {
                            $result[$position->ranking_type_id] = $position->position;
                        }
                        return $result;
                    });

                    // Set max+1 positions for rankings the player doesn't have
                    foreach ($maxPlayersByRankingType as $rankingTypeId => $maxPlayers) {
                        if (!isset($otherPlayerPositions[$rankingTypeId])) {
                            $otherPlayerPositions[$rankingTypeId] = $maxPlayers + 1;
                        }
                    }

                    // Calculate combined score for this player
                    $otherScore = $this->calculateCombinedRankingScore($otherPlayerPositions);

                    // Count if this player has a better score
                    if ($otherScore < $playerScore) {
                        $betterPlayers++;
                    }
                }

                return [$betterPlayers + 1, $totalPlayers];
            } else {
                // For larger datasets, use an approximation based on percentile
                // This is much faster but less accurate

                // Calculate the percentile of the player's score
                // Lower scores are better, so we want to know what percentage of players have lower scores
                $percentile = 0;

                // Count how many ranking types the player has positions for
                $playerRankingCount = 0;
                foreach ($playerRankingPositions as $rankingTypeId => $pos) {
                    // Only count positions that are not max+1 (i.e., the player actually has this ranking)
                    if ($pos <= ($maxPlayersByRankingType[$rankingTypeId] ?? 0)) {
                        $playerRankingCount++;
                    }
                }

                if ($playerRankingCount > 0) {
                    // Calculate average percentile across all ranking types
                    $percentileSum = 0;
                    $rankingTypeCount = 0;

                    foreach ($playerRankingPositions as $rankingTypeId => $position) {
                        // Skip rankings the player doesn't have
                        if ($position > ($maxPlayersByRankingType[$rankingTypeId] ?? 0)) {
                            continue;
                        }

                        // Calculate percentile for this ranking type (0-100, lower is better)
                        $maxPlayers = $maxPlayersByRankingType[$rankingTypeId] ?? 0;
                        if ($maxPlayers > 0) {
                            $percentileSum += (($position - 1) / $maxPlayers) * 100;
                            $rankingTypeCount++;
                        }
                    }

                    if ($rankingTypeCount > 0) {
                        $percentile = $percentileSum / $rankingTypeCount;
                    }
                }

                // Estimate position based on percentile
                $estimatedPosition = max(1, min($totalPlayers, round(($percentile / 100) * $totalPlayers) + 1));

                return [$estimatedPosition, $totalPlayers];
            }
        });
    }

    #[Get(uri: '/player/{hofUserId}/{roundId}', name: 'ranking.player')]
    public function playerRankings($hofUserId, $roundId)
    {
        // Performance optimization: Use eager loading for all related models
        $player = HofUser::findOrFail($hofUserId);
        $round = InstanceRound::with('instance')->findOrFail($roundId);
        $gameInstance = $round->instance;

        // Get the hall of fame for this round (cache for 1 hour)
        $hofCacheKey = "hall_of_fame:{$roundId}";
        $hallOfFame = Cache::remember($hofCacheKey, 3600, function () use ($roundId) {
            return HallOfFame::where('instance_round_id', $roundId)->first();
        });

        if (!$hallOfFame) {
            return redirect()->route('ranking.index')
                ->with('error', 'Keine Hall of Fame für diese Runde gefunden.');
        }

        // Create cache keys
        $playerRankingsCacheKey = "player_rankings:{$hofUserId}:{$hallOfFame->id}";
        $rankingTypesCacheKey = "ranking_types:{$hallOfFame->id}";
        $maxPlayersCacheKey = "max_players:{$hallOfFame->id}";
        $playerPositionsCacheKey = "player_positions:{$hofUserId}:{$hallOfFame->id}";

        // Get all rankings for this player in this hall of fame with eager loading (cache for 1 hour)
        $rankings = Cache::remember($playerRankingsCacheKey, 3600, function () use ($hofUserId, $hallOfFame) {
            return Ranking::with(['rankingType'])
                ->where('hof_id', $hallOfFame->id)
                ->where('hof_user_id', $hofUserId)
                ->orderByDesc('value')
                ->get();
        });

        // Get only ranking types that exist in this hall of fame (cache for 1 hour)
        $rankingTypeIds = Cache::remember($rankingTypesCacheKey . ':ids', 3600, function () use ($hallOfFame) {
            return Ranking::where('hof_id', $hallOfFame->id)
                ->distinct()
                ->pluck('ranking_type_id');
        });

        $rankingTypes = Cache::remember($rankingTypesCacheKey, 3600, function () use ($rankingTypeIds) {
            return RankingType::whereIn('id', $rankingTypeIds)
                ->orderBy('display_name')
                ->get();
        });

        // Get only ranking types that exist in this hall of fame (same as $rankingTypeIds)
        $allRankingTypes = $rankingTypeIds->toArray();

        // Get total number of ranking types
        $totalRankingTypes = count($allRankingTypes);

        // Calculate max players for each ranking type in a single query (cache for 1 hour)
        $maxPlayersByRankingType = Cache::remember($maxPlayersCacheKey, 3600, function () use ($hallOfFame) {
            return Ranking::where('hof_id', $hallOfFame->id)
                ->selectRaw('ranking_type_id, COUNT(*) as count')
                ->groupBy('ranking_type_id')
                ->pluck('count', 'ranking_type_id')
                ->toArray();
        });

        // Get player's position in each ranking type in a single query (cache for 1 hour)
        $playerPositions = Cache::remember($playerPositionsCacheKey, 3600, function () use ($hofUserId, $hallOfFame) {
            return \DB::table('rankings as r1')
                ->select([
                    'r1.ranking_type_id',
                    \DB::raw('(SELECT COUNT(*) + 1 FROM rankings as r2
                               WHERE r2.hof_id = r1.hof_id
                               AND r2.ranking_type_id = r1.ranking_type_id
                               AND r2.value > r1.value) as position')
                ])
                ->where('r1.hof_id', $hallOfFame->id)
                ->where('r1.hof_user_id', $hofUserId)
                ->get();
        });

        // Convert to associative array
        $rankingPositions = [];
        foreach ($playerPositions as $position) {
            $rankingPositions[$position->ranking_type_id] = $position->position;
        }

        // Set max+1 positions for rankings the player doesn't have
        foreach ($allRankingTypes as $rankingTypeId) {
            if (!isset($rankingPositions[$rankingTypeId])) {
                $rankingPositions[$rankingTypeId] = ($maxPlayersByRankingType[$rankingTypeId] ?? 0) + 1;
            }
        }

        // Calculate combined ranking score for this player
        $combinedScore = $this->calculateCombinedRankingScore($rankingPositions);

        // Get player's position in the combined ranking using the optimized method
        [$combinedPosition, $totalPlayers] = $this->calculatePlayerCombinedPosition(
            $hofUserId,
            $hallOfFame,
            $rankingPositions,
            $maxPlayersByRankingType
        );

        return view('ranking.player', compact(
            'player',
            'round',
            'hallOfFame',
            'rankings',
            'rankingTypes',
            'rankingPositions',
            'gameInstance',
            'combinedScore',
            'combinedPosition',
            'totalPlayers',
            'maxPlayersByRankingType',
            'totalRankingTypes'
        ));
    }

    #[Get(uri: '/combined-rankings/{roundId}', name: 'ranking.combined')]
    public function combinedRankings($roundId)
    {
        // Get the round with eager loading of instance
        $round = InstanceRound::with('instance')->findOrFail($roundId);

        // Get the hall of fame for this round
        $hallOfFame = HallOfFame::where('instance_round_id', $roundId)->first();

        if (!$hallOfFame) {
            return redirect()->route('ranking.index')
                ->with('error', 'Keine Hall of Fame für diese Runde gefunden.');
        }

        // Create cache keys
        $rankingTypesCacheKey = "ranking_types:{$hallOfFame->id}";
        $maxPlayersCacheKey = "max_players:{$hallOfFame->id}";
        $topPlayersCacheKey = "top_players:{$hallOfFame->id}";

        // Get only ranking types that exist in this hall of fame (cache for 1 hour)
        $rankingTypeIds = Cache::remember($rankingTypesCacheKey . ':ids', 3600, function () use ($hallOfFame) {
            return Ranking::where('hof_id', $hallOfFame->id)
                ->distinct()
                ->pluck('ranking_type_id');
        });

        // Get all ranking types with their display names
        $rankingTypes = Cache::remember($rankingTypesCacheKey, 3600, function () use ($rankingTypeIds) {
            return RankingType::whereIn('id', $rankingTypeIds)
                ->orderBy('display_name')
                ->get();
        });

        // Get total number of ranking types in this hall of fame
        $totalRankingTypes = $rankingTypes->count();

        // Calculate max players for each ranking type in a single query (cache for 1 hour)
        $maxPlayersByRankingType = Cache::remember($maxPlayersCacheKey, 3600, function () use ($hallOfFame) {
            return Ranking::where('hof_id', $hallOfFame->id)
                ->selectRaw('ranking_type_id, COUNT(*) as count')
                ->groupBy('ranking_type_id')
                ->pluck('count', 'ranking_type_id')
                ->toArray();
        });

        // Get top 1 player for each ranking type (cache for 1 hour)
        $topPlayers = Cache::remember($topPlayersCacheKey, 3600, function () use ($hallOfFame, $rankingTypes) {
            $result = [];

            foreach ($rankingTypes as $rankingType) {
                // Get the top player for this ranking type
                $topPlayer = Ranking::with(['user', 'rankingType'])
                    ->where('hof_id', $hallOfFame->id)
                    ->where('ranking_type_id', $rankingType->id)
                    ->orderByDesc('value')
                    ->first();

                if ($topPlayer) {
                    $result[$rankingType->id] = $topPlayer;
                }
            }

            return $result;
        });

        // Get game instance for breadcrumb
        $gameInstance = $round->instance;

        return view('ranking.combined', compact(
            'round',
            'hallOfFame',
            'rankingTypes',
            'topPlayers',
            'gameInstance',
            'totalRankingTypes',
            'maxPlayersByRankingType'
        ));
    }

    #[Get(uri: '/', name: 'ranking.index')]
    public function index(Request $request)
    {
        // Cache game instances for 1 hour
        $gameInstances = Cache::remember('game_instances', 3600, function () {
            return GameInstance::orderBy('name')->get();
        });

        // Default: select the primary game instance if none selected
        $selectedGameInstance = $request->input('game_instance_id');
        if (!$selectedGameInstance) {
            // Find the primary game instance
            $primaryInstance = Cache::remember('primary_game_instance', 3600, function () {
                return GameInstance::where('primary', true)->first();
            });

            if ($primaryInstance) {
                $selectedGameInstance = $primaryInstance->id;
            }
        }

        $selectedRound = $request->input('round_id');
        $selectedRankingType = $request->input('ranking_type_id');
        $search = $request->input('search');

        // Get rounds for the selected game instance (cache for 1 hour)
        $rounds = collect();
        $latestRound = null;
        if ($selectedGameInstance) {
            $roundsCacheKey = "rounds:{$selectedGameInstance}";
            $rounds = Cache::remember($roundsCacheKey, 3600, function () use ($selectedGameInstance) {
                return InstanceRound::where('game_instance_id', $selectedGameInstance)
                    ->orderBy(\DB::raw('CAST(REGEXP_REPLACE(name, "[^0-9]", "") AS UNSIGNED)'))
                    ->get();
            });

            // Find the latest round (assuming higher number means newer round)
            if ($rounds->isNotEmpty()) {
                $latestRound = $rounds->last();
            }

            // Set the selected round to the latest round
            if (!$selectedRound && $latestRound) {
                $selectedRound = $latestRound->id;
            }
        }

        // Get available ranking types for the selected round
        $rankingTypes = collect();
        $hallOfFame = null;
        $rankings = collect();

        if ($selectedRound) {
            // Get hall of fame for the selected round (only once) (cache for 1 hour)
            $hofCacheKey = "hall_of_fame:{$selectedRound}";
            $hallOfFame = Cache::remember($hofCacheKey, 3600, function () use ($selectedRound) {
                return HallOfFame::where('instance_round_id', $selectedRound)->first();
            });

            if ($hallOfFame) {
                // Create cache keys
                $rankingTypesCacheKey = "ranking_types:{$hallOfFame->id}";

                // Get ranking types that exist in this hall of fame (cache for 1 hour)
                $rankingTypeIds = Cache::remember($rankingTypesCacheKey . ':ids', 3600, function () use ($hallOfFame) {
                    return Ranking::where('hof_id', $hallOfFame->id)
                        ->distinct()
                        ->pluck('ranking_type_id');
                });

                $rankingTypes = Cache::remember($rankingTypesCacheKey, 3600, function () use ($rankingTypeIds) {
                    return RankingType::whereIn('id', $rankingTypeIds)
                        ->orderBy('display_name')
                        ->get();
                });

                // Build rankings query with eager loading
                // We don't cache the rankings query result because it depends on user input (search, filters)
                // and pagination, which would lead to too many cache keys
                $rankingsQuery = Ranking::with(['user', 'rankingType'])
                    ->where('hof_id', $hallOfFame->id);

                if ($selectedRankingType) {
                    $rankingsQuery->where('ranking_type_id', $selectedRankingType);
                }

                if ($search) {
                    $rankingsQuery->whereHas('user', function($query) use ($search) {
                        $query->where('nickname', 'like', "%{$search}%")
                            ->orWhere('coordinates', 'like', "%{$search}%")
                            ->orWhere('alliance_tag', 'like', "%{$search}%");
                    });
                }

                // Add pagination to avoid loading too many records at once
                $rankings = $rankingsQuery->orderByDesc('value')->paginate(100);
            }
        } else {
            // If no round is selected, show all ranking types (cache for 1 hour)
            $rankingTypes = Cache::remember('all_ranking_types', 3600, function () {
                return RankingType::orderBy('display_name')->get();
            });
        }

        return view('ranking.index', compact(
            'gameInstances',
            'rankingTypes',
            'rounds',
            'hallOfFame',
            'rankings',
            'selectedGameInstance',
            'selectedRound',
            'selectedRankingType',
            'search'
        ));
    }

    #[Post(uri: '/filter', name: 'ranking.filter')]
    public function filter(Request $request)
    {
        // Cache game instances for 1 hour
        $gameInstances = Cache::remember('game_instances', 3600, function () {
            return GameInstance::orderBy('name')->get();
        });

        // Default: select the primary game instance if none selected
        $selectedGameInstance = $request->input('game_instance_id');
        if (!$selectedGameInstance) {
            // Find the primary game instance
            $primaryInstance = Cache::remember('primary_game_instance', 3600, function () {
                return GameInstance::where('primary', true)->first();
            });

            if ($primaryInstance) {
                $selectedGameInstance = $primaryInstance->id;
            }
        }

        $selectedRound = $request->input('round_id');
        $selectedRankingType = $request->input('ranking_type_id');
        $search = $request->input('search');

        // Get rounds for the selected game instance (cache for 1 hour)
        $rounds = collect();
        $latestRound = null;
        if ($selectedGameInstance) {
            $roundsCacheKey = "rounds:{$selectedGameInstance}";
            $rounds = Cache::remember($roundsCacheKey, 3600, function () use ($selectedGameInstance) {
                return InstanceRound::where('game_instance_id', $selectedGameInstance)
                    ->orderBy(\DB::raw('CAST(REGEXP_REPLACE(name, "[^0-9]", "") AS UNSIGNED)'))
                    ->get();
            });

            // Find the latest round (assuming higher number means newer round)
            if ($rounds->isNotEmpty()) {
                $latestRound = $rounds->last();
            }

            // Set the selected round to the latest round if none is selected
            if (!$selectedRound && $latestRound) {
                $selectedRound = $latestRound->id;
            }
        }

        // Get available ranking types for the selected round
        $rankingTypes = collect();
        $hallOfFame = null;
        $rankings = collect();

        if ($selectedRound) {
            // Get hall of fame for the selected round (only once) (cache for 1 hour)
            $hofCacheKey = "hall_of_fame:{$selectedRound}";
            $hallOfFame = Cache::remember($hofCacheKey, 3600, function () use ($selectedRound) {
                return HallOfFame::where('instance_round_id', $selectedRound)->first();
            });

            if ($hallOfFame) {
                // Create cache keys
                $rankingTypesCacheKey = "ranking_types:{$hallOfFame->id}";

                // Get ranking types that exist in this hall of fame (cache for 1 hour)
                $rankingTypeIds = Cache::remember($rankingTypesCacheKey . ':ids', 3600, function () use ($hallOfFame) {
                    return Ranking::where('hof_id', $hallOfFame->id)
                        ->distinct()
                        ->pluck('ranking_type_id');
                });

                $rankingTypes = Cache::remember($rankingTypesCacheKey, 3600, function () use ($rankingTypeIds) {
                    return RankingType::whereIn('id', $rankingTypeIds)
                        ->orderBy('display_name')
                        ->get();
                });

                // Build rankings query with eager loading
                // We don't cache the rankings query result because it depends on user input (search, filters)
                // and pagination, which would lead to too many cache keys
                $rankingsQuery = Ranking::with(['user', 'rankingType'])
                    ->where('hof_id', $hallOfFame->id);

                if ($selectedRankingType) {
                    $rankingsQuery->where('ranking_type_id', $selectedRankingType);
                }

                if ($search) {
                    $rankingsQuery->whereHas('user', function($query) use ($search) {
                        $query->where('nickname', 'like', "%{$search}%")
                            ->orWhere('coordinates', 'like', "%{$search}%")
                            ->orWhere('alliance_tag', 'like', "%{$search}%");
                    });
                }

                // Add pagination to avoid loading too many records at once
                $rankings = $rankingsQuery->orderByDesc('value')->paginate(100);
            }
        } else {
            // If no round is selected, show all ranking types (cache for 1 hour)
            $rankingTypes = Cache::remember('all_ranking_types', 3600, function () {
                return RankingType::orderBy('display_name')->get();
            });
        }

        // If this is an HTMX request for updating rounds dropdown
        if ($request->header('HX-Trigger-Name') === 'game_instance_id') {
            return view('ranking.partials.rounds_dropdown', compact('rounds', 'selectedRound'));
        }

        // Log all requests for debugging
        \Log::info('Filter method called', [
            'selectedRound' => $selectedRound,
            'hallOfFame' => $hallOfFame ? $hallOfFame->id : null,
            'rankingTypeIds' => $rankingTypes->pluck('id')->toArray(),
            'rankingTypeCount' => $rankingTypes->count(),
            'headers' => $request->headers->all(),
            'HX-Trigger' => $request->header('HX-Trigger'),
            'HX-Trigger-Name' => $request->header('HX-Trigger-Name'),
            'HX-Target' => $request->header('HX-Target'),
            'HX-Current-URL' => $request->header('HX-Current-URL')
        ]);

        // If this is an HTMX request for updating ranking types dropdown
        if ($request->header('HX-Target') === 'ranking_type_id') {
            // Log for debugging
            \Log::info('HTMX request for updating ranking types dropdown', [
                'selectedRound' => $selectedRound,
                'hallOfFame' => $hallOfFame ? $hallOfFame->id : null,
                'rankingTypeIds' => $rankingTypes->pluck('id')->toArray(),
                'rankingTypeCount' => $rankingTypes->count(),
                'headers' => $request->headers->all()
            ]);

            return view('ranking.partials.ranking_types_dropdown', compact('rankingTypes', 'selectedRankingType'));
        }

        return view('ranking.partials.content', compact(
            'gameInstances',
            'rankingTypes',
            'rounds',
            'hallOfFame',
            'rankings',
            'selectedGameInstance',
            'selectedRound',
            'selectedRankingType',
            'search'
        ));
    }
}
