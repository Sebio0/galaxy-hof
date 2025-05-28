<option value="">-- Alle Typen --</option>
@foreach($rankingTypes as $type)
    <option value="{{ $type->id }}" {{ $selectedRankingType == $type->id ? 'selected' : '' }}>
        {{ $type->display_name }}
    </option>
@endforeach
