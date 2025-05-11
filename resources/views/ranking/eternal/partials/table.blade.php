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
