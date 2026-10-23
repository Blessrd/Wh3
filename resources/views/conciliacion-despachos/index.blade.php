@extends('layouts.app')
@section('page-title', 'Conciliación Magaya vs SAP')
@section('page-description', 'Comparación automática de cantidades de materiales: Magaya vs SAP.')

@section('content')

<link rel="stylesheet" href="{{ asset('modules/conciliacion-despachos.css') }}">

@php
    $paso = 1;
    if ($estado['despacho'] && $estado['sap']) { $paso = $resultado ? 4 : 3; }
    elseif ($estado['despacho'] || $estado['sap']) { $paso = 2; }
@endphp

<div class="cd-steps">
    <div class="cd-step {{ $paso === 1 ? 'active' : 'done' }}">
        <span class="step-num">1</span> Cargar archivos
    </div>
    <div class="cd-step {{ $paso === 2 ? 'active' : ($paso > 2 ? 'done' : '') }}">
        <span class="step-num">2</span> Validar columnas
    </div>
    <div class="cd-step {{ $paso === 3 ? 'active' : ($paso > 3 ? 'done' : '') }}">
        <span class="step-num">3</span> Ejecutar conciliación
    </div>
    <div class="cd-step {{ $paso === 4 ? 'active' : '' }}">
        <span class="step-num">4</span> Resultados y exportación
    </div>
</div>

@if(session('cd_error'))
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> {{ session('cd_error') }}
    </div>
@endif

@if($errores)
    <div class="alert alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <strong>Validación fallida:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errores as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($error && !session('cd_error'))
    <div class="alert alert-warning">
        <i class="fa-solid fa-circle-info"></i> {{ $error }}
    </div>
@endif

{{-- ── Paso 1: Carga ── --}}
<div class="card corporate-card mb-4">
    <div class="card-header" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;">
        <i class="fa-solid fa-file-arrow-up"></i> 1. Carga de archivos
    </div>
    <div class="card-body">

        <div class="cd-upload-grid">

            {{-- Archivo despacho --}}
            <div class="cd-upload-card {{ $estado['despacho'] ? 'loaded' : '' }}">
                <h5><i class="fa-solid fa-truck"></i> Archivo 1: Magaya</h5>
                <form method="POST" action="/conciliacion-despachos/cargar" enctype="multipart/form-data" class="cd-needs-progress" data-progress-msg="Leyendo archivo de despacho...">
                    @csrf
                    <input type="hidden" name="tipo" value="despacho">
                    <div class="mb-2">
                        <input class="form-control cd-file-input" type="file" name="archivo" accept=".xlsx,.xls,.csv,.txt" style="padding:8px 12px;height:auto;">
                    </div>
                    <div class="cd-file-meta mb-2 cd-file-name">
                        @if($metaDespacho)
                            <i class="fa-solid fa-file-excel text-success"></i>
                            <strong>{{ $metaDespacho['nombre'] ?? '' }}</strong>
                        @else
                            Ningún archivo seleccionado
                        @endif
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa-solid fa-upload"></i> Cargar archivo
                    </button>
                    @if($estado['despacho'])
                        <span class="badge bg-success ms-2"><i class="fa-solid fa-check"></i> Cargado</span>
                    @endif
                </form>

                @if($previewDespacho ?? null)
                    @include('conciliacion-despachos.partials.preview', [
                        'tipo' => 'despacho',
                        'info' => $previewDespacho,
                        'campos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_DESPACHO,
                        'requeridos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_REQUERIDOS['despacho'],
                        'mapa' => $previewDespacho['mapa'] ?? [],
                        'encabezados' => $previewDespacho['headers'] ?? [],
                    ])
                @endif
            </div>

            {{-- Archivo SAP --}}
            <div class="cd-upload-card {{ $estado['sap'] ? 'loaded' : '' }}">
                <h5><i class="fa-solid fa-database"></i> Archivo 2: Conciliación SAP</h5>
                <form method="POST" action="/conciliacion-despachos/cargar" enctype="multipart/form-data" class="cd-needs-progress" data-progress-msg="Leyendo archivo SAP...">
                    @csrf
                    <input type="hidden" name="tipo" value="sap">
                    <div class="mb-2">
                        <input class="form-control cd-file-input" type="file" name="archivo" accept=".xlsx,.xls,.csv,.txt" style="padding:8px 12px;height:auto;">
                    </div>
                    <div class="cd-file-meta mb-2 cd-file-name">
                        @if($metaSap)
                            <i class="fa-solid fa-file-excel text-success"></i>
                            <strong>{{ $metaSap['nombre'] ?? '' }}</strong>
                        @else
                            Ningún archivo seleccionado
                        @endif
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fa-solid fa-upload"></i> Cargar archivo
                    </button>
                    @if($estado['sap'])
                        <span class="badge bg-success ms-2"><i class="fa-solid fa-check"></i> Cargado</span>
                    @endif
                </form>

                @if($previewSap ?? null)
                    @include('conciliacion-despachos.partials.preview', [
                        'tipo' => 'sap',
                        'info' => $previewSap,
                        'campos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_SAP,
                        'requeridos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_REQUERIDOS['sap'],
                        'mapa' => $previewSap['mapa'] ?? [],
                        'encabezados' => $previewSap['headers'] ?? [],
                    ])
                @endif
            </div>

        </div>

        @if($metaDespacho && $metaSap)
            <div class="mt-3 d-flex gap-2 flex-wrap">
                <a href="/conciliacion-despachos/limpiar" class="btn btn-outline-danger btn-sm"
                   onclick="return confirm('¿Limpiar ambos archivos y resultados?')">
                    <i class="fa-solid fa-trash"></i> Limpiar datos
                </a>
            </div>
        @endif
    </div>
</div>

{{-- ── Paso 2/3: Mapeo + config + ejecutar ── --}}
@if($estado['despacho'] && $estado['sap'] && !($resultado ?? null))
    <div class="card corporate-card mb-4">
        <div class="card-header" style="background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;">
            <i class="fa-solid fa-sliders"></i> 2. Validación de columnas y parámetros
        </div>
        <div class="card-body">

            <form method="POST" action="/conciliacion-despachos/validar" id="form-conciliar" class="cd-needs-progress" data-progress-msg="Ejecutando conciliación...">
                @csrf

                <div class="row">
                    <div class="col-lg-6 mb-3">
                        <h6 class="fw-bold text-primary"><i class="fa-solid fa-truck"></i> Columnas — Despacho</h6>
                        @include('conciliacion-despachos.partials.mapeo', [
                            'prefijo' => 'mapa_despacho',
                            'campos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_DESPACHO,
                            'requeridos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_REQUERIDOS['despacho'],
                            'encabezados' => $metaDespacho['encabezados'] ?? [],
                            'mapaActual' => $metaDespacho['mapa'] ?? [],
                        ])
                    </div>
                    <div class="col-lg-6 mb-3">
                        <h6 class="fw-bold text-primary"><i class="fa-solid fa-database"></i> Columnas — SAP</h6>
                        @include('conciliacion-despachos.partials.mapeo', [
                            'prefijo' => 'mapa_sap',
                            'campos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_SAP,
                            'requeridos' => \App\Services\ConciliacionDespachos\ColumnMapper::CAMPOS_REQUERIDOS['sap'],
                            'encabezados' => $metaSap['encabezados'] ?? [],
                            'mapaActual' => $metaSap['mapa'] ?? [],
                        ])
                    </div>
                </div>

                <h6 class="fw-bold text-primary mt-2"><i class="fa-solid fa-arrows-rotate"></i> Tipos de movimiento SAP incluidos</h6>
                <p class="small text-muted mb-2">
                    Solo los tipos marcados se tendrán en cuenta como movimientos válidos.
                    Las categorías <strong>salida</strong> y <strong>traslado_salida</strong> suman cantidad comparable.
                </p>

                <div class="cd-mov-types mb-3">
                    @foreach($config['tipos_base'] ?? [] as $codigo => $def)
                        @php $checked = in_array((string)$codigo, $config['tipos_incluidos'] ?? [], true); @endphp
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="tipos_movimiento[]"
                                   value="{{ $codigo }}" id="tipo-{{ $codigo }}" {{ $checked ? 'checked' : '' }}>
                            <label class="form-check-label" for="tipo-{{ $codigo }}">
                                <strong>{{ $codigo }}</strong> — {{ $def['texto'] }}
                                <span class="badge bg-secondary text-uppercase" style="font-size:.62rem">{{ $def['categoria'] }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>

                <div class="cd-action-row mt-2">
                    <button class="btn btn-primary cd-submit" type="submit" id="btnEjecutar">
                        <i class="fa-solid fa-gears"></i> Ejecutar conciliación
                    </button>
                    <span class="text-muted small">No se ejecutará hasta que ambos archivos estén cargados y validados.</span>
                </div>
            </form>

            <div class="cd-progress" id="cd-progress">
                <i class="fa-solid fa-spinner fa-spin"></i>
                <span>Procesando...</span>
            </div>

        </div>
    </div>
@endif

{{-- ── Paso 4: Resultados ── --}}
@if($resultado ?? null)
    @include('conciliacion-despachos.partials.resultados', ['resultado' => $resultado])
@endif

@endsection

@section('scripts')
<script src="{{ asset('modules/conciliacion-despachos.js') }}"></script>
@endsection
