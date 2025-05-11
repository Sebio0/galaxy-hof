<?php

namespace App\Services;

use App\Models\EternalRanking;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class RankingImportService
{
    /**
     * Import a single round, sourcing from gn_hof or gn_users based on roundNumber
     *
     * @param int $roundNumber
     * @return void
     */
    public function importSingleRound(int $roundNumber): void
    {
        $database = $roundNumber <= 63 ? 'gn_legacy' : "galaxy_hof_r{$roundNumber}";

        try {
            config(['database.connections.legacy.database' => $database]);
            DB::purge('legacy');

            $round = EternalRanking::firstOrCreate(
                ['round_number' => $roundNumber],
                ['round_name' => "Runde {$roundNumber}"]
            );

            if ($roundNumber <= 63) {
                $rows = DB::connection('legacy')
                    ->table('gn_hof')
                    ->select('email_hashed', 'nick', DB::raw("runde{$roundNumber} as score"))
                    ->get();

                foreach ($rows as $row) {
                    $player = EternalRankingPlayer::firstOrCreate(
                        ['email_hash' => $row->email_hashed],
                        ['nickname' => $row->nick]
                    );

                    EternalRankingResult::updateOrCreate(
                        ['player_id' => $player->id, 'ranking_id' => $round->id],
                        ['score' => $row->score]
                    );
                }
            } else {
                $rows = DB::connection('legacy')
                    ->table('gn_users as u')
                    ->join('gn_user_details as d', 'u.id', '=', 'd.id')
                    ->select('u.nickname', 'u.macht as score', 'd.email')
                    ->get();

                foreach ($rows as $row) {
                    $emailHash = md5(strtolower(trim($row->email)));
                    $player = EternalRankingPlayer::firstOrCreate(
                        ['email_hash' => $emailHash],
                        ['nickname' => $row->nickname]
                    );

                    EternalRankingResult::updateOrCreate(
                        ['player_id' => $player->id, 'ranking_id' => $round->id],
                        ['score' => $row->score]
                    );
                }
            }

            $this->recalculatePercentages($round->id);
        } catch (Exception $e) {
            Log::error("Fehler beim Importieren von Runde {$roundNumber}: " . $e->getMessage(), [
                'roundNumber' => $roundNumber,
                'database' => $database,
                'exception' => $e
            ]);
        }
    }

    public function importFromDatabase(string $database, string $roundName, int $roundNumber): void
    {
        try {
            config(['database.connections.legacy.database' => $database]);
            DB::purge('legacy');

            $round = EternalRanking::firstOrCreate(
                ['round_name' => $roundName, 'round_number' => $roundNumber],
            );

            if ($roundNumber <= 63) {
                $rows = DB::connection('legacy')
                    ->table('gn_hof')
                    ->select('email_hashed', 'nick', DB::raw("runde{$roundNumber} as score"))
                    ->get();

                foreach ($rows as $row) {
                    $player = EternalRankingPlayer::firstOrCreate(
                        ['email_hash' => $row->email_hashed],
                        ['nickname' => $row->nick]
                    );

                    EternalRankingResult::updateOrCreate(
                        ['player_id' => $player->id, 'ranking_id' => $round->id],
                        ['score' => $row->score]
                    );
                }
            } else {
                $rows = DB::connection('legacy')
                    ->table('gn_users as u')
                    ->join('gn_user_details as d', 'u.id', '=', 'd.id')
                    ->select('u.nickname', 'u.macht as score', 'd.email')
                    ->get();

                foreach ($rows as $row) {
                    $emailHash = md5(strtolower(trim($row->email)));
                    $player = EternalRankingPlayer::firstOrCreate(
                        ['email_hash' => $emailHash],
                        ['nickname' => $row->nickname]
                    );

                    EternalRankingResult::updateOrCreate(
                        ['player_id' => $player->id, 'ranking_id' => $round->id],
                        ['score' => $row->score]
                    );
                }
            }

            $this->recalculatePercentages($round->id);
        } catch (Exception $e) {
            Log::error("Fehler beim Importieren aus Datenbank '{$database}' für Runde {$roundNumber}: " . $e->getMessage(), [
                'roundName' => $roundName,
                'roundNumber' => $roundNumber,
                'database' => $database,
                'exception' => $e
            ]);
        }
    }

    private function recalculatePercentages(int $rankingId): void
    {
        $maxScore = EternalRankingResult::where('ranking_id', $rankingId)->max('score');

        if ($maxScore > 0) {
            EternalRankingResult::where('ranking_id', $rankingId)
                ->orderBy('id')
                ->chunkById(500, function ($results) use ($maxScore) {
                    foreach ($results as $result) {
                        $pct = round(($result->score / $maxScore) * 100, 3);
                        $result->pct = $pct;
                        $result->save();
                    }
                });
        } else {
            EternalRankingResult::where('ranking_id', $rankingId)
                ->update(['pct' => null]);
        }
    }
}
