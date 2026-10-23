<?php

namespace App\Services\ConciliacionDespachos;

use PhpOffice\PhpSpreadsheet\IOFactory;

class LectorArchivos
{
    /**
     * Lee un archivo Excel/CSV y devuelve todas las filas como arrays.
     *
     * @return array{rows: array<int, array<int, mixed>>, nombre: string}
     */
    public function leer(string $ruta, string $nombreOriginal): array
    {
        $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

        // Detectar el formato REAL por magic bytes (no por extensión).
        // Muchos "Excel" exportados desde SAP/Excel son en realidad CSV/texto
        // con extensión falsa, lo que hace fallar a PhpSpreadsheet con iconv().
        $formato = $this->detectarFormato($ruta);

        if ($formato === 'xlsx' || $formato === 'xls') {
            try {
                $rows = $this->leerExcel($ruta);
            } catch (\Throwable $e) {
                // Fallback: intentar leer como CSV/texto.
                try {
                    $rows = $this->leerCsv($ruta);
                } catch (\Throwable) {
                    throw new \RuntimeException(
                        'El archivo no parece ser un Excel/CSV válido. ' .
                        'Exporte el archivo como CSV o XLSX real desde la aplicación de origen.'
                    );
                }
            }
        } else {
            $rows = $this->leerCsv($ruta);
        }

        if (count($rows) === 0) {
            throw new \RuntimeException('El archivo está vacío o no contiene registros.');
        }

        return [
            'rows' => $rows,
            'nombre' => $nombreOriginal,
        ];
    }

    /**
     * Detecta el formato real del archivo por sus magic bytes.
     *
     * @return 'xlsx'|'xls'|'csv'
     */
    public function detectarFormato(string $ruta): string
    {
        $handle = fopen($ruta, 'rb');

        if ($handle === false) {
            return 'csv';
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        if ($bytes === false || $bytes === '') {
            return 'csv';
        }

        // XLSX (ZIP/OOXML)
        if (str_starts_with($bytes, "PK\x03\x04")) {
            return 'xlsx';
        }

        // XLS legacy (OLE2)
        if (str_starts_with($bytes, "\xD0\xCF\x11\xE0")) {
            return 'xls';
        }

        return 'csv';
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function leerExcel(string $ruta): array
    {
        $spreadsheet = IOFactory::load($ruta);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false) ?: [];
        $spreadsheet->disconnectWorksheets();

        return $rows;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function leerCsv(string $ruta): array
    {
        $contenido = file_get_contents($ruta);

        if ($contenido === false) {
            throw new \RuntimeException('No se pudo leer el archivo.');
        }

        // Eliminar BOM si existe.
        if (str_starts_with($contenido, "\xEF\xBB\xBF")) {
            $contenido = substr($contenido, 3);
        }

        // Normalizar saltos de línea.
        $contenido = str_replace("\r\n", "\n", $contenido);
        $contenido = str_replace("\r", "\n", $contenido);

        // Convertir codificación a UTF-8 de forma tolerante.
        $contenido = $this->normalizarCodificacion($contenido);

        $delimitador = $this->detectarDelimitador($contenido);
        $rows = [];

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, $contenido);
        rewind($handle);

        while (($fila = fgetcsv($handle, 0, $delimitador, '"', '\\')) !== false) {
            $rows[] = array_map(fn ($c) => $c === null ? '' : $c, $fila);
        }

        fclose($handle);

        return $rows;
    }

    /**
     * Convierte el contenido a UTF-8 de forma tolerante, evitando fallos
     * de iconv por caracteres multibyte incompletos.
     */
    private function normalizarCodificacion(string $contenido): string
    {
        if ($contenido === '') {
            return '';
        }

        // Si ya es UTF-8 válido, no tocar.
        if (mb_check_encoding($contenido, 'UTF-8')) {
            return $contenido;
        }

        // Intentar CP1252/Latin-1 → UTF-8 (típico de exports Windows/SAP).
        $convertido = @mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');

        if (is_string($convertido) && mb_check_encoding($convertido, 'UTF-8')) {
            return $convertido;
        }

        // Fallback: intentar con ISO-8859-1.
        $convertido = @mb_convert_encoding($contenido, 'UTF-8', 'ISO-8859-1');

        if (is_string($convertido) && mb_check_encoding($convertido, 'UTF-8')) {
            return $convertido;
        }

        // Último recurso: iconv con sustitución (no fatal).
        $convertido = @iconv('UTF-8', 'UTF-8//IGNORE', $contenido);

        if (is_string($convertido)) {
            return $convertido;
        }

        // Eliminar bytes inválidos manualmente como último recurso.
        $contenido = preg_replace('/[\x80-\xFF](?![\x80-\xBF])/', '', $contenido) ?? $contenido;

        return $contenido;
    }

    private function detectarDelimitador(string $contenido): string
    {
        $primeraLinea = strtok($contenido, "\n") ?: '';

        $conteos = [
            ';' => substr_count($primeraLinea, ';'),
            ',' => substr_count($primeraLinea, ','),
            "\t" => substr_count($primeraLinea, "\t"),
        ];

        arsort($conteos);

        $mejor = array_key_first($conteos);

        return $conteos[$mejor] > 0 ? $mejor : ',';
    }
}
