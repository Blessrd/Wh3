<?php

namespace App\Services\ConciliacionDespachos;

class ConciliacionDespachosService
{
    public function __construct(
        private readonly LectorArchivos $lector = new LectorArchivos(),
        private readonly ColumnMapper $mapper = new ColumnMapper(),
        private readonly AlmacenTemporal $almacen = new AlmacenTemporal(),
        private readonly Conciliador $conciliador = new Conciliador(),
        private readonly ExportadorXlsx $exportador = new ExportadorXlsx(),
        private readonly Normalizador $normalizador = new Normalizador(),
    ) {
    }

    public function almacen(): AlmacenTemporal
    {
        return $this->almacen;
    }

    public function mapper(): ColumnMapper
    {
        return $this->mapper;
    }

    public function normalizador(): Normalizador
    {
        return $this->normalizador;
    }

    /**
     * Carga y parsea un archivo (despacho|sap), guarda temporal y devuelve preview + mapa.
     *
     * @return array{nombre: string, preview: array, headers: string[], mapa: array, sin_reconocer: string[], requeridos: array}
     */
    public function cargar($file, string $tipo): array
    {
        $ruta = $this->almacen->guardarArchivo($file, $tipo);
        $leido = $this->lector->leer($ruta, $file->getClientOriginalName());

        $detectado = $this->mapper->detectar($leido['rows'], $tipo);

        $this->almacen->guardarJson($tipo, [
            'rows' => $leido['rows'],
            'mapa' => $detectado['mapa'],
            'nombre' => $leido['nombre'],
        ]);

        $this->almacen->guardarJson($tipo . '_meta', [
            'nombre' => $leido['nombre'],
            'encabezados' => $detectado['encabezados'],
            'fila_encabezado' => $detectado['fila_encabezado'],
            'mapa' => $detectado['mapa'],
            'sin_reconocer' => $detectado['sin_reconocer'],
        ]);

        session()->forget(['cd_validado', 'cd_resultado']);

        $limit = (int) config('conciliacion-despachos.preview_filas', 10);
        $inicio = $detectado['fila_encabezado'] + 1;
        $preview = array_slice($leido['rows'], $inicio, $limit);

        $requeridos = $this->mapper->validarRequeridos($detectado['mapa'], $tipo);

        return [
            'nombre' => $leido['nombre'],
            'preview' => $preview,
            'headers' => $detectado['encabezados'],
            'mapa' => $detectado['mapa'],
            'sin_reconocer' => $detectado['sin_reconocer'],
            'fila_encabezado' => $detectado['fila_encabezado'],
            'requeridos' => $requeridos,
            'total_filas' => max(count($leido['rows']) - $inicio, 0),
        ];
    }

    /**
     * Aplica mapeo manual y revalida la estructura de ambos archivos.
     *
     * @return array{despacho: array, sap: array, ok: bool, errores: string[]}
     */
    public function validar(array $manualDespacho, array $manualSap): array
    {
        $errores = [];
        $resultado = ['despacho' => null, 'sap' => null];

        foreach (['despacho', 'sap'] as $tipo) {
            $datos = $this->almacen->leerJson($tipo);
        $meta = $this->almacen->leerJson($tipo . '_meta');

        if ($datos === null || $meta === null) {
            $errores[] = 'Falta el archivo de ' . $tipo . '.';

            continue;
        }

        $manual = $tipo === 'despacho' ? $manualDespacho : $manualSap;
        $encabezados = $meta['encabezados'] ?? [];

        if (is_array($manual) && count(array_filter($manual, fn ($v) => (string) $v !== '')) > 0) {
            $mapaManual = $this->mapper->aplicarManual($manual, $encabezados, $tipo);

            foreach ($meta['mapa'] as $campo => $col) {
                if (! isset($mapaManual[$campo])) {
                    $mapaManual[$campo] = $col;
                }
            }

            $mapa = $mapaManual;
        } else {
            $mapa = $meta['mapa'] ?? $datos['mapa'] ?? [];
        }

        $validacion = $this->mapper->validarRequeridos($mapa, $tipo);

        if (! $validacion['ok']) {
            $encabezadosDetectados = implode(', ', $meta['encabezados'] ?? []);
            $errores[] = 'Archivo de ' . $tipo . ': faltan columnas requeridas (' .
                implode(', ', $validacion['faltantes']) . '). ' .
                'Encabezados detectados: [' . $encabezadosDetectados . ']. ' .
                'Puede reasignar las columnas manualmente en los selectores de abajo.';
        }

        $datos['mapa'] = $mapa;
        $this->almacen->guardarJson($tipo, $datos);
        $this->almacen->guardarJson($tipo . '_meta', array_merge($meta, ['mapa' => $mapa]));

        $resultado[$tipo] = [
            'mapa' => $mapa,
            'requeridos' => $validacion,
            'nombre' => $meta['nombre'] ?? '',
            'sin_reconocer' => $meta['sin_reconocer'] ?? [],
        ];
        }

        $ok = count($errores) === 0;
        session(['cd_validado' => $ok]);

        return [
            'despacho' => $resultado['despacho'],
            'sap' => $resultado['sap'],
            'ok' => $ok,
            'errores' => $errores,
        ];
    }

    /**
     * Ejecuta la conciliación con los datos almacenados.
     */
    public function ejecutar(array $config = []): array
    {
        $despacho = $this->almacen->leerJson('despacho');
        $sap = $this->almacen->leerJson('sap');

        if ($despacho === null || $sap === null) {
            throw new \RuntimeException('Deben cargarse ambos archivos antes de ejecutar la conciliación.');
        }

        $this->almacen->guardarJson('config', ['tipos_incluidos' => $config['tipos_incluidos'] ?? []]);

        $resultado = $this->conciliador->ejecutar($despacho, $sap, $config);
        $this->almacen->guardarJson('resultado', $resultado);

        return $resultado;
    }

    public function resultadoGuardado(): ?array
    {
        return $this->almacen->leerJson('resultado');
    }

    public function configGuardada(): array
    {
        $config = $this->almacen->leerJson('config') ?? [];
        $tiposBase = config('conciliacion-despachos.tipos_movimiento', []);

        if (! isset($config['tipos_incluidos']) || count($config['tipos_incluidos'] ?? []) === 0) {
            $config['tipos_incluidos'] = array_map('strval', array_keys($tiposBase));
        }

        $config['tipos_base'] = $tiposBase;

        return $config;
    }

    /**
     * Exporta el resultado guardado a .xlsx.
     *
     * @return array{nombre: string, contenido: string}
     */
    public function exportar(): array
    {
        $resultado = $this->resultadoGuardado();

        if ($resultado === null) {
            throw new \RuntimeException('No hay resultados para exportar. Ejecute la conciliación primero.');
        }

        $contenido = $this->exportador->exportar($resultado);
        $marca = date('Ymd_His');

        return [
            'nombre' => 'conciliacion_despachos_' . $marca . '.xlsx',
            'contenido' => $contenido,
        ];
    }

    /**
     * Estado de carga de ambos archivos.
     *
     * @return array{despacho: bool, sap: bool, validado: bool, resultado: bool}
     */
    public function estado(): array
    {
        return [
            'despacho' => $this->almacen->existeJson('despacho'),
            'sap' => $this->almacen->existeJson('sap'),
            'validado' => (bool) session('cd_validado', false),
            'resultado' => $this->almacen->existeJson('resultado'),
        ];
    }

    public function metaCargada(string $tipo): ?array
    {
        return $this->almacen->leerJson($tipo . '_meta');
    }

    public function filasCargadas(string $tipo): int
    {
        $datos = $this->almacen->leerJson($tipo);

        return $datos === null ? 0 : count($datos['rows'] ?? []);
    }

    /**
     * Mapeo actual de campos para un tipo (para pintar selects).
     *
     * @return array<string, string>
     */
    public function mapaActual(string $tipo): array
    {
        $meta = $this->almacen->leerJson($tipo . '_meta');

        return $meta['mapa'] ?? [];
    }
}
