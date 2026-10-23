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
    <div class="cd-empty">No hay materiales con diferencias. ¡Excelente!</div>
@else
<div class="cd-table-wrap">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Número de Parte / Material</th>
                <th>Descripción</th>
                <th>Lote Despacho</th>
                <th>Lote SAP</th>
                <th class="text-end">Cant. Despacho</th>
                <th class="text-end">Cant. SAP</th>
                <th class="text-end">Diferencia</th>
                <th class="text-end">Paletas</th>
                <th>Documento SAP</th>
                <th>Tipo Mov.</th>
                <th>Trailer</th>
                <th>Estado</th>
                <th>Causa</th>
                <th>Acción Recomendada</th>
                <th></th>
            </tr>
        </thead>
        <tbody class="cd-filter-body">
            @foreach($filas as $f)
                @php
                    $detail = [
                        'Material' => $f['material'],
                        'Lote despacho' => $f['lote'],
                        'Lote SAP' => $f['lote'],
                        'Cantidad despacho' => $f['cantidad_despacho'],
                        'Cantidad SAP' => $f['cantidad_sap'],
                        'Diferencia' => $f['diferencia'],
                        '% Cumplimiento' => $f['porcentaje'],
                        'Paletas' => $f['paletas'],
                        'Registros SAP' => $f['registros_sap'],
                        'Documentos' => $f['documentos_sap'],
                        'Tipos movimiento' => $f['tipos_movimiento'],
                        'Solo almacén' => $f['solo_almacen'] ? 'Sí' : 'No',
                        'Salida física' => $f['tiene_salida_fisica'] ? 'Sí' : 'No',
                        'Referencias SAP' => $f['referencias_sap'],
                        'Trailer' => $f['trailer'],
                        'Consignatario' => $f['consignatario'],
                        'UoM despacho' => $f['uom_despacho'],
                        'UoM SAP' => $f['uom_sap'],
                        'Observaciones' => $f['observaciones'],
                    ];
                @endphp
                <tr
                    data-material="{{ $f['material'] }}"
                    data-lote="{{ $f['lote'] }}"
                    data-estado="{{ $f['estado'] }}"
                    data-trailer="{{ $f['trailer'] }}"
                    data-consignatario="{{ $f['consignatario'] }}"
                    data-despacho="{{ $f['entrega_carga'] }}"
                    data-tipos="{{ implode(',', $f['tipos_movimiento'] ?? []) }}"
                >
                    <td><strong>{{ $f['material'] }}</strong></td>
                    <td>{{ $f['descripcion'] }}</td>
                    <td>{{ $f['lote'] !== '' ? $f['lote'] : '—' }}</td>
                    <td>{{ $f['lote'] !== '' ? $f['lote'] : ($f['estado'] === 'LOTE DIFERENTE' ? '(distinto)' : '—') }}</td>
                    <td class="text-end">{{ number_format($f['cantidad_despacho'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($f['cantidad_sap'], 2, ',', '.') }}</td>
                    <td class="text-end fw-bold text-danger">{{ number_format($f['diferencia'], 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($f['paletas'], 0, ',', '.') }}</td>
                    <td>{{ count($f['documentos_sap'] ?? []) ? implode(', ', $f['documentos_sap']) : '—' }}</td>
                    <td>{{ count($f['tipos_movimiento'] ?? []) ? implode(', ', $f['tipos_movimiento']) : '—' }}</td>
                    <td>{{ $f['trailer'] !== '' ? $f['trailer'] : '—' }}</td>
                    <td><span class="cd-badge {{ $badge($f['estado']) }}">{{ $f['estado'] }}</span></td>
                    <td style="white-space:normal;max-width:260px;font-size:.72rem;">{{ $f['causa'] }}</td>
                    <td style="white-space:normal;max-width:260px;font-size:.72rem;">{{ $f['accion'] }}</td>
                    <td>
                        <button type="button" class="cd-detail-btn"
                                data-detail="{{ e(json_encode($detail, JSON_UNESCAPED_UNICODE)) }}">
                            <i class="fa-solid fa-magnifying-glass"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
