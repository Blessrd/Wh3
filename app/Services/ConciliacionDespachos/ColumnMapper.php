<?php

namespace App\Services\ConciliacionDespachos;

class ColumnMapper
{
    /**
     * Campos canónicos del archivo de despacho.
     *
     * @var array<string, string>
     */
    public const CAMPOS_DESPACHO = [
        'estado' => 'Estado',
        'numero_parte' => 'Número de Parte',
        'descripcion' => 'Descripción',
        'lote' => 'Número de Lote',
        'piezas_contenidas' => 'Piezas Contenidas',
        'recibo_almacen' => 'Recibo de Almacén',
        'fecha_recibo' => 'Fecha de recibo',
        'entrega_carga' => 'Entrega de Carga',
        'fecha_salida' => 'Fecha de salida',
        'ubicacion' => 'Código de la Localización',
        'cantidad' => 'Piezas o cantidad de paletas',
        'embalaje' => 'Embalaje',
        'contenedor' => 'Número de Contenedor',
        'consignatario' => 'Nombre Consignatario',
        'bl' => 'Número de BL',
        'modo_transporte' => 'Modo de Transporte',
        'transportista' => 'Nombre del Transportista',
        'trailer' => 'Entrega de Carga:No. Trailer',
        'placa' => 'Placa del Camión',
        'seguimiento' => 'Número de Seguimiento',
        'paletas' => 'Paletas',
    ];

    /**
     * Campos canónicos del archivo de conciliación SAP.
     *
     * @var array<string, string>
     */
    public const CAMPOS_SAP = [
        'posting_date' => 'Posting Date',
        'plant' => 'Plant',
        'movement_type' => 'Movement Type',
        'movement_type_text' => 'Movement Type Text',
        'storage_location' => 'Storage Location',
        'material_document' => 'Material Document',
        'material' => 'Material',
        'quantity' => 'Quantity',
        'base_uom' => 'Base Unit of Measure',
        'batch' => 'Batch',
        'document_header_text' => 'Document Header Text',
        'reference' => 'Reference',
        'user_name' => 'User name',
        'material_description' => 'Material Description',
        'name_1' => 'Name 1',
        'document_date' => 'Document Date',
        'entry_date' => 'Entry Date',
        'material_doc_item' => 'Material Doc.Item',
        'qty_in_entry_uom' => 'Qty in Un. of Entry',
        'unit_of_entry' => 'Unit of Entry',
    ];

    /**
     * Campos indispensables por archivo (si faltan => REGISTRO INCOMPLETO / validación fallida).
     *
     * @var array<string, string[]>
     */
    public const CAMPOS_REQUERIDOS = [
        'despacho' => ['numero_parte', 'lote'],
        'sap' => ['material', 'quantity', 'movement_type'],
    ];

    /**
     * Keywords por campo (header ya normalizado: minúsin, sin acentos).
     *
     * @var array<string, array<string, string[]>>
     */
    private const KEYWORDS = [
        'despacho' => [
            'estado' => ['estado', 'status', 'state'],
            'numero_parte' => ['numero de parte', 'num parte', 'no parte', 'part number', 'parte', 'material', 'sku', 'codigo'],
            'descripcion' => ['descripcion', 'description', 'desc'],
            'lote' => ['lote', 'lot', 'batch'],
            'piezas_contenidas' => ['piezas contenidas', 'contenido', 'pieces contained'],
            'recibo_almacen' => ['recibo de almacen', 'recibo almacen', 'receipt'],
            'fecha_recibo' => ['fecha de recibo', 'receipt date'],
            'entrega_carga' => ['entrega de carga', 'entrega'],
            'fecha_salida' => ['fecha de salida', 'ship date', 'salida'],
            'ubicacion' => ['localizacion', 'ubicacion', 'location', 'codigo de la localizacion'],
            'cantidad' => ['piezas o cantidad', 'cantidad', 'quantity', 'qty', 'piezas'],
            'embalaje' => ['embalaje', 'packaging'],
            'contenedor' => ['contenedor', 'container'],
            'consignatario' => ['consignatario', 'consignee'],
            'bl' => ['bl', 'bill of lading'],
            'modo_transporte' => ['modo de transporte', 'transport mode'],
            'transportista' => ['transportista', 'carrier'],
            'trailer' => ['carga:no. trailer', 'no. trailer', 'trailer'],
            'placa' => ['placa', 'camion', 'truck plate'],
            'seguimiento' => ['seguimiento', 'tracking'],
            'paletas' => ['paletas', 'pallets', 'palets'],
        ],
        'sap' => [
            'posting_date' => ['posting date', 'fecha de contabilizacion'],
            'plant' => ['plant', 'centro', 'planta'],
            'movement_type' => ['movement type', 'tipo de movimiento'],
            'movement_type_text' => ['movement type text', 'texto tipo movimiento'],
            'storage_location' => ['storage location', 'almacen'],
            'material_document' => ['material document', 'documento de material', 'material doc'],
            'material' => ['material', 'numero de parte', 'part number'],
            'quantity' => ['quantity', 'qty', 'cantidad'],
            'base_uom' => ['base unit of measure', 'uom', 'unidad'],
            'batch' => ['batch', 'lote'],
            'document_header_text' => ['document header text', 'texto cabecera'],
            'reference' => ['reference', 'referencia'],
            'user_name' => ['user name', 'usuario'],
            'material_description' => ['material description', 'descripcion', 'description'],
            'name_1' => ['name 1', 'nombre'],
            'document_date' => ['document date', 'fecha documento'],
            'entry_date' => ['entry date', 'fecha entrada'],
            'material_doc_item' => ['material doc.item', 'material doc item', 'doc.item', 'item'],
            'qty_in_entry_uom' => ['qty in un. of entry', 'qty in un of entry', 'cantidad unidad entrada'],
            'unit_of_entry' => ['unit of entry', 'unidad de entrada'],
        ],
    ];

    /**
     * Normaliza un header para comparación.
     */
    public function normalizarHeader(string $header): string
    {
        $header = mb_strtolower(trim($header));
        $header = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ', 'à', 'è', 'ì', 'ò', 'ù'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n', 'a', 'e', 'i', 'o', 'u'],
            $header
        );
        $header = preg_replace('/[\s_]+/', ' ', $header) ?? $header;
        $header = preg_replace('/[^\w\s%\/\.\-:]/u', '', $header) ?? $header;

        return trim($header);
    }

    /**
     * Detecta automáticamente el mapa campo=>índice de columna.
     *
     * @return array{mapa: array<string,int>, encabezados: string[], sin_reconocer: string[], fila_encabezado: int}
     */
    public function detectar(array $rows, string $tipo): array
    {
        $limite = min(count($rows), 10);
        $mejor = null;

        for ($i = 0; $i < $limite; $i++) {
            $headers = array_map(fn ($c) => $this->normalizarHeader((string) $c), $rows[$i] ?? []);
            $mapa = $this->mapear($headers, $tipo);
            $puntaje = count($mapa);

            if ($mejor === null || $puntaje > $mejor['puntaje']) {
                $mejor = [
                    'mapa' => $mapa,
                    'encabezados' => $headers,
                    'fila_encabezado' => $i,
                    'puntaje' => $puntaje,
                ];
            }

            if ($puntaje >= 3) {
                break;
            }
        }

        if ($mejor === null) {
            $mejor = ['mapa' => [], 'encabezados' => [], 'fila_encabezado' => 0, 'puntaje' => 0];
        }

        $reconocidos = array_flip($mejor['mapa']);
        $sinReconocer = [];
        foreach ($mejor['encabezados'] as $i => $header) {
            if ($header !== '' && ! isset($reconocidos[$i])) {
                $sinReconocer[] = $header;
            }
        }

        return [
            'mapa' => $mejor['mapa'],
            'encabezados' => $mejor['encabezados'],
            'sin_reconocer' => $sinReconocer,
            'fila_encabezado' => $mejor['fila_encabezado'],
        ];
    }

    /**
     * Mapea headers normalizados a campos canónicos por keywords.
     *
     * @return array<string,int>
     */
    public function mapear(array $headers, string $tipo): array
    {
        $keywords = self::KEYWORDS[$tipo] ?? [];
        $mapa = [];

        foreach ($headers as $i => $header) {
            if ($header === '') {
                continue;
            }

            $mejorCampo = null;
            $mejorLen = 0;

            foreach ($keywords as $campo => $kws) {
                if (isset($mapa[$campo])) {
                    continue;
                }

                foreach ($kws as $kw) {
                    if (str_contains($header, $kw) && strlen($kw) > $mejorLen) {
                        $mejorLen = strlen($kw);
                        $mejorCampo = $campo;
                    }
                }
            }

            if ($mejorCampo !== null) {
                $mapa[$mejorCampo] = (int) $i;
            }
        }

        return $mapa;
    }

    /**
     * Aplica un mapeo manual (campo => nombre de columna visible o índice).
     *
     * @return array<string,int>
     */
    public function aplicarManual(array $manual, array $encabezados, string $tipo): array
    {
        $campos = $tipo === 'sap' ? self::CAMPOS_SAP : self::CAMPOS_DESPACHO;
        $mapa = [];

        foreach ($manual as $campo => $headerSeleccionado) {
            if (! isset($campos[$campo])) {
                continue;
            }

            $headerSeleccionado = (string) $headerSeleccionado;

            if ($headerSeleccionado === '') {
                continue;
            }

            $normalizado = $this->normalizarHeader($headerSeleccionado);

            foreach ($encabezados as $i => $header) {
                if ($this->normalizarHeader((string) $header) === $normalizado) {
                    $mapa[$campo] = (int) $i;
                    break;
                }
            }
        }

        return $mapa;
    }

    /**
     * Campos requeridos presentes en el mapa.
     *
     * @return array{ok: bool, faltantes: string[]}
     */
    public function validarRequeridos(array $mapa, string $tipo): array
    {
        $requeridos = self::CAMPOS_REQUERIDOS[$tipo] ?? [];
        $campos = $tipo === 'sap' ? self::CAMPOS_SAP : self::CAMPOS_DESPACHO;
        $faltantes = [];

        foreach ($requeridos as $campo) {
            if (! isset($mapa[$campo])) {
                $faltantes[] = $campos[$campo] ?? $campo;
            }
        }

        // Para despacho: cantidad puede venir de 'piezas_contenidas'
        if ($tipo === 'despacho' && ! isset($mapa['cantidad']) && ! isset($mapa['piezas_contenidas'])) {
            if (! in_array($campos['cantidad'] ?? 'cantidad', $faltantes, true)) {
                $faltantes[] = $campos['cantidad'] ?? 'Piezas o cantidad de paletas';
            }
        }

        return ['ok' => count($faltantes) === 0, 'faltantes' => $faltantes];
    }
}
