<?php

namespace App\Services\ConciliacionDespachos;

class AlmacenTemporal
{
    private const SESSION_TOKEN = 'cd_token';

    /**
     * Token de sesión para la sesión actual de conciliación de despachos.
     */
    public function token(): string
    {
        $token = session(self::SESSION_TOKEN);

        if (! is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(16));
            session([self::SESSION_TOKEN => $token]);
        }

        if (! preg_match('/^[a-f0-9]{32}$/', $token)) {
            $token = bin2hex(random_bytes(16));
            session([self::SESSION_TOKEN => $token]);
        }

        return $token;
    }

    /**
     * Directorio de trabajo de la sesión actual (bajo storage/app).
     */
    public function directorio(): string
    {
        $base = storage_path('app/conciliacion-despachos');
        $dir = $base . DIRECTORY_SEPARATOR . $this->token();

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    /**
     * Guarda un archivo subido conservando la extensión. Devuelve la ruta guardada.
     */
    public function guardarArchivo($file, string $tipo): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'xlsx');
        $ruta = $this->directorio() . DIRECTORY_SEPARATOR . $tipo . '.' . $extension;

        foreach (glob($this->directorio() . DIRECTORY_SEPARATOR . $tipo . '.*') ?: [] as $viejo) {
            @unlink($viejo);
        }

        $file->move($this->directorio(), $tipo . '.' . $extension);

        if (! is_file($ruta)) {
            throw new \RuntimeException('No se pudo guardar el archivo temporal.');
        }

        return $ruta;
    }

    public function guardarJson(string $nombre, array $datos): void
    {
        file_put_contents(
            $this->rutaJson($nombre),
            json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]'
        );
    }

    public function leerJson(string $nombre): ?array
    {
        $ruta = $this->rutaJson($nombre);

        if (! is_file($ruta)) {
            return null;
        }

        $contenido = file_get_contents($ruta);

        if ($contenido === false || $contenido === '') {
            return null;
        }

        $datos = json_decode($contenido, true);

        return is_array($datos) ? $datos : null;
    }

    public function existeJson(string $nombre): bool
    {
        return is_file($this->rutaJson($nombre));
    }

    public function nombreArchivoGuardado(string $tipo): ?string
    {
        $archivos = glob($this->directorio() . DIRECTORY_SEPARATOR . $tipo . '.*') ?: [];

        if (count($archivos) === 0) {
            return null;
        }

        return $this->leerJson($tipo . '_meta')['nombre'] ?? basename($archivos[0]);
    }

    public function rutaArchivoGuardado(string $tipo): ?string
    {
        $archivos = glob($this->directorio() . DIRECTORY_SEPARATOR . $tipo . '.*') ?: [];

        return $archivos[0] ?? null;
    }

    /**
     * Borra archivos, JSON y metadatos de la sesión actual.
     */
    public function limpiar(): void
    {
        $dir = $this->directorio();

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') ?: [] as $archivo) {
            if (is_file($archivo)) {
                @unlink($archivo);
            }
        }

        session()->forget([
            self::SESSION_TOKEN,
            'cd_despacho_mapa',
            'cd_sap_mapa',
            'cd_config',
            'cd_validado',
            'cd_resultado',
            'cd_errores',
        ]);
    }

    private function rutaJson(string $nombre): string
    {
        if (! preg_match('/^[a-z0-9_]+$/', $nombre)) {
            throw new \InvalidArgumentException('Nombre de archivo temporal inválido.');
        }

        return $this->directorio() . DIRECTORY_SEPARATOR . $nombre . '.json';
    }
}
