<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script src="https://unpkg.com/htmx.org@1.9.2"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style> table.compact td, table.compact th { padding:.25rem; font-size:.85rem; white-space: nowrap; } </style>
    <title>Spieler-Historie</title>
</head>
<body>
<div class="container-fluid mt-4">
    <h1 class="mb-3">Spieler-Historie aller Runden</h1>
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
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
