@php
    $badge = fn (string $estado) => match ($estado) {
        'CONCILIADO' => 'estado-conciliado',
        'FALTANTE EN SAP' => 'estado-faltante',
        'CANTIDAD INCOMPLETA' => 'estado-incompleto',
        'EXCEDENTE EN SAP' => 'estado-excedente',
        'NO APARECE EN DESPACHO' => 'estado-solo-sap',
        'LOTE DIFERENTE' => 'estado-lote',
        'MOVIMIENTO ANULADO O CONTRAPARTIDA' => 'estado-anulado',
        'UNIDAD INCOMPATIBLE' => 'estado-uom',
        'REGISTRO INCOMPLETO' => 'estado-registro',
        'POSIBLE DUPLICADO' => 'estado-duplicado',
        'REVISIÓN MANUAL' => 'estado-revision',
        default => 'estado-revision',
    };
@endphp

@if(count($filas) === 0)
    <div class="cd-empty">Sin registros.</div>
@else
<div class="cd-table-wrap">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Número de Parte</th>
                <th class="text-end">Piezas Magaya</th>
                <th class="text-end">Piezas SAP</th>
                <th class="text-end">Diferencia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody class="cd-filter-body">
            @foreach($filas as $f)
                <tr
                    data-material="{{ $f['material'] }}"
                    data-estado="{{ $f['estado'] }}"
                >
                    <td><strong>{{ $f['material'] }}</strong></td>
                    <td class="text-end">{{ number_format($f['cantidad_magaya'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($f['cantidad_sap'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold {{ $f['diferencia'] == 0 ? 'text-success' : 'text-danger' }}">
                        {{ number_format($f['diferencia'], 2, ',', '.') }}
                    </td>
                    <td><span class="cd-badge {{ $badge($f['estado']) }}">{{ $f['estado'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
