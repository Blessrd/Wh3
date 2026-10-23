@php
    $esDespacho = $tipo === 'despacho';
    $totalFilas = $info['total_filas'] ?? 0;
    $headers = $info['headers'] ?? [];
    $preview = $info['preview'] ?? [];
    $mapa = $info['mapa'] ?? [];
    $sinReconocer = $info['sin_reconocer'] ?? [];
    $requeridosInfo = $info['requeridos'] ?? ['ok' => false, 'faltantes' => []];
@endphp

<div class="mt-3" style="border-top:1px solid #e2e8f0;padding-top:.85rem;">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <strong class="small">
            <i class="fa-solid fa-table-list text-primary"></i>
            Vista previa — {{ $info['nombre'] ?? '' }}
            ({{ $totalFilas }} filas de datos)
        </strong>
        @if($requeridosInfo['ok'] ?? false)
            <span class="badge bg-success"><i class="fa-solid fa-check"></i> Estructura válida</span>
        @else
            <span class="badge bg-danger">
                <i class="fa-solid fa-xmark"></i>
                Faltan: {{ implode(', ', $requeridosInfo['faltantes'] ?? []) }}
            </span>
        @endif
    </div>

    @if(count($sinReconocer) > 0)
        <div class="small mb-2">
            <span class="text-muted">Encabezados no reconocidos:</span>
            <div class="cd-unknown-tags">
                @foreach($sinReconocer as $h)
                    <span>{{ $h }}</span>
                @endforeach
            </div>
        </div>
    @endif

    @if(count($preview) > 0)
        <div class="cd-preview-wrap">
            <table class="table table-sm table-bordered mb-0">
                <thead>
                    <tr>
                        @foreach($headers as $h)
                            <th>{{ $h !== '' ? $h : '—' }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($preview as $fila)
                        <tr>
                            @for($c = 0; $c < count($headers); $c++)
                                <td>{{ $fila[$c] ?? '' }}</td>
                            @endfor
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="cd-empty">No se encontraron filas de datos.</div>
    @endif
</div>
