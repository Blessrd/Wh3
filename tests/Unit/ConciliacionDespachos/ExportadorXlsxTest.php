<?php

use App\Services\ConciliacionDespachos\ColumnMapper;
use App\Services\ConciliacionDespachos\Conciliador;
use App\Services\ConciliacionDespachos\ExportadorXlsx;

uses(Tests\TestCase::class);

test('la pagina del modulo carga correctamente', function () {
    $this->get('/conciliacion-despachos')->assertStatus(200);
});

test('exportar xlsx genera las 9 hojas', function () {
    $mapper = new ColumnMapper();

    $despachoRows = [
        ['Número de Parte', 'Número de Lote', 'Piezas o cantidad de paletas', 'Entrega de Carga:No. Trailer'],
        ['PN-001', 'L1', '100', 'TR-1'],
        ['PN-002', 'L2', '200', 'TR-1'],
    ];
    $sapRows = [
        ['Material', 'Batch', 'Quantity', 'Movement Type', 'Material Document'],
        ['PN-001', 'L1', '100', '601', 'DOC-1'],
        ['PN-002', 'L2', '150', '601', 'DOC-2'],
    ];

    $d = $mapper->detectar($despachoRows, 'despacho');
    $s = $mapper->detectar($sapRows, 'sap');

    $resultado = (new Conciliador())->ejecutar(
        ['rows' => $despachoRows, 'mapa' => $d['mapa'], 'nombre' => 'd.xlsx'],
        ['rows' => $sapRows, 'mapa' => $s['mapa'], 'nombre' => 's.xlsx'],
    );

    $xlsx = (new ExportadorXlsx())->exportar($resultado);

    expect(strlen($xlsx))->toBeGreaterThan(1000);
    expect(substr($xlsx, 0, 2))->toBe('PK'); // firma ZIP/OOXML

    $tmp = tempnam(sys_get_temp_dir(), 'cdxlsx') . '.xlsx';
    file_put_contents($tmp, $xlsx);

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmp);
    $nombres = array_map(fn ($ws) => $ws->getTitle(), $spreadsheet->getAllSheets());

    expect($nombres)->toHaveCount(1);
    expect($nombres[0])->toBe('Conciliación');

    $spreadsheet->disconnectWorksheets();
    @unlink($tmp);
});

test('flujo completo via HTTP: cargar, validar y ejecutar', function () {
    // Crear archivos XLSX temporales en memoria
    $despacho = crearXlsxTemp([
        ['Número de Parte', 'Número de Lote', 'Piezas o cantidad de paletas'],
        ['PN-100', 'L10', '50'],
    ]);
    $sap = crearXlsxTemp([
        ['Material', 'Batch', 'Quantity', 'Movement Type'],
        ['PN-100', 'L10', '50', '601'],
    ]);

    // Cargar despacho
    $this->post('/conciliacion-despachos/cargar', [
        'tipo' => 'despacho',
        'archivo' => $despacho,
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertStatus(200);

    // Cargar SAP
    $this->post('/conciliacion-despachos/cargar', [
        'tipo' => 'sap',
        'archivo' => $sap,
    ], ['X-Requested-With' => 'XMLHttpRequest'])->assertStatus(200);

    // Ejecutar
    $this->post('/conciliacion-despachos/ejecutar', [
        'tipos_movimiento' => ['601', '313', '315'],
    ])->assertStatus(200);
});

/**
 * Crea un XLSX temporal y devuelve un UploadedFile simulado.
 */
function crearXlsxTemp(array $filas): \Illuminate\Http\UploadedFile
{
    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $ws = $ss->getActiveSheet();

    foreach ($filas as $r => $fila) {
        foreach ($fila as $c => $valor) {
            $ws->setCellValue(chr(65 + $c) . ($r + 1), $valor);
        }
    }

    $ruta = tempnam(sys_get_temp_dir(), 'cd') . '.xlsx';
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save($ruta);
    $ss->disconnectWorksheets();

    return new \Illuminate\Http\UploadedFile($ruta, 'archivo.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
}
