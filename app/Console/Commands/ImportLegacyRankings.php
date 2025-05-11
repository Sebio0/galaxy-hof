<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class ImportLegacyRankings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'legacy:import-rankings {file=database.txt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import legacy ranking data into the eternal ranking tables';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $path = base_path($this->argument('file'));

        if (!file_exists($path)) {
            $this->error("File not found: {$path}");
            return 1;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $stats = [];

        foreach ($lines as $line) {
            [$roundName, $legacyDb] = array_map('trim', explode('|', $line));

            // switch legacy DB connection
            Config::set('database.connections.legacy.database', $legacyDb);
            DB::purge('legacy');
            DB::reconnect('legacy');

            $this->info("Processing {$roundName} from {$legacyDb}...");

            // ensure ranking record exists
            $ranking = DB::table('eternal_rankings')
                ->where('round_name', $roundName)
                ->first();

            if (!$ranking) {
                $rankingId = (string) Str::ulid();
                DB::table('eternal_rankings')->insert([
                    'id' => $rankingId,
                    'round_number' => intval(preg_replace('/[^0-9]/', '', $roundName)),
                    'round_name' => $roundName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                $rankingId = $ranking->id;
            }

            $imported = 0;
            $updated = 0;

            // fetch legacy user data
            $rows = DB::connection('legacy')
                ->table('gn_users as u')
                ->join('gn_users_detail as d', 'u.id', '=', 'd.id')
                ->select('u.nick', 'd.email', 'u.macht')
                ->get();

            foreach ($rows as $row) {
                $emailHash = md5(strtolower(trim($row->email)));

                // upsert player
                $player = DB::table('eternal_ranking_players')
                    ->where('email_hash', $emailHash)
                    ->first();

                if (!$player) {
                    $playerId = (string) Str::ulid();
                    DB::table('eternal_ranking_players')->insert([
                        'id' => $playerId,
                        'email_hash' => $emailHash,
                        'nickname' => $row->nick,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $playerId = $player->id;
                    // optionally update nickname
                    DB::table('eternal_ranking_players')
                        ->where('id', $playerId)
                        ->update(['nickname' => $row->nick, 'updated_at' => now()]);
                }

                // upsert result
                $exists = DB::table('eternal_ranking_results')
                    ->where('player_id', $playerId)
                    ->where('ranking_id', $rankingId)
                    ->exists();

                if ($exists) {
                    DB::table('eternal_ranking_results')
                        ->where('player_id', $playerId)
                        ->where('ranking_id', $rankingId)
                        ->update([
                            'score'      => $row->macht,
                            'updated_at' => now(),
                        ]);
                    $updated++;
                } else {
                    DB::table('eternal_ranking_results')->insert([
                        'id'         => (string) Str::ulid(),
                        'player_id'  => $playerId,
                        'ranking_id' => $rankingId,
                        'score'      => $row->macht,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $imported++;
                }
            }

            $stats[] = [
                'Runde'    => $roundName,
                'Importiert' => $imported,
                'Aktualisiert' => $updated,
            ];

            $this->info("Done {$roundName}: imported={$imported}, updated={$updated}");
        }

        // Display summary table
        $this->table(
            ['Runde', 'Importiert', 'Aktualisiert'],
            $stats
        );

        $this->info('Import completed successfully.');

        return 0;
    }
}
