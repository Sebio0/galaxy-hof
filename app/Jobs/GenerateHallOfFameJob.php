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
                    'u.id', 'u.nick', 'd.email',
                    'u.crystal', 'u.metal', 'u.ex_crystal', 'u.ex_metal',
                    'u.macht', 'u.roids', 'u.amp', 'u.blocker'
                ])
                ->get();

            foreach ($users as $user) {
                // Sum fleet s1..s10
                $fleet = DB::connection($connectionName)
                    ->table('gn_flotte')
                    ->where('owner_id', $user->id)
                    ->selectRaw('SUM(s1) as s1, SUM(s2) as s2, SUM(s3) as s3, SUM(s4) as s4, SUM(s5) as s5, SUM(s6) as s6, SUM(s7) as s7, SUM(s8) as s8, SUM(s9) as s9, SUM(s10) as s10')
                    ->first();

                // Sum defence g1..g6
                $defense = DB::connection($connectionName)
                    ->table('gn_defence')
                    ->where('owner_id', $user->id)
                    ->selectRaw('SUM(g1) as g1, SUM(g2) as g2, SUM(g3) as g3, SUM(g4) as g4, SUM(g5) as g5, SUM(g6) as g6')
                    ->first();

                // Create HofUser
                $hofUser = HofUser::create([
                    'id' => Str::uuid()->toString(),
                    'hof_id' => $hof->id,
                    'nickname' => $user->nick,
                    'coordinates' => '', // falls aus DB notwendig, ggf anpassen
                    'alliance_tag' => null,
                ]);

                // Basic rankings
                $basic = [
                    'crystal' => $user->crystal,
                    'metal' => $user->metal,
                    'ex_crystal' => $user->ex_crystal,
                    'ex_metal' => $user->ex_metal,
                    'macht' => $user->macht,
                    'roids' => $user->roids,
                    'amp' => $user->amp,
                    'blocker' => $user->blocker,
                ];

                foreach ($basic as $key => $value) {
                    Log::debug('Ranking: ' . $key . ' => ' . $value);
                    Ranking::create([
                        'id' => Str::uuid()->toString(),
                        'hof_id' => $this->data['hof_id'],
                        'hof_user_id' => $hofUser->id,
                        'ranking_type_id' => RankingType::where('key_name', $key)->select('id')->firstOrFail()->id,
                        'value' => $value,
                    ]);
                }

                // Fleet rankings s1..s10
// Fleet rankings s1..s10
                for ($i = 1; $i <= 10; $i++) {
                    $key = 's' . $i;
                    if($key === 's8'){
                        // Skip s8 (Kommandoschiff)
                        continue;
                    }
                    Ranking::create([
                        'id' => Str::uuid()->toString(),
                        'hof_user_id' => $hofUser->id,
                        'hof_id'          => $hof->id,
                        'ranking_type_id' => RankingType::where('key_name', $key)->select('id')->firstOrFail()->id,
                        'value' => $fleet->{$key} ?? 0,
                    ]);
                }

// Defense rankings g1..g6
                for ($i = 1; $i <= 6; $i++) {
                    $key = 'g' . $i;
                    if($key === 'g6'){
                        // skip g6 raumbasis
                        continue;
                    }
                    Ranking::create([
                        'id' => Str::uuid()->toString(),
                        'hof_user_id' => $hofUser->id,
                        'hof_id'          => $hof->id,
                        'ranking_type_id' => RankingType::where('key_name', $key)->select('id')->firstOrFail()->id,
                        'value' => $defense->{$key} ?? 0,
                    ]);
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
