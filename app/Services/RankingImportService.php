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
     * Import a round by name ("Runde X") or number.
     *
     * @param string|null $roundName   e.g. "Runde 72"
     * @param int|null    $roundNumber if omitted, extracted from roundName
     * @return void
     */
    public function importRound(string $roundName = null, int $roundNumber = null): void
    {
        // Determine round number and name
        if ($roundNumber === null) {
            if (!preg_match('/Runde\s*(\d+)/i', $roundName, $matches)) {
                throw new \InvalidArgumentException("Ungültiges Round-Format: {$roundName}");
            }
            $roundNumber = (int) $matches[1];
        }
        $roundName = $roundName ?? "Runde {$roundNumber}";

        // Set legacy connection database (configured in .env as 'game')
        config(['database.connections.legacy.database' => config('database.connections.game.database')]);
        DB::purge('legacy');

        DB::beginTransaction();
        try {
            // Create or update ranking meta
            $round = EternalRanking::updateOrCreate(
                ['round_number' => $roundNumber],
                ['round_name'   => $roundName]
            );

            // Fetch and import rows
            $rows = $this->fetchSourceRows($roundNumber);
            foreach ($rows as $row) {
                // Resolve player
                $player = EternalRankingPlayer::updateOrCreate(
                    ['email_hash' => $row['email_hash']],
                    ['nickname'   => $row['nickname']]
                );

                // Upsert result
                EternalRankingResult::updateOrCreate(
                    ['player_id'  => $player->id, 'ranking_id' => $round->id],
                    ['score'      => $row['score']]
                );
            }

            // Update percentages
            $this->recalculatePercentages($round->id);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Fehler beim Import Runde {$roundNumber}: {$e->getMessage()}", [
                'roundNumber' => $roundNumber,
                'roundName'   => $roundName,
                'exception'   => $e,
            ]);
        }
    }

    /**
     * Fetch source rows according to legacy schema.
     *
     * @param int $roundNumber
     * @return array<int, array{email_hash:string, nickname:string, score:int}>
     */
    protected function fetchSourceRows(int $roundNumber): array
    {
        $conn = DB::connection('legacy');
        if ($roundNumber <= 63) {
            return $conn->table('gn_hof')
                ->selectRaw('email_hashed as email_hash, nick as nickname, ?? as score', ["runde{$roundNumber}"])
                ->get()
                ->map(fn($r) => (array) $r)
                ->toArray();
        }

        // New source schema
        return $conn->table('gn_users as u')
            ->join('gn_user_details as d', 'u.id', 'd.id')
            ->selectRaw('md5(lower(trim(d.email))) as email_hash, u.nickname as nickname, u.macht as score')
            ->get()
            ->map(fn($r) => (array) $r)
            ->toArray();
    }

    /**
     * Recalculate percentage scores.
     *
     * @param string $rankingId
     * @return void
     */
    protected function recalculatePercentages(string $rankingId): void
    {
        $maxScore = EternalRankingResult::where('ranking_id', $rankingId)->max('score');

        if ($maxScore > 0) {
            EternalRankingResult::where('ranking_id', $rankingId)
                ->orderBy('id')
                ->chunkById(500, function ($batch) use ($maxScore) {
                    foreach ($batch as $result) {
                        $result->pct = round($result->score / $maxScore * 100, 3);
                        $result->save();
                    }
                });
        } else {
            EternalRankingResult::where('ranking_id', $rankingId)
                ->update(['pct' => null]);
        }
    }
}
