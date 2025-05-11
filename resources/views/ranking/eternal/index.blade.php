<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        table.compact td, table.compact th { padding: .25rem; font-size: .85rem; white-space: nowrap; }
    </style>
    <title>Spieler-Historie</title>
</head>
<body>
<div class="container-fluid mt-4">
    <h1 class="mb-3">Spieler-Historie aller Runden</h1>
    <form method="GET" action="{{ route('ranking.eternal.index') }}" class="mb-3">
        <div class="row g-2">
            <div class="col-auto">
                <label for="round_id" class="visually-hidden">Runde</label>
                <input type="number" name="round_id" id="round_id" class="form-control" placeholder="Runde ID" value="{{ request('round_id') }}">
            </div>
            <div class="col-auto">
                <button class="btn btn-primary">Filtern</button>
            </div>
        </div>
    </form>
    <table class="table table-bordered table-sm compact">
        <thead class="table-dark">
        <tr>
            <th>Spieler</th>
            <th>Gesamt%</th>
            @foreach($rounds as $r)
                <th>R{{$r}}</th>
            @endforeach
        </tr>
        </thead>
        <tbody>
        @foreach($players as $player)
            <tr>
                <td>{{ $player->nickname }}</td>
                <td>{{ number_format($player->avg_pct, 2, ',', '.') }}%</td>
                @foreach($rounds as $r)
                    <td>
                        @if(isset($results[$player->id][$r]))
                            @php($row = $results[$player->id][$r])
                            {{ number_format($row->score,0,',','.') }}<br>
                            <small>{{ number_format($row->pct,1,',','.') }}%</small>
                        @else
                            –
                        @endif
                    </td>
                @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="d-flex justify-content-center">
        {{ $players->links() }}
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
