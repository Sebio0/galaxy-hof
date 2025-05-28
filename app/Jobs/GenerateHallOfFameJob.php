<?php

namespace App\Jobs;

use App\Models\GameInstance;
use App\Models\HallOfFame;
use App\Models\HofUser;
use App\Models\InstanceRound;
use App\Models\Ranking;
use App\Models\RankingType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\Logger;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GenerateHallOfFameJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;

    /**
     * Create a new job instance.
     *
     * @param array $data
     *   Expected keys: game_instance_id, round_id, hof_id, round_name, start_date, end_date
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Load game and server instance
        $game = GameInstance::findOrFail($this->data['game_instance_id']);
        $server = $game->serverInstance;

        // Configure dynamic connection for source (gn_)
        $connectionName = 'gn_source';

        Config::set("database.connections.{$connectionName}", [
            'driver' => 'mysql',
            'host' => $server->database_host,
            'database' => $server->database_name,
            'username' => $server->database_user,
            'password' => $server->database_password,
            'charset' => 'utf8mb3',
            'collation' => 'utf8mb3_unicode_ci',
        ]);
        DB::purge($connectionName);

        // Begin target DB transaction
        DB::transaction(function () use ($connectionName) {
            $round = InstanceRound::firstOrCreate(
                ['id' => $this->data['round_id']],
                [
                    'game_instance_id' => $this->data['game_instance_id'],
                    'name' => $this->data['round_name'],
                    'start_date' => $this->data['start_date'],
                    'end_date' => $this->data['end_date'],
                ]
            );
            $hof = HallOfFame::create([
                'instance_round_id' => $round->id,
                'name' => $this->data['round_name'],
            ]);

            // Fetch users from source DB
            $users = DB::connection($connectionName)
                ->table('gn_users as u')
                ->join('gn_users_detail as d', 'u.id', '=', 'd.id')
                ->select([
                    'u.id', 'u.nick', 'd.email'
                ])
                ->get();

            // Get all ranking types and group them by type
            $rankingTypes = RankingType::all()->groupBy('type');

            foreach ($users as $user) {
                // Create HofUser
                $hofUser = HofUser::create([
                    'id' => Str::uuid()->toString(),
                    'hof_id' => $hof->id,
                    'nickname' => $user->nick,
                    'coordinates' => '', // falls aus DB notwendig, ggf anpassen
                    'alliance_tag' => null,
                ]);

                // Process rankings based on their type
                foreach ($rankingTypes as $type => $typeRankings) {
                    $sourceTable = '';
                    $sourceData = null;

                    // Determine the source table based on the ranking type
                    switch ($type) {
                        case 'fleet':
                            $sourceTable = 'gn_flotte';
                            break;
                        case 'defense':
                            $sourceTable = 'gn_defence';
                            break;
                        case 'resource':
                        case 'misc':
                            $sourceTable = 'gn_users';
                            break;
                        default:
                            Log::warning("Unknown ranking type: {$type}");
                            continue 2; // Skip to the next type
                    }

                    // Extract the keys we need to fetch from this table
                    $keys = $typeRankings->pluck('key_name')->toArray();

                    if (empty($keys)) {
                        continue; // Skip if no keys for this type
                    }

                    // Fetch data from the appropriate table
                    if ($type === 'fleet' || $type === 'defense') {
                        // For fleet and defense, we need to sum the values
                        $selectFields = [];
                        foreach ($keys as $key) {
                            $selectFields[] = "SUM({$key}) as {$key}";
                        }

                        $sourceData = DB::connection($connectionName)
                            ->table($sourceTable)
                            ->where('owner_id', $user->id)
                            ->selectRaw(implode(', ', $selectFields))
                            ->first();
                    } else {
                        // For resource and misc, we fetch directly from gn_users
                        $sourceData = DB::connection($connectionName)
                            ->table($sourceTable)
                            ->where('id', $user->id)
                            ->select($keys)
                            ->first();
                    }

                    if (!$sourceData) {
                        continue; // Skip if no data found
                    }

                    // Process the rankings for this type
                    foreach ($typeRankings as $rankingType) {
                        $key = $rankingType->key_name;

                        // Skip specific keys if needed
                        if (($type === 'fleet' && $key === 's8') ||
                            ($type === 'defense' && $key === 'g6')) {
                            continue;
                        }

                        if (property_exists($sourceData, $key)) {
                            $value = $sourceData->$key ?? 0;
                            Log::debug("Ranking ({$type}): {$key} => {$value}");

                            Ranking::create([
                                'id' => Str::uuid()->toString(),
                                'hof_id' => $hof->id,
                                'hof_user_id' => $hofUser->id,
                                'ranking_type_id' => $rankingType->id,
                                'value' => $value,
                            ]);
                        }
                    }
                }
            }
        });
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        // TODO: Notify admins or log the error
    }
}
