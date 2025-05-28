<?php

namespace App\Console\Commands;

use App\Models\GameInstance;
use App\Models\HallOfFame;
use App\Models\HofUser;
use App\Models\InstanceRound;
use App\Models\Ranking;
use App\Models\RankingType;
use App\Models\ServerInstance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ImportLegacyHofCommand extends Command
{
    protected $signature = 'hof:legacy_import {path : Wurzelverzeichnis oder einzelnes Rundeverzeichnis}';
    protected $description = 'Importiert HOF-Runden und passt neue FKs an';

    protected $ignoredDirs = ['allgemein', 'allgemein.bak-r14', 'allgemein_lan', 'allgemein_old'];

    public function handle()
    {
        $root = rtrim($this->argument('path'), DIRECTORY_SEPARATOR);
        if (!File::isDirectory($root)) {
            $this->error("Pfad '$root' existiert nicht oder ist kein Verzeichnis.");
            return Command::FAILURE;
        }

        $roundDirs = File::directories($root);
        $toImport = empty($roundDirs) ? [$root] : $roundDirs;

        $report = [];
        $allIssues = [];
        foreach ($toImport as $dir) {
            $basename = basename($dir);
            if (in_array($basename, $this->ignoredDirs, true)) {
                continue;
            }

            list($status, $issues) = $this->importDirectory($dir);
            $report[] = $status;
            $allIssues = array_merge($allIssues, $issues);
        }

        // Ausgabe
        $this->info("\nImport-Übersicht:");
        $this->table(
            ['Runde', 'HOF ID', 'Einträge', 'Warn', 'Fehler'],
            array_map(fn($r) => [
                $r['round'],
                $r['hof_id'],
                $r['entries'],
                $r['warnings'],
                $r['errors'],
            ], $report)
        );

        if (!empty($allIssues)) {
            $this->info("\nDetailierte Issues:");
            foreach ($allIssues as $issue) {
                $this->line($issue);
            }
        }

        return Command::SUCCESS;
    }

    protected function importDirectory(string $dir): array
    {
        $rawName = basename($dir);
        $roundName = Str::of($rawName)->replace('_', ' ')->title();
        $warnings = [];
        $errors = [];
        $entries = 0;
        $hofId = null;

        DB::beginTransaction();
        try {
            // Server und GameInstance
            $server = ServerInstance::firstOrCreate(
                ['server_name' => 'Default'],
                ['database_host' => 'localhost',
                    'database_name' => 'galaxy_hof',
                    'database_user' => 'root',
                    'database_password' => '']
            );

            // Find the primary instance or create it if it doesn't exist
            $instance = GameInstance::where('primary', true)->first();
            if (!$instance) {
                $instance = GameInstance::create([
                    'name' => 'Default',
                    'primary' => true,
                    'server_instance_id' => $server->id
                ]);
            }

            // Runde und HoF
            $round = InstanceRound::create([
                'game_instance_id' => $instance->id,
                'name' => $roundName,
                'start_date' => now(),
                'end_date' => now(),
            ]);

            $hof = HallOfFame::create([
                'instance_round_id' => $round->id,
                'name' => $roundName,
            ]);

            $hofId = $hof->id;

            // Cleanup Nicht-.dat-Dateien
            foreach (File::files($dir) as $f) {
                if (!Str::endsWith($f->getFilename(), '.dat')) {
                    File::delete($f->getPathname());
                }
            }

            $datFiles = array_filter(
                File::files($dir),
                fn($f) => Str::endsWith($f->getFilename(), '.dat')
                    && !Str::endsWith($f->getFilename(), '_Zone.Identifier')
            );

            if (empty($datFiles)) {
                throw new \Exception('Keine .dat Dateien gefunden');
            }

            // First process einzel.dat to extract ranking type names
            $rankingTypeNames = [];
            $einzelFile = null;

            foreach ($datFiles as $file) {
                if ($file->getFilename() === 'einzel.dat') {
                    $einzelFile = $file;
                    $lines = File::lines($file->getPathname())
                        ->filter(fn($l) => trim($l) !== '')
                        ->map(function($l) {
                            $l = mb_convert_encoding($l, 'UTF-8', 'ISO-8859-1');
                            // Special handling for «NL» to ensure correct encoding
                            if (strpos($l, 'NL') !== false) {
                                $l = str_replace(['Â«NLÂ»', '«NL»'], '«NL»', $l);
                            }
                            return $l;
                        })
                        ->values();

                    foreach ($lines as $line) {
                        $parts = explode(';', trim($line));
                        if (count($parts) >= 6) {
                            // Extract ranking type name (4th value) and key (6th value)
                            $rankingName = trim($parts[3]);
                            $rankingKey = trim($parts[5]);
                            $rankingTypeNames[$rankingKey] = $rankingName;
                        }
                    }

                    $this->info("Extracted " . count($rankingTypeNames) . " ranking type names from einzel.dat");
                    break;
                }
            }

            // Import
            $userMap = [];

            foreach ($datFiles as $file) {
                // Skip einzel.dat for actual data import
                if ($file->getFilename() === 'einzel.dat') {
                    continue;
                }

                $key = Str::before($file->getFilename(), '.dat');
                $displayName = $rankingTypeNames[$key] ?? ucfirst($key);

                $rt = RankingType::firstOrCreate(
                    ['key_name' => $key],
                    ['display_name' => $displayName,
                        'type' => 'resource']
                );

                $lines = File::lines($file->getPathname())
                    ->filter(fn($l) => trim($l) !== '')
                    ->map(function($l) {
                        $l = mb_convert_encoding($l, 'UTF-8', 'ISO-8859-1');
                        // Special handling for «NL» to ensure correct encoding
                        if (strpos($l, 'NL') !== false) {
                            $l = str_replace(['Â«NLÂ»', '«NL»'], '«NL»', $l);
                        }
                        return $l;
                    })
                    ->values();

                // Ensure all strings are properly encoded as UTF-8
                $this->info("Processing file: " . $file->getFilename() . " with " . count($lines) . " lines");

                foreach ($lines as $pos => $raw) {
                    $parts = explode(';', trim($raw));

                    if (count($parts) === 5) {
                        [$_pos, $nick, $coord, $tag, $val] = array_map('trim', $parts);
                    } elseif (count($parts) === 6) {
                        [$_pos, $nick, $coord, $t1, $t2, $val] = array_map('trim', $parts);
                        $tag = "$t1;$t2";
                    } else {
                        $warnings[] = "[$roundName][$key] Zeile $pos hat " . count($parts) . " Teile";
                        continue;
                    }

                    $uniqueKey = "$nick|$coord";

                    if (!isset($userMap[$uniqueKey])) {
                        // Data is already converted to UTF-8 in the lines processing
                        $nickname = $nick;
                        $coordinates = $coord;
                        $allianceTag = $tag ?: null;

                        $user = HofUser::firstOrCreate(
                            ['nickname' => $nickname,
                                'coordinates' => $coordinates,
                                'hof_id' => $hof->id],
                            ['alliance_tag' => $allianceTag]
                        );

                        $userMap[$uniqueKey] = $user->id;
                    }

                    Ranking::create([
                        'hof_id' => $hof->id,
                        'hof_user_id' => $userMap[$uniqueKey],
                        'ranking_type_id' => $rt->id,
                        'value' => (int)$val,
                    ]);

                    $entries++;
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $errors[] = "[$roundName] Fehler: " . $e->getMessage();
        }

        return [
            [
                'round' => $roundName,
                'hof_id' => $hofId,
                'entries' => $entries,
                'warnings' => count($warnings),
                'errors' => count($errors),
            ],
            array_merge($warnings, $errors)
        ];
    }
}
