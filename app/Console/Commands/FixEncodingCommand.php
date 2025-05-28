<?php

namespace App\Console\Commands;

use App\Models\HofUser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixEncodingCommand extends Command
{
    protected $signature = 'hof:fix-encoding';
    protected $description = 'Fix encoding issues in the database for alliance tags and nicknames';

    public function handle()
    {
        $this->info('Fixing encoding issues in the database...');

        // Get all HofUser records with potential encoding issues
        $users = HofUser::where('alliance_tag', 'like', '%Ã%')
            ->orWhere('nickname', 'like', '%Ã%')
            ->get();

        $this->info("Found {$users->count()} records with potential encoding issues.");

        $fixed = 0;
        foreach ($users as $user) {
            $originalNickname = $user->nickname;
            $originalAllianceTag = $user->alliance_tag;

            // Fix double-encoded strings by converting them back to UTF-8
            $fixedNickname = $this->fixDoubleEncoding($originalNickname);
            $fixedAllianceTag = $this->fixDoubleEncoding($originalAllianceTag);

            if ($fixedNickname !== $originalNickname || $fixedAllianceTag !== $originalAllianceTag) {
                $user->nickname = $fixedNickname;
                $user->alliance_tag = $fixedAllianceTag;
                $user->save();

                $this->line("Fixed user {$user->id}:");
                if ($fixedNickname !== $originalNickname) {
                    $this->line("  Nickname: '{$originalNickname}' -> '{$fixedNickname}'");
                }
                if ($fixedAllianceTag !== $originalAllianceTag) {
                    $this->line("  Alliance: '{$originalAllianceTag}' -> '{$fixedAllianceTag}'");
                }

                $fixed++;
            }
        }

        $this->info("Fixed encoding issues in {$fixed} records.");
        return Command::SUCCESS;
    }

    /**
     * Fix double-encoded UTF-8 strings
     *
     * @param string|null $string The potentially double-encoded string
     * @return string|null The fixed string
     */
    protected function fixDoubleEncoding(?string $string): ?string
    {
        if ($string === null) {
            return null;
        }

        // Check if the string contains typical double-encoding patterns
        if (strpos($string, 'Ã') !== false) {
            // First convert to ISO-8859-1, then back to UTF-8
            // This reverses the effect of double UTF-8 encoding
            return utf8_decode($string);
        }

        return $string;
    }
}
