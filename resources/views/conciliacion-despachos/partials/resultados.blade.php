@php
    $dash = $resultado['dashboard'] ?? [];
    $tabla = $resultado['tabla'] ?? [];
    $noEncontrados = $resultado['no_encontrados'] ?? [];
    $meta = $resultado['metadatos'] ?? [];
@endphp

<div class="card corporate-card mb-4" id="resultados">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2"
         style="background:linear-gradient(135deg,#14532d,#22c55e);color:#fff;">
        <span><i class="fa-solid fa-chart-line"></i> 4. Resultados de la conciliación</span>
        <a href="/conciliacion-despachos/exportar" class="btn btn-sm btn-light">
            <i class="fa-solid fa-file-excel"></i> Exportar Excel
        </a>
    </div>
    <div class="card-body">

        <div class="small text-muted mb-3">
            <i class="fa-solid fa-clock"></i> Ejecutado: <strong>{{ $meta['fecha_ejecucion'] ?? '' }}</strong>
            &nbsp;·&nbsp; Magaya: <strong>{{ $meta['archivo_magaya'] ?? '' }}</strong>
            &nbsp;·&nbsp; SAP: <strong>{{ $meta['archivo_sap'] ?? '' }}</strong>
        </div>

        {{-- Resumen mínimo --}}
        <div class="cd-kpi-grid mb-4">
            <div class="cd-kpi kpi-ok">
                <div class="kpi-label">Completos</div>
                <div class="kpi-value">{{ $dash['completos'] ?? 0 }}</div>
            </div>
            <div class="cd-kpi kpi-danger">
                <div class="kpi-label">Incompletos</div>
                <div class="kpi-value">{{ $dash['incompletos'] ?? 0 }}</div>
            </div>
            <div class="cd-kpi kpi-danger">
                <div class="kpi-label">No encontrados</div>
                <div class="kpi-value">{{ count($noEncontrados) }}</div>
            </div>
            <div class="cd-kpi kpi-info">
                <div class="kpi-label">Piezas Magaya</div>
                <div class="kpi-value">{{ number_format($dash['piezas_magaya'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="cd-kpi kpi-info">
                <div class="kpi-label">Piezas SAP</div>
                <div class="kpi-value">{{ number_format($dash['piezas_sap'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="cd-kpi {{ ($dash['diferencia_neta'] ?? 0) == 0 ? 'kpi-ok' : 'kpi-danger' }}">
                <div class="kpi-label">Diferencia Neta</div>
                <div class="kpi-value">{{ number_format($dash['diferencia_neta'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- Veredicto --}}
        @php
            $todoOk = ($dash['incompletos'] ?? 1) === 0 && count($noEncontrados) === 0;
        @endphp
        @if($todoOk)
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <strong>Magaya completamente conciliado.</strong>
                Todos los materiales están validados y las cantidades coinciden.
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <strong>Quedan diferencias por revisar.</strong>
            </div>
        @endif

        {{-- Tabla principal --}}
        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-table"></i> Conciliación por Material</h6>
        @include('conciliacion-despachos.partials.tabla-a', ['filas' => $tabla])

        {{-- No encontrados --}}
        @if(count($noEncontrados) > 0)
            <h6 class="fw-bold text-danger mt-4 mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Materiales no encontrados en el otro archivo</h6>
            <div class="cd-table-wrap">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Material</th>
                            <th class="text-end">Piezas Magaya</th>
                            <th class="text-end">Piezas SAP</th>
                            <th class="text-end">Diferencia</th>
                            <th>Origen</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($noEncontrados as $nd)
                            <tr>
                                <td><strong>{{ $nd['material'] }}</strong></td>
                                <td class="text-end">{{ number_format($nd['cantidad_magaya'], 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($nd['cantidad_sap'], 2, ',', '.') }}</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($nd['diferencia'], 2, ',', '.') }}</td>
                                <td>{{ $nd['origen'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- Resumen mínimo --}}
        <div class="cd-kpi-grid mb-4">
            <div class="cd-kpi kpi-ok">
                <div class="kpi-label">Completos</div>
                <div class="kpi-value">{{ $dash['completos'] ?? 0 }}</div>
            </div>
            <div class="cd-kpi kpi-danger">
                <div class="kpi-label">Incompletos</div>
                <div class="kpi-value">{{ $dash['incompletos'] ?? 0 }}</div>
            </div>
            <div class="cd-kpi kpi-warn">
                <div class="kpi-label">Sin ubicación</div>
                <div class="kpi-value">{{ $dash['sin_ubicacion'] ?? 0 }}</div>
            </div>
            <div class="cd-kpi kpi-danger">
                <div class="kpi-label">No encontrados</div>
                <div class="kpi-value">{{ count($noEncontrados) }}</div>
            </div>
            <div class="cd-kpi kpi-info">
                <div class="kpi-label">Paletas Magaya</div>
                <div class="kpi-value">{{ number_format($dash['total_paletas'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="cd-kpi kpi-info">
                <div class="kpi-label">Piezas Magaya</div>
                <div class="kpi-value">{{ number_format($dash['piezas_despacho'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="cd-kpi kpi-info">
                <div class="kpi-label">Piezas SAP</div>
                <div class="kpi-value">{{ number_format($dash['piezas_sap'] ?? 0, 0, ',', '.') }}</div>
            </div>
            <div class="cd-kpi {{ ($dash['diferencia_neta'] ?? 0) == 0 ? 'kpi-ok' : 'kpi-danger' }}">
                <div class="kpi-label">Diferencia Neta</div>
                <div class="kpi-value">{{ number_format($dash['diferencia_neta'] ?? 0, 0, ',', '.') }}</div>
            </div>
        </div>

        {{-- Veredicto --}}
        @php
            $todoOk = ($dash['incompletos'] ?? 1) === 0 && ($dash['sin_ubicacion'] ?? 1) === 0 && count($noDespachados) === 0;
        @endphp
        @if($todoOk)
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                <strong>Magaya completamente conciliado.</strong>
                Todos los materiales están validados y las cantidades coinciden.
            </div>
        @else
            <div class="alert alert-warning">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <strong>Quedan diferencias por revisar.</strong>
            </div>
        @endif

        {{-- Tabla principal --}}
        <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-table"></i> Conciliación por Material</h6>
        @include('conciliacion-despachos.partials.tabla-a', ['filas' => $tabla])

        {{-- No despachados / incompletos --}}
        @if(count($noEncontrados) > 0)
            <h6 class="fw-bold text-danger mt-4 mb-2"><i class="fa-solid fa-triangle-exclamation"></i> Materiales no despachados / incompletos en SAP</h6>
            <div class="cd-table-wrap">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Número de Parte</th>
                            <th>Lote</th>
                            <th>Ubicación</th>
                            <th>Nave</th>
                            <th class="text-end">Requerido (Magaya)</th>
                            <th class="text-end">Despachado (SAP)</th>
                            <th class="text-end">Diferencia</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($noEncontrados as $nd)
                            <tr>
                                <td><strong>{{ $nd['material'] }}</strong></td>
                                <td>{{ $nd['lote'] }}</td>
                                <td style="white-space:normal;max-width:180px;font-size:.72rem;">{{ $nd['ubicacion'] ?: '—' }}</td>
                                <td>{{ $nd['nave'] }}</td>
                                <td class="text-end">{{ number_format($nd['cantidad_despacho'], 2, ',', '.') }}</td>
                                <td class="text-end">0,00</td>
                                <td class="text-end fw-bold text-danger">{{ number_format($nd['cantidad_despacho'], 2, ',', '.') }}</td>
                                <td><span class="cd-badge estado-faltante">NO DESPACHADO</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="cd-action-row mt-3">
            <a href="/conciliacion-despachos/exportar" class="btn btn-success">
                <i class="fa-solid fa-file-arrow-down"></i> Descargar Excel
            </a>
            <a href="/conciliacion-despachos/limpiar" class="btn btn-outline-danger"
               onclick="return confirm('¿Iniciar una nueva conciliación? Se limpiarán los datos actuales.')">
                <i class="fa-solid fa-rotate"></i> Nueva conciliación
            </a>
        </div>

    </div>
</div>