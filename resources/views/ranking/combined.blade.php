@extends('layouts.ranking')

@section('title', 'Galaxy Hall of Fame - Top Spieler')

@section('subtitle', 'Top Spieler für Runde ' . $round->name)

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index') }}">Rankings</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index', ['game_instance_id' => $gameInstance->id]) }}">{{ $gameInstance->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index', ['game_instance_id' => $gameInstance->id, 'round_id' => $round->id]) }}">{{ $round->name }}</a></li>
                    <li class="breadcrumb-item active">Top Spieler</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Diese Übersicht zeigt die jeweils erstplatzierten Spieler in allen Ranking-Kategorien für diese Runde.
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Top Spieler in {{ $hallOfFame->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-rankings">
                            <thead>
                                <tr>
                                    <th>Ranking-Kategorie</th>
                                    <th>Spieler</th>
                                    <th>Koordinaten</th>
                                    <th>Allianz</th>
                                    <th class="text-end">Wert</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankingTypes as $rankingType)
                                    @if(isset($topPlayers[$rankingType->id]))
                                        @php
                                            $topPlayer = $topPlayers[$rankingType->id];
                                        @endphp
                                        <tr>
                                            <td>{{ $rankingType->display_name }}</td>
                                            <td>
                                                <a href="{{ route('ranking.player', ['hofUserId' => $topPlayer->user->id, 'roundId' => $round->id]) }}">
                                                    {{ $topPlayer->user->nickname }}
                                                </a>
                                            </td>
                                            <td>{{ $topPlayer->user->coordinates }}</td>
                                            <td>{{ $topPlayer->user->alliance_tag }}</td>
                                            <td class="text-end">{{ number_format($topPlayer->value, 0, ',', '.') }}</td>
                                        </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Keine Daten gefunden</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <a href="{{ route('ranking.index', ['game_instance_id' => $gameInstance->id, 'round_id' => $round->id]) }}" class="btn btn-primary">
                <i class="bi bi-arrow-left"></i> Zurück zur Rangliste
            </a>
        </div>
    </div>
@endsection
