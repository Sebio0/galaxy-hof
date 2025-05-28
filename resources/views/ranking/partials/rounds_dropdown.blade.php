<option value="">-- Alle Runden --</option>
@foreach($rounds as $round)
    <option value="{{ $round->id }}" {{ $selectedRound == $round->id ? 'selected' : '' }}>
        {{ $round->name }}
    </option>
@endforeach
