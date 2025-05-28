<?php
namespace App\Service;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\{HallOfFame, InstanceRound, HofUser, RankingType, Ranking, ServerInstance};

class HallOfFameImportService
{
    protected string $connectionName;

    public function __construct(string $serverInstanceId)
    {
        $connName = 'source_'.Str::slug($serverInstanceId);
        // Dynamische Datenbank-Verbindung konfigurieren
        $source = ServerInstance::findOrFail($serverInstanceId);

        config()->set("database.connections.$connName", [
            'driver'    => 'mysql',
            'host'      => $source->database_host,
            'database'  => $source->database_name,
            'username'  => $source->database_user,
            'password'  => $source->database_password,
            'charset'   => 'utf8mb3',
            'collation' => 'utf8mb3_unicode_ci',
        ]);

        $this->connectionName = $connName;
    }

    public function import(string $gameInstanceId, string $roundName): void
    {
        // 1) Runde anlegen
        $round = InstanceRound::create([
            'id'               => Str::uuid()->toString(),
            'name'             => $roundName,
            'game_instance_id' => $gameInstanceId,
            'start_date'       => now(),
            'end_date'         => now(),
        ]);

        // 2) HallOfFame anlegen
        $hof = HallOfFame::create([
            'id'                => Str::uuid()->toString(),
            'instance_round_id' => $round->id,
            'name'              => $roundName,
        ]);

        // 3) Begin Transaction auf Quell-DB
        DB::connection($this->connectionName)->beginTransaction();

        try {
            // 4) Benutzer-Grunddaten & Details
            $users = DB::connection($this->connectionName)
                ->table('gn_users as u')
                ->join('gn_users_detail as d', 'u.id', '=', 'd.id')
                ->select([
                    'u.id', 'u.nick','u.amp', 'u.blocker', 'u.crystal', 'u.metal', 'u.ex_metal', 'u.ex_crystal', 'u.macht', 'u.roids',
                    'u.galid', 'u.placeid', 'd.email'
                ])
                ->get();

            // 5) Flotten-Summen (s1-s10)
            $fleetSums = DB::connection($this->connectionName)
                ->table('gn_flotte')
                ->selectRaw('owner_id, ' . implode(', ', array_map(fn($i) => "SUM(s$i) AS s$i", range(1,10))))
                ->groupBy('owner_id')
                ->get()
                ->keyBy('owner_id');

            // 6) Defence-Summen (g1-g6)
            $defenceSums = DB::connection($this->connectionName)
                ->table('gn_defence')
                ->selectRaw('owner_id, ' . implode(', ', array_map(fn($i) => "SUM(g$i) AS g$i", range(1,6))))
                ->groupBy('owner_id')
                ->get()
                ->keyBy('owner_id');

            // 7) RankingType IDs cachen
            $rankingTypeIds = RankingType::pluck('id', 'key_name')->toArray();

            // 8) Durch alle Benutzer iterieren
            foreach ($users as $u) {
                // 8a) HofUser anlegen
                $hofUser = HofUser::create([
                    'id'           => Str::uuid()->toString(),
                    'hof_id'       => $hof->id,
                    'nickname'     => $this->ensureUtf8Encoding($u->nick),
                    'coordinates'  => $this->ensureUtf8Encoding("{$u->galid}:{$u->placeid}"),
                    'alliance_tag' => $this->ensureUtf8Encoding(
                            DB::connection($this->connectionName)
                            ->table('gn_galaxy')
                            ->where('galaxy', $u->galid)
                            ->value('tag') ?? null
                        ),
                ]);

                // 8b) Basis-Rankings
                foreach (['crystal', 'metal', 'ex_crystal', 'ex_metal', 'macht', 'roids', 'blocker', 'amp'] as $col) {
                    Ranking::create([
                        'id'              => Str::uuid()->toString(),
                        'hof_user_id'     => $hofUser->id,
                        'ranking_type_id' => $rankingTypeIds[$col],
                        'value'           => $u->{$col},
                    ]);
                }

                // 8c) Flotten-Rankings
                if (isset($fleetSums[$u->id])) {
                    foreach (range(1, 10) as $i) {
                        Ranking::create([
                            'id'              => Str::uuid()->toString(),
                            'hof_user_id'     => $hofUser->id,
                            'ranking_type_id' => $rankingTypeIds['s'.$i],
                            'value'           => $fleetSums[$u->id]->{'s'.$i},
                        ]);
                    }
                }

                // 8d) Defence-Rankings
                if (isset($defenceSums[$u->id])) {
                    foreach (range(1, 6) as $i) {
                        Ranking::create([
                            'id'              => Str::uuid()->toString(),
                            'hof_user_id'     => $hofUser->id,
                            'ranking_type_id' => $rankingTypeIds['g'.$i],
                            'value'           => $defenceSums[$u->id]->{'g'.$i},
                        ]);
                    }
                }
            }

            // 9) Commit
            DB::connection($this->connectionName)->commit();
        } catch (Exception $e) {
            // 10) Rollback und weiterwerfen
            DB::connection($this->connectionName)->rollBack();
            throw $e;
        }
    }

    /**
     * Ensures that a string is properly encoded in UTF-8.
     * Converts from ISO-8859-1 if necessary.
     *
     * @param string|null $string The string to ensure encoding for
     * @return string|null The properly encoded string
     */
    protected function ensureUtf8Encoding(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }

        // Always convert from ISO-8859-1 to UTF-8 to ensure proper encoding
        return mb_convert_encoding($string, 'UTF-8', 'ISO-8859-1');
    }
}
