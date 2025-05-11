<?php

namespace App\Filament\Resources\EternalRankingPlayerResource\Pages;

use App\Filament\Resources\EternalRankingPlayerResource;
use App\Models\EternalRankingPlayer;
use App\Models\EternalRankingResult;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;

class MergePlayers extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = EternalRankingPlayerResource::class;

    protected static string $view = 'filament.pages.merge-players';

    public ?string $sourceEmail = null;
    public ?string $targetEmail = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('sourceHash')
                ->label('Von Spieler (alte E-Mail)')
                ->searchable()
                ->getSearchResultsUsing(function (string $search) {
                    return EternalRankingPlayer::query()
                        ->where('nickname', 'like', "%{$search}%")
                        ->get()
                        ->mapWithKeys(fn($player) => [
                            $player->email_hash => "{$player->nickname} – [{$player->email_hash}]"
                        ]);
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $player = EternalRankingPlayer::where('email_hash', $value)->first();
                    return $player ? "{$player->nickname} – [{$player->email_hash}]" : null;
                })
                ->reactive()
                ->afterStateUpdated(function (callable $set) {
                    // Reset target if source changes
                    $set('targetHash', null);
                })
                ->required(),

            Select::make('targetHash')
                ->label('Zu Spieler (Ziel-E-Mail)')
                ->searchable()
                ->getSearchResultsUsing(function (string $search, callable $get) {
                    return EternalRankingPlayer::query()
                        ->where('nickname', 'like', "%{$search}%")
                        ->get()
                        ->filter(fn($player) => $player->email_hash !== $get('sourceHash')) // Ausschluss
                        ->mapWithKeys(fn($player) => [
                            $player->email_hash => "{$player->nickname} – [{$player->email_hash}]"
                        ]);
                })
                ->getOptionLabelUsing(function ($value): ?string {
                    $player = EternalRankingPlayer::where('email_hash', $value)->first();
                    return $player ? "{$player->nickname} – [{$player->email_hash}]" : null;
                })
                ->required(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())
            ->statePath('data');
    }

    public function merge(): void
    {
        $sourceHash = md5(strtolower(trim($this->sourceEmail)));
        $targetHash = md5(strtolower(trim($this->targetEmail)));

        $source = EternalRankingPlayer::where('email_hash', $sourceHash)->first();
        $target = EternalRankingPlayer::where('email_hash', $targetHash)->first();

        if (!$source || !$target || $source->id === $target->id) {
            $this->notify('danger', 'Ungültige E-Mail-Adressen ausgewählt.');
            return;
        }

        EternalRankingResult::where('player_id', $source->id)
            ->update(['player_id' => $target->id]);

        $source->delete();

        $this->notify('success', 'Spieler erfolgreich zusammengeführt.');
    }
}
