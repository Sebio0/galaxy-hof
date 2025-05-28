@extends('layouts.ranking')

@section('title', 'Galaxy Hall of Fame - Runden-Rankings')

@section('subtitle', 'Spieler-Rankings nach Runden und Kategorien')

@section('content')

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Filter</h5>
                </div>
                <div class="card-body">
                    <form id="filter-form" method="POST" action="{{ route('ranking.filter') }}" class="row g-3">
                        @csrf
                        <div class="col-md-3">
                            <label for="game_instance_id" class="form-label">Spielinstanz</label>
                            <select class="form-select" id="game_instance_id" name="game_instance_id"
                                    hx-post="{{ route('ranking.filter') }}"
                                    hx-include="#filter-form"
                                    hx-target="#round_id"
                                    hx-swap="innerHTML"
                                    hx-trigger="change">
                                <option value="">-- Alle Instanzen --</option>
                                @foreach($gameInstances as $instance)
                                    <option value="{{ $instance->id }}" {{ $selectedGameInstance == $instance->id ? 'selected' : '' }}>
                                        {{ $instance->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="round_id" class="form-label">Runde</label>
                            <select class="form-select" id="round_id" name="round_id"
                                    hx-post="{{ route('ranking.filter') }}"
                                    hx-include="#filter-form"
                                    hx-target="#content-container"
                                    hx-swap="innerHTML"
                                    hx-trigger="change"
                                    hx-indicator="#round-loading">
                                <option value="">-- Alle Runden --</option>
                                @foreach($rounds as $round)
                                    <option value="{{ $round->id }}" {{ $selectedRound == $round->id ? 'selected' : '' }}>
                                        {{ $round->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div id="round-loading" class="htmx-indicator">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                            <!-- Second HTMX request to update ranking types dropdown -->
                            <div id="ranking-types-updater-2"
                                 hx-post="{{ route('ranking.filter') }}"
                                 hx-include="#filter-form"
                                 hx-target="#ranking_type_id"
                                 hx-swap="innerHTML"
                                 hx-trigger="change from:#round_id"
                                 hx-indicator="#ranking-types-loading-2">
                            </div>
                            <div id="ranking-types-loading-2" class="htmx-indicator">
                                <div class="spinner-border spinner-border-sm text-primary" role="status">
                                    <span class="visually-hidden">Loading ranking types...</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label for="ranking_type_id" class="form-label">Ranking-Typ</label>
                            <select class="form-select" id="ranking_type_id" name="ranking_type_id"
                                    hx-post="{{ route('ranking.filter') }}"
                                    hx-include="#filter-form"
                                    hx-target="#content-container"
                                    hx-swap="innerHTML"
                                    hx-trigger="change">
                                <option value="">-- Alle Typen --</option>
                                @foreach($rankingTypes as $type)
                                    <option value="{{ $type->id }}" {{ $selectedRankingType == $type->id ? 'selected' : '' }}>
                                        {{ $type->display_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label for="search" class="form-label">Suche</label>
                            <input type="text" class="form-control" id="search" name="search"
                                   placeholder="Spielername, Koordinaten, Allianz..."
                                   value="{{ $search }}"
                                   hx-post="{{ route('ranking.filter') }}"
                                   hx-include="#filter-form"
                                   hx-trigger="keyup changed delay:500ms"
                                   hx-target="#content-container"
                                   hx-swap="innerHTML">
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div id="content-container">
        @include('ranking.partials.content')
    </div>
@endsection
