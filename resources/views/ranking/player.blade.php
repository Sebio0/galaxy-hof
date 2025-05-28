@extends('layouts.ranking')

@section('title', 'Galaxy Hall of Fame - Spielerprofil')

@section('subtitle', 'Alle Rankings für ' . $player->nickname . ' in Runde ' . $round->name)

@section('content')
    <div class="row mb-4">
        <div class="col-12">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index') }}">Rankings</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index', ['game_instance_id' => $gameInstance->id]) }}">{{ $gameInstance->name }}</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ranking.index', ['game_instance_id' => $gameInstance->id, 'round_id' => $round->id]) }}">{{ $round->name }}</a></li>
                    <li class="breadcrumb-item active">{{ $player->nickname }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Spielerinformationen</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Spielername:</th>
                            <td>{{ $player->nickname }}</td>
                        </tr>
                        <tr>
                            <th>Koordinaten:</th>
                            <td>{{ $player->coordinates }}</td>
                        </tr>
                        @if($player->alliance_tag)
                        <tr>
                            <th>Allianz:</th>
                            <td>{{ $player->alliance_tag }}</td>
                        </tr>
                        @endif
                        <tr>
                            <th>Runde:</th>
                            <td>{{ $round->name }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Gesamtranking</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <h2>Position: {{ $combinedPosition }} von {{ $totalPlayers }}</h2>
                        <p class="lead">Durchschnittliche Platzierung: {{ number_format($combinedScore, 2, ',', '.') }}</p>
                        @php
                            $actualRankings = 0;
                            foreach ($rankingPositions as $rankingTypeId => $position) {
                                if (isset($maxPlayersByRankingType[$rankingTypeId]) && $position <= $maxPlayersByRankingType[$rankingTypeId]) {
                                    $actualRankings++;
                                }
                            }
                        @endphp
                        <p>Vorhandene Rankings: <strong>{{ $actualRankings }}</strong> von <strong>{{ $totalRankingTypes }}</strong></p>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i> Dieses Gesamtranking berechnet sich aus dem Durchschnitt aller Platzierungen des Spielers in <strong>allen</strong> Kategorien. Wenn ein Spieler in einer Kategorie nicht vorhanden ist, wird für diese Kategorie die Position (maximale Anzahl Spieler + 1) verwendet.
                    </div>
                    <div class="text-center">
                        <a href="{{ route('ranking.combined', ['roundId' => $round->id]) }}" class="btn btn-warning">
                            <i class="bi bi-trophy"></i> Alle Spieler im Gesamtranking anzeigen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Rankings in {{ $hallOfFame->name }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-rankings">
                            <thead>
                                <tr>
                                    <th>Kategorie</th>
                                    <th class="text-end">Wert</th>
                                    <th class="text-end">Position</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($rankingTypes as $rankingType)
                                    @php
                                        $ranking = $rankings->where('ranking_type_id', $rankingType->id)->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge bg-{{ $rankingType->type }}">
                                                {{ $rankingType->display_name }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            @if($ranking)
                                                {{ number_format($ranking->value, 0, ',', '.') }}
                                            @else
                                                <span class="text-muted">0</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if(isset($rankingPositions[$rankingType->id]))
                                                @if($ranking)
                                                    {{ $rankingPositions[$rankingType->id] }}
                                                @else
                                                    <span class="text-danger">Nicht vorhanden</span>
                                                @endif
                                            @else
                                                -
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center">Keine Rankings gefunden</td>
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
