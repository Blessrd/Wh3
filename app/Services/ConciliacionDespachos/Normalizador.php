<?php

namespace App\Services\ConciliacionDespachos;

class Normalizador
{
    /**
     * Texto limpio: trim, colapsa espacios internos, convierte null a cadena vacía.
     */
    public function texto($valor): string
    {
        if ($valor === null) {
            return '';
        }

        $texto = trim((string) $valor);
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return $texto;
    }

    /**
     * Número aceptando formatos ES (1.234,56) y EN (1,234.56).
     * Devuelve float|null si no es interpretable como número.
     */
    public function numero($valor): ?float
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return null;
        }

        $texto = str_replace([' ', "\xc2\xa0"], '', $texto);

        if (! preg_match('/^-?\d{1,3}(?:[.,\s]\d{3})*(?:[.,]\d+)?$|^-?\d+(?:[.,]\d+)?$/', $texto)) {
            if (is_numeric($texto)) {
                return (float) $texto;
            }

            return null;
        }

        $ultimoPunto = strrpos($texto, '.');
        $ultimaComa = strrpos($texto, ',');

        if ($ultimoPunto !== false && $ultimaComa !== false) {
            $decimalesEn = $ultimoPunto > $ultimaComa;
            $separadorDecimal = $decimalesEn ? '.' : ',';
            $separadorMiles = $decimalesEn ? ',' : '.';
        } elseif ($ultimoPunto !== false || $ultimaComa !== false) {
            $pos = max((int) $ultimoPunto, (int) $ultimaComa);
            $cola = substr($texto, $pos + 1);
            $esDecimal = strlen($cola) !== 3;
            $separadorDecimal = $esDecimal ? ($ultimoPunto !== false ? '.' : ',') : null;
            $separadorMiles = $separadorDecimal === null
                ? ($ultimoPunto !== false ? '.' : ',')
                : ($separadorDecimal === '.' ? ',' : '.');
        } else {
            $separadorDecimal = null;
            $separadorMiles = null;
        }

        if ($separadorMiles !== null) {
            $texto = str_replace($separadorMiles, '', $texto);
        }

        if ($separadorDecimal !== null) {
            $texto = str_replace($separadorDecimal, '.', $texto);
        }

        if (! is_numeric($texto)) {
            return null;
        }

        return (float) $texto;
    }

    /**
     * Fecha desde strings comunes (d/m/Y, d-m-Y, Y-m-d, d.m.Y) o serial de Excel.
     * Devuelve string 'Y-m-d' o null.
     */
    public function fecha($valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            $numero = (float) $valor;

            if ($numero > 20000 && $numero < 60000) {
                try {
                    $fecha = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($numero);

                    return $fecha->format('Y-m-d');
                } catch (\Throwable) {
                    return null;
                }
            }

            return null;
        }

        $texto = trim((string) $valor);

        $formatos = ['d/m/Y', 'd-m-Y', 'd.m.Y', 'Y-m-d', 'd/m/y', 'm/d/Y'];
        foreach ($formatos as $formato) {
            $fecha = \DateTime::createFromFormat($formato, $texto);
            if ($fecha !== false && $fecha->format($formato) === $texto) {
                return $fecha->format('Y-m-d');
            }
        }

        try {
            return (new \DateTime($texto))->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Cantidad comparable de SAP según la categoría del tipo de movimiento.
     * No convierte indiscriminadamente: solo categorías salientes usan valor absoluto.
     */
    public function cantidadComparable(?float $valor, string $categoria, array $categoriasComparables): float
    {
        if ($valor === null) {
            return 0.0;
        }

        if (! in_array($categoria, $categoriasComparables, true)) {
            return 0.0;
        }

        return abs($valor);
    }

    /**
     * Clave normalizada de UoM: minúsculas, sin espacios.
     */
    public function uom(string $unidad): string
    {
        return mb_strtolower($this->texto($unidad));
    }

    /**
     * Dos unidades son comparables si coinciden o están declaradas como equivalentes.
     */
    public function uomCompatible(string $a, string $b, array $mapaCompatibles): bool
    {
        $a = $this->uom($a);
        $b = $this->uom($b);

        if ($a === '' || $b === '') {
            return true;
        }

        if ($a === $b) {
            return true;
        }

        foreach ($mapaCompatibles as $base => $equivalentes) {
            $grupo = array_merge([$base], array_map(fn ($e) => $this->uom((string) $e), $equivalentes));

            if (in_array($a, $grupo, true) && in_array($b, $grupo, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Nave a partir del código de ubicación (D1* → Nave3, D2* → Nave7).
     */
    public function naveDesdeUbicacion(string $ubicacion, array $mapa): ?string
    {
        $ubicacion = mb_strtoupper($this->texto($ubicacion));

        foreach ($mapa as $prefijo => $nave) {
            if ($ubicacion !== '' && str_starts_with($ubicacion, mb_strtoupper((string) $prefijo))) {
                return $nave;
            }
        }

        return null;
    }
}
