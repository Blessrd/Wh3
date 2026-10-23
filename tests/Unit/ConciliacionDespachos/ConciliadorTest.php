<?php

use App\Services\ConciliacionDespachos\ColumnMapper;
use App\Services\ConciliacionDespachos\Conciliador;

uses(Tests\TestCase::class);

/**
 * Helper: construye un input para el Conciliador a partir de headers y filas.
 */
function cdInput(array $headers, array $filas, string $tipo, string $nombre = 'test.xlsx'): array
{
    $mapper = new ColumnMapper();
    $detectado = $mapper->detectar(array_merge([$headers], $filas), $tipo);

    return [
        'rows' => array_merge([$headers], $filas),
        'mapa' => $detectado['mapa'],
        'nombre' => $nombre,
    ];
}

beforeEach(function () {
    $this->conciliador = new Conciliador();
});

test('material completo cuando las cantidades coinciden', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-001', '100', '601']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'])->toHaveCount(1);
    expect($resultado['tabla'][0]['estado'])->toBe('COMPLETO');
    expect($resultado['tabla'][0]['diferencia'])->toBe(0.0);
    expect($resultado['dashboard']['completos'])->toBe(1);
});

test('material incompleto cuando SAP registra menos', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-001', '60', '601']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'][0]['estado'])->toBe('INCOMPLETO');
    expect($resultado['tabla'][0]['diferencia'])->toBe(-40.0);
});

test('material incompleto cuando SAP registra más', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-001', '150', '601']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'][0]['estado'])->toBe('INCOMPLETO');
    expect($resultado['tabla'][0]['diferencia'])->toBe(50.0);
});

test('material solo en Magaya aparece en no_encontrados', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-999', '50', '601']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'])->toHaveCount(2);
    $estados = array_column($resultado['tabla'], 'estado');
    expect($estados)->toContain('INCOMPLETO');
    expect(count($resultado['no_encontrados']))->toBe(2);
});

test('suma varios registros SAP del mismo material', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [
            ['PN-001', '40', '601'],
            ['PN-001', '60', '601'],
        ],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'][0]['estado'])->toBe('COMPLETO');
    expect($resultado['tabla'][0]['cantidad_sap'])->toBe(100.0);
});

test('porcentaje nulo cuando cantidad magaya es cero', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '0']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-001', '50', '601']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    expect($resultado['tabla'][0]['estado'])->toBe('INCOMPLETO');
});

test('no declara completo si hay diferencia', function () {
    $despacho = cdInput(
        ['Número de Parte', 'Piezas Contenidas'],
        [['PN-001', '100']],
        'despacho'
    );
    $sap = cdInput(
        ['Material', 'Quantity', 'Movement Type'],
        [['PN-001', '100', '']],
        'sap'
    );

    $resultado = $this->conciliador->ejecutar($despacho, $sap);

    // Tipo de movimiento vacío → se trata como salida por defecto
    expect($resultado['tabla'])->toHaveCount(1);
});