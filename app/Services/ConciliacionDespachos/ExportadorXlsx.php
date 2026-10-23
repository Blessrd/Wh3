<?php

namespace App\Services\ConciliacionDespachos;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportadorXlsx
{
    public function exportar(array $resultado): string
    {
        $spreadsheet = new Spreadsheet();
        $ws = $spreadsheet->getActiveSheet();
        $ws->setTitle('Conciliación');

        $meta = $resultado['metadatos'] ?? [];
        $tabla = $resultado['tabla'] ?? [];
        $dashboard = $resultado['dashboard'] ?? [];

        // Título
        $ws->mergeCells('A1:E1');
        $ws->setCellValue('A1', 'Conciliación Magaya vs SAP');
        $ws->getStyle('A1')->getFont()->setBold(true)->setSize(14)->setColor(new Color('FFFFFFFF'));
        $ws->getStyle('A1')->getFill()->setFillType('solid')->getStartColor()->setRGB('1E3A8A');
        $ws->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getRowDimension(1)->setRowHeight(24);

        // Metadatos
        $fila = 3;
        $ws->setCellValue('A' . $fila, 'Fecha de ejecución:');
        $ws->getStyle('A' . $fila)->getFont()->setBold(true);
        $ws->setCellValue('B' . $fila, $meta['fecha_ejecucion'] ?? '');
        $fila++;
        $ws->setCellValue('A' . $fila, 'Archivo Magaya:');
        $ws->getStyle('A' . $fila)->getFont()->setBold(true);
        $ws->setCellValue('B' . $fila, $meta['archivo_magaya'] ?? '');
        $fila++;
        $ws->setCellValue('A' . $fila, 'Archivo SAP:');
        $ws->getStyle('A' . $fila)->getFont()->setBold(true);
        $ws->setCellValue('B' . $fila, $meta['archivo_sap'] ?? '');
        $fila += 2;

        // Resumen
        $ws->setCellValue('A' . $fila, 'Resumen');
        $ws->getStyle('A' . $fila)->getFont()->setBold(true)->setColor(new Color('1E3A8A'));
        $fila++;
        $resumen = [
            'Total materiales' => $dashboard['total_materiales'] ?? count($tabla),
            'Completos' => $dashboard['completos'] ?? 0,
            'Incompletos' => $dashboard['incompletos'] ?? 0,
            'No encontrados' => $dashboard['no_encontrados'] ?? 0,
            'Total piezas Magaya' => $dashboard['piezas_magaya'] ?? 0,
            'Total piezas SAP' => $dashboard['piezas_sap'] ?? 0,
            'Diferencia neta' => $dashboard['diferencia_neta'] ?? 0,
        ];
        foreach ($resumen as $etiqueta => $valor) {
            $ws->setCellValue('A' . $fila, $etiqueta);
            $ws->setCellValue('B' . $fila, $valor);
            if (is_numeric($valor) && ! is_string($valor)) {
                $ws->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            }
            $fila++;
        }
        $fila += 1;

        // Encabezados de tabla
        $headers = ['Material', 'Piezas Magaya', 'Piezas SAP', 'Diferencia', 'Estado'];
        foreach ($headers as $col => $header) {
            $letra = chr(65 + $col);
            $coord = $letra . $fila;
            $ws->setCellValue($coord, $header);
            $ws->getStyle($coord)->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
            $ws->getStyle($coord)->getFill()->setFillType('solid')->getStartColor()->setRGB('1E3A8A');
            $ws->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $ws->getStyle($coord)->getBorders()->getBottom()
                ->setBorderStyle(Border::BORDER_THIN)->setColor(new Color('111827'));
        }
        $fila++;

        // Datos
        $inicioDatos = $fila;
        foreach ($tabla as $registro) {
            $ws->setCellValue('A' . $fila, $registro['material']);
            $ws->setCellValueExplicit('A' . $fila, (string) $registro['material'], DataType::TYPE_STRING);
            $ws->setCellValue('B' . $fila, $registro['cantidad_magaya']);
            $ws->setCellValue('C' . $fila, $registro['cantidad_sap']);
            $ws->setCellValue('D' . $fila, $registro['diferencia']);
            $ws->setCellValue('E' . $fila, $registro['estado']);

            $ws->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            $ws->getStyle('C' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
            $ws->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');

            $ws->getStyle('A' . $fila . ':E' . $fila)->getBorders()->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN);

            $fila++;
        }

        if (count($tabla) === 0) {
            $ws->setCellValue('A' . $fila, 'Sin registros');
        }

        // Anchos de columna
        $ws->getColumnDimension('A')->setWidth(24);
        $ws->getColumnDimension('B')->setWidth(16);
        $ws->getColumnDimension('C')->setWidth(16);
        $ws->getColumnDimension('D')->setWidth(16);
        $ws->getColumnDimension('E')->setWidth(14);

        // No encontrados (si hay)
        $noEncontrados = $resultado['no_encontrados'] ?? [];
        if (count($noEncontrados) > 0) {
            $fila += 2;
            $ws->setCellValue('A' . $fila, 'Materiales no encontrados en el otro archivo');
            $ws->getStyle('A' . $fila)->getFont()->setBold(true)->setColor(new Color('DC2626'));
            $fila++;

            $headers2 = ['Material', 'Piezas Magaya', 'Piezas SAP', 'Diferencia', 'Origen'];
            foreach ($headers2 as $col => $header) {
                $letra = chr(65 + $col);
                $coord = $letra . $fila;
                $ws->setCellValue($coord, $header);
                $ws->getStyle($coord)->getFont()->setBold(true)->setColor(new Color('FFFFFFFF'));
                $ws->getStyle($coord)->getFill()->setFillType('solid')->getStartColor()->setRGB('DC2626');
                $ws->getStyle($coord)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $fila++;

            foreach ($noEncontrados as $nd) {
                $ws->setCellValue('A' . $fila, $nd['material']);
                $ws->setCellValueExplicit('A' . $fila, (string) $nd['material'], DataType::TYPE_STRING);
                $ws->setCellValue('B' . $fila, $nd['cantidad_magaya']);
                $ws->setCellValue('C' . $fila, $nd['cantidad_sap']);
                $ws->setCellValue('D' . $fila, $nd['diferencia']);
                $ws->setCellValue('E' . $fila, $nd['origen']);

                $ws->getStyle('B' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->getStyle('C' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');
                $ws->getStyle('D' . $fila)->getNumberFormat()->setFormatCode('#,##0.00');

                $ws->getStyle('A' . $fila . ':E' . $fila)->getBorders()->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                $fila++;
            }
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $contenido = ob_get_clean();
        $spreadsheet->disconnectWorksheets();

        return $contenido;
    }
}
