@extends('layouts.ranking')

@section('title', 'Galaxy Hall of Fame - Ewige Bestenliste')

@section('subtitle', 'Spieler-Historie aller Runden')

@section('styles')
<style> table.compact td, table.compact th { padding:.25rem; font-size:.85rem; white-space: nowrap; } </style>
@endsection

@section('content')
    <form id="filter-form" method="POST" action="#" class="mb-3 row g-2">
        @csrf
        <div class="col-auto">
            <input type="number" name="round_id" class="form-control" placeholder="Runde ID"
                   value="{{ request('round_id') }}"
                   hx-post="{{ route('ranking.eternal.round') }}"
                   hx-include="#filter-form"
                   hx-trigger="change delay:300ms"
                   hx-target="#table-container"
                   hx-swap="outerHTML">
        </div>
        <div class="col-auto">
            <input type="text" name="search" class="form-control" placeholder="Spielername suchen"
                   value="{{ request('search') }}"
                   hx-post="{{ route('ranking.eternal.search') }}"
                   hx-include="#filter-form"
                   hx-trigger="keyup changed delay:500ms"
                   hx-target="#table-container"
                   hx-swap="outerHTML">
        </div>
    </form>

    <div id="table-container"
         hx-post="{{ route('ranking.eternal.table') }}"
         hx-include="#filter-form"
         hx-trigger="load">
        {{-- Initial: komplette Tabelle --}}
        @include('ranking.eternal.partials.table')
    </div>
@endsection
