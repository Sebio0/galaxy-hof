<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\EternalRankingResult;

class CalculateRankingPercentageJob
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $rankingId;

    public function __construct(string $rankingId)
    {
        $this->rankingId = $rankingId;
    }

    public function handle()
    {
        $max = EternalRankingResult::where('ranking_id', $this->rankingId)->max('score');

        if (is_null($max) || $max === 0) {
            EternalRankingResult::where('ranking_id', $this->rankingId)
                ->update(['pct' => 0.00]);
        } else {
            EternalRankingResult::where('ranking_id', $this->rankingId)
                ->update(['pct' => \DB::raw("(score / {$max} * 100)")]);
        }
    }
}
