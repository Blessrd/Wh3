@php
    $mapaInvertido = [];
    foreach ($mapaActual as $campo => $col) {
        $mapaInvertido[$campo] = $encabezados[$col] ?? (string) $col;
    }
@endphp

<div class="cd-mapping-grid">
    @foreach($campos as $campo => $etiqueta)
        @php $esReq = in_array($campo, $requeridos, true); @endphp
        <div class="cd-mapping-field {{ $esReq ? 'required' : '' }}">
            <label>{{ $etiqueta }}</label>
            <select class="form-select" name="{{ $prefijo }}[{{ $campo }}]">
                <option value="">— Detectado automáticamente —</option>
                @foreach($encabezados as $i => $h)
                    @if($h === '') @continue @endif
                    <option value="{{ $h }}" {{ ($mapaInvertido[$campo] ?? '') === $h ? 'selected' : '' }}>
                        {{ $h }}
                    </option>
                @endforeach
            </select>
        </div>
    @endforeach
</div>
