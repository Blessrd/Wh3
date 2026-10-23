<?php

use App\Services\ConciliacionDespachos\ColumnMapper;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->mapper = new ColumnMapper();
});

test('normaliza headers con acentos y espacios', function () {
    expect($this->mapper->normalizarHeader('  Número de Parte '))->toBe('numero de parte');
    expect($this->mapper->normalizarHeader('Material_Doc.Item'))->toBe('material doc.item');
});

test('detecta columnas del despacho', function () {
    $rows = [
        ['Número de Parte', 'Descripción', 'Número de Lote', 'Piezas o cantidad de paletas', 'Entrega de Carga:No. Trailer'],
        ['PN-001', 'Widget', 'L-100', '500', 'TR-9'],
    ];

    $resultado = $this->mapper->detectar($rows, 'despacho');

    expect($resultado['mapa'])->toHaveKeys(['numero_parte', 'descripcion', 'lote', 'cantidad', 'trailer']);
    expect($resultado['fila_encabezado'])->toBe(0);
    expect($resultado['mapa']['numero_parte'])->toBe(0);
});

test('detecta columnas SAP con abreviaturas', function () {
    $rows = [
        ['Posting Date', 'Movement Type', 'Material', 'Quantity', 'Batch', 'Material Document', 'Reference'],
        ['2026-01-15', '601', 'MAT-1', '100', 'L1', 'DOC-1', 'DES-001'],
    ];

    $resultado = $this->mapper->detectar($rows, 'sap');

    expect($resultado['mapa'])->toHaveKeys(['posting_date', 'movement_type', 'material', 'quantity', 'batch', 'material_document', 'reference']);
});

test('reconoce Material Doc.Item y Qty in Un. of Entry', function () {
    $rows = [
        ['Material', 'Material Doc.Item', 'Qty in Un. of Entry', 'Unit of Entry'],
        ['M1', '1', '10', 'EA'],
    ];

    $resultado = $this->mapper->detectar($rows, 'sap');

    expect($resultado['mapa'])->toHaveKeys(['material', 'material_doc_item', 'qty_in_entry_uom', 'unit_of_entry']);
});

test('validar requeridos detecta faltantes', function () {
    $validacion = $this->mapper->validarRequeridos(['numero_parte' => 0, 'lote' => 1], 'despacho');

    expect($validacion['ok'])->toBeFalse();
    expect($validacion['faltantes'])->toContain('Piezas o cantidad de paletas');
});

test('validar requeridos ok con mapa completo', function () {
    $validacion = $this->mapper->validarRequeridos([
        'numero_parte' => 0,
        'lote' => 1,
        'cantidad' => 2,
    ], 'despacho');

    expect($validacion['ok'])->toBeTrue();
});

test('aplicar manual reemplaza el mapa', function () {
    $encabezados = ['Parte X', 'Lote Y', 'Cant Z'];

    $mapa = $this->mapper->aplicarManual([
        'numero_parte' => 'Parte X',
        'lote' => 'Lote Y',
        'cantidad' => 'Cant Z',
    ], $encabezados, 'despacho');

    expect($mapa)->toBe([
        'numero_parte' => 0,
        'lote' => 1,
        'cantidad' => 2,
    ]);
});
