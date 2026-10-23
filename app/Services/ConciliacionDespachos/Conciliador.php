<?php

namespace App\Services\ConciliacionDespachos;

class Conciliador
{
    public const ESTADOS = [
        'COMPLETO',
        'INCOMPLETO',
    ];

    private Normalizador $n;

    private ColumnMapper $mapper;

    public function __construct(
        ?Normalizador $normalizador = null,
        ?ColumnMapper $mapper = null,
    ) {
        $this->n = $normalizador ?? new Normalizador();
        $this->mapper = $mapper ?? new ColumnMapper();
    }

    /**
     * Ejecuta la conciliación completa.
     *
     * @param array{rows: array, mapa: array, nombre: string} $despacho (Magaya)
     * @param array{rows: array, mapa: array, nombre: string} $sap
     * @param array{tipos_incluidos?: string[]} $config
     */
    public function ejecutar(array $despacho, array $sap, array $config = []): array
    {
        $cfg = $this->config($config);

        $registrosMagaya = $this->parsearMagaya($despacho['rows'], $despacho['mapa']);
        $registrosSap = $this->parsearSap($sap['rows'], $sap['mapa'], $cfg);

        $idxMagaya = $this->indexarMagaya($registrosMagaya);
        $idxSap = $this->indexarSap($registrosSap);

        $materiales = array_unique(array_merge(array_keys($idxMagaya), array_keys($idxSap)));
        sort($materiales);

        $tabla = [];
        $noEncontrados = [];

        foreach ($materiales as $material) {
            $magaya = $idxMagaya[$material] ?? null;
            $sap = $idxSap[$material] ?? null;

            $cantMagaya = $magaya['cantidad'] ?? 0.0;
            $cantSap = $sap['cantidad'] ?? 0.0;
            $diferencia = $cantSap - $cantMagaya;

            if ($magaya !== null && $sap !== null) {
                $estado = abs($diferencia) <= 0.0001 ? 'COMPLETO' : 'INCOMPLETO';
            } elseif ($magaya !== null) {
                $estado = 'INCOMPLETO';
                $noEncontrados[] = [
                    'material' => $material,
                    'cantidad_magaya' => $cantMagaya,
                    'cantidad_sap' => 0.0,
                    'diferencia' => -$cantMagaya,
                    'origen' => 'Magaya',
                ];
            } else {
                $estado = 'INCOMPLETO';
                $noEncontrados[] = [
                    'material' => $material,
                    'cantidad_magaya' => 0.0,
                    'cantidad_sap' => $cantSap,
                    'diferencia' => $cantSap,
                    'origen' => 'SAP',
                ];
            }

            $tabla[] = [
                'material' => $material,
                'cantidad_magaya' => $cantMagaya,
                'cantidad_sap' => $cantSap,
                'diferencia' => $diferencia,
                'estado' => $estado,
            ];
        }

        // Ordenar: incompletos primero, luego completos, luego alfabético
        usort($tabla, function ($a, $b) {
            $pa = $a['estado'] === 'COMPLETO' ? 1 : 0;
            $pb = $b['estado'] === 'COMPLETO' ? 1 : 0;
            return $pa <=> $pb ?: strcmp($a['material'], $b['material']);
        });

        $dashboard = $this->calcularDashboard($tabla, $noEncontrados);

        return [
            'metadatos' => [
                'fecha_ejecucion' => date('Y-m-d H:i:s'),
                'archivo_magaya' => $despacho['nombre'] ?? '',
                'archivo_sap' => $sap['nombre'] ?? '',
            ],
            'dashboard' => $dashboard,
            'tabla' => $tabla,
            'no_encontrados' => $noEncontrados,
        ];
    }

    /* ─── Config ─── */

    private function config(array $config): array
    {
        $base = config('conciliacion-despachos', []);
        $tiposBase = $base['tipos_movimiento'] ?? [];

        $incluidos = $config['tipos_incluidos'] ?? array_keys($tiposBase);
        $incluidos = array_values(array_intersect(array_map('strval', $incluidos), array_map('strval', array_keys($tiposBase))));

        $tipos = [];
        foreach ($incluidos as $codigo) {
            $def = $tiposBase[$codigo] ?? $tiposBase[(int) $codigo] ?? null;
            if ($def === null) {
                continue;
            }
            $tipos[(string) $codigo] = [
                'texto' => $def['texto'] ?? '',
                'categoria' => $def['categoria'] ?? 'salida',
            ];
        }

        return [
            'tipos' => $tipos,
            'categorias_comparables' => $base['categorias_comparables'] ?? ['salida', 'traslado_salida'],
        ];
    }

    /* ─── Parsing Magaya ─── */

    private function parsearMagaya(array $rows, array $mapa): array
    {
        $registros = [];
        $filaEncabezado = $this->buscarFilaEncabezado($rows);

        foreach ($rows as $i => $fila) {
            if ($i <= $filaEncabezado || ! is_array($fila)) {
                continue;
            }

            $material = mb_strtoupper(trim((string) ($fila[$mapa['numero_parte'] ?? -1] ?? '')));
            if ($material === '') {
                continue;
            }

            $cantidadCol = $mapa['piezas_contenidas'] ?? $mapa['cantidad'] ?? -1;
            $cantidad = $this->n->numero($fila[$cantidadCol] ?? null) ?? 0.0;

            $registros[] = [
                'material' => $material,
                'cantidad' => $cantidad,
            ];
        }

        return $registros;
    }

    /* ─── Parsing SAP ─── */

    private function parsearSap(array $rows, array $mapa, array $cfg): array
    {
        $registros = [];
        $filaEncabezado = $this->buscarFilaEncabezado($rows);

        foreach ($rows as $i => $fila) {
            if ($i <= $filaEncabezado || ! is_array($fila)) {
                continue;
            }

            $material = mb_strtoupper(trim((string) ($fila[$mapa['material'] ?? -1] ?? '')));
            if ($material === '') {
                continue;
            }

            $tipoMov = (string) ($fila[$mapa['movement_type'] ?? -1] ?? '');
            $cantidad = $this->n->numero($fila[$mapa['quantity'] ?? -1] ?? null) ?? 0.0;

            $categoria = $cfg['tipos'][$tipoMov]['categoria'] ?? 'salida';
            $comparable = $this->n->cantidadComparable($cantidad, $categoria, $cfg['categorias_comparables']);

            $registros[] = [
                'material' => $material,
                'cantidad' => $comparable,
            ];
        }

        return $registros;
    }

    private function buscarFilaEncabezado(array $rows): int
    {
        $limite = min(count($rows), 10);
        $mejorI = 0;
        $mejorScore = -1.0;

        for ($i = 0; $i < $limite; $i++) {
            $fila = $rows[$i] ?? null;
            if (! is_array($fila) || count($fila) === 0) {
                continue;
            }
            if ($i >= count($rows) - 1) {
                continue;
            }

            $noVacias = 0;
            $textoCount = 0;
            foreach ($fila as $celda) {
                $texto = $this->n->texto((string) $celda);
                if ($texto === '') continue;
                $noVacias++;
                if (preg_match('/[a-zA-ZáéíóúÁÉÍÓÚñÑ]/u', $texto)) {
                    $textoCount++;
                }
            }
            if ($noVacias === 0) continue;
            $ratio = $textoCount / $noVacias;
            if ($ratio > $mejorScore) {
                $mejorScore = $ratio;
                $mejorI = $i;
            }
        }

        return $mejorI;
    }

    /* ─── Índices ─── */

    private function indexarMagaya(array $registros): array
    {
        $idx = [];
        foreach ($registros as $r) {
            $idx[$r['material']] = ($idx[$r['material']] ?? 0) + $r['cantidad'];
        }
        // Convertir a formato consistente
        $result = [];
        foreach ($idx as $material => $cantidad) {
            $result[$material] = ['material' => $material, 'cantidad' => $cantidad];
        }
        return $result;
    }

    private function indexarSap(array $registros): array
    {
        $idx = [];
        foreach ($registros as $r) {
            $idx[$r['material']] = ($idx[$r['material']] ?? 0) + $r['cantidad'];
        }
        $result = [];
        foreach ($idx as $material => $cantidad) {
            $result[$material] = ['material' => $material, 'cantidad' => $cantidad];
        }
        return $result;
    }

    /* ─── Dashboard ─── */

    private function calcularDashboard(array $tabla, array $noEncontrados): array
    {
        $completos = count(array_filter($tabla, fn ($f) => $f['estado'] === 'COMPLETO'));
        $incompletos = count(array_filter($tabla, fn ($f) => $f['estado'] === 'INCOMPLETO'));

        $piezasMagaya = array_sum(array_map(fn ($f) => $f['cantidad_magaya'], $tabla));
        $piezasSap = array_sum(array_map(fn ($f) => $f['cantidad_sap'], $tabla));
        $diferenciaNeta = $piezasSap - $piezasMagaya;

        return [
            'completos' => $completos,
            'incompletos' => $incompletos,
            'total_materiales' => count($tabla),
            'no_encontrados' => count($noEncontrados),
            'piezas_magaya' => $piezasMagaya,
            'piezas_sap' => $piezasSap,
            'diferencia_neta' => $diferenciaNeta,
        ];
    }

    private function Normalizador(): Normalizador
    {
        return $this->n;
    }
}
