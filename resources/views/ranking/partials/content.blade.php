@if($hallOfFame)
    <div class="alert alert-info mb-4">
        <i class="bi bi-info-circle"></i> Klicke auf einen Spielernamen, um alle Rankings dieses Spielers für die ausgewählte Runde zu sehen.
    </div>

    <div class="row mb-4">
        <div class="col-12 text-end">
            <a href="{{ route('ranking.combined', ['roundId' => $hallOfFame->instance_round_id]) }}" class="btn btn-warning">
                <i class="bi bi-trophy"></i> Gesamtranking aller Spieler anzeigen
            </a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">{{ $hallOfFame->name }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-rankings">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Spieler</th>
                            <th>Koordinaten</th>
                            <th>Allianz</th>
                            <th>Kategorie</th>
                            <th class="text-end">Wert</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rankings as $index => $ranking)
                            <tr>
                                <td>{{ ($rankings->currentPage() - 1) * $rankings->perPage() + $index + 1 }}</td>
                                <td>
                                    <a href="{{ route('ranking.player', ['hofUserId' => $ranking->user->id, 'roundId' => $hallOfFame->instance_round_id]) }}">
                                        {{ $ranking->user->nickname }}
                                    </a>
                                </td>
                                <td>{{ $ranking->user->coordinates }}</td>
                                <td>{{ $ranking->user->alliance_tag }}</td>
                                <td>
                                    <span class="badge bg-{{ $ranking->rankingType->type }}">
                                        {{ $ranking->rankingType->display_name }}
                                    </span>
                                </td>
                                <td class="text-end">{{ number_format($ranking->value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Keine Daten gefunden</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($rankings instanceof \Illuminate\Pagination\LengthAwarePaginator && $rankings->hasPages())
                <div class="d-flex justify-content-center mt-4">
                    {{ $rankings->appends(request()->except('page'))->links() }}
                </div>
            @endif
        </div>
    </div>
@elseif($selectedRound)
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> Keine Hall of Fame für diese Runde gefunden.
    </div>
@elseif($selectedGameInstance)
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Bitte wähle eine Runde aus.
    </div>
@else
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Bitte wähle eine Spielinstanz aus.
    </div>
@endif
