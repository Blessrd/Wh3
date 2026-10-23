<?php

namespace App\Http\Controllers;

use App\Services\ConciliacionDespachos\ConciliacionDespachosService;
use Illuminate\Http\Request;

class ConciliacionDespachosController extends Controller
{
    public function __construct(
        private readonly ConciliacionDespachosService $service,
    ) {
    }

    public function index()
    {
        return view('conciliacion-despachos.index', $this->datosVista());
    }

    public function cargar(Request $request)
    {
        $request->validate([
            'tipo' => 'required|in:despacho,sap',
            'archivo' => 'required|file|extensions:xlsx,xls,csv,txt',
        ]);

        $tipo = $request->input('tipo');

        try {
            $info = $this->service->cargar($request->file('archivo'), $tipo);
        } catch (\Throwable $e) {
            return back()->with('cd_error', 'No se pudo leer el archivo de ' . $tipo . ': ' . $e->getMessage());
        }

        $datos = $this->datosVista();
        $datos['recienCargado'] = $tipo;
        $datos['infoCarga'] = $info;
        $datos['preview_' . $tipo] = $info;

        return view('conciliacion-despachos.index', $datos);
    }

    public function validar(Request $request)
    {
        $manualDespacho = $request->input('mapa_despacho', []);
        $manualSap = $request->input('mapa_sap', []);
        $config = $this->leerConfigRequest($request);

        try {
            $validacion = $this->service->validar($manualDespacho, $manualSap);

            if (! $validacion['ok']) {
                return back()->with('cd_errores', $validacion['errores'])->withInput();
            }

            $resultado = $this->service->ejecutar($config);
        } catch (\Throwable $e) {
            return back()->with('cd_error', 'Error al validar/ejecutar: ' . $e->getMessage());
        }

        $datos = $this->datosVista();
        $datos['resultado'] = $resultado;
        $datos['validacion'] = $validacion;

        return view('conciliacion-despachos.index', $datos);
    }

    public function ejecutar(Request $request)
    {
        $estado = $this->service->estado();

        if (! $estado['despacho'] || ! $estado['sap']) {
            return back()->with('cd_error', 'Debe cargar ambos archivos antes de ejecutar la conciliación.');
        }

        try {
            $resultado = $this->service->ejecutar($this->leerConfigRequest($request));
        } catch (\Throwable $e) {
            return back()->with('cd_error', 'No se pudo ejecutar la conciliación: ' . $e->getMessage());
        }

        $datos = $this->datosVista();
        $datos['resultado'] = $resultado;

        return view('conciliacion-despachos.index', $datos);
    }

    public function exportar()
    {
        try {
            $export = $this->service->exportar();
        } catch (\Throwable $e) {
            return back()->with('cd_error', $e->getMessage());
        }

        return response($export['contenido'], 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $export['nombre'] . '"',
            'Content-Length' => (string) strlen($export['contenido']),
        ]);
    }

    public function limpiar()
    {
        $this->service->almacen()->limpiar();

        return redirect('/conciliacion-despachos');
    }

    /* ─── Helpers ─── */

    private function datosVista(): array
    {
        return [
            'estado' => $this->service->estado(),
            'metaDespacho' => $this->service->metaCargada('despacho'),
            'metaSap' => $this->service->metaCargada('sap'),
            'previewDespacho' => null,
            'previewSap' => null,
            'resultado' => $this->service->resultadoGuardado(),
            'config' => $this->service->configGuardada(),
            'validacion' => null,
            'error' => session('cd_error'),
            'errores' => session('cd_errores'),
            'recienCargado' => null,
            'infoCarga' => null,
        ];
    }

    private function leerConfigRequest(Request $request): array
    {
        $tipos = $request->input('tipos_movimiento', []);

        if (! is_array($tipos) || count($tipos) === 0) {
            $base = config('conciliacion-despachos.tipos_movimiento', []);
            $tipos = array_keys($base);
        }

        return [
            'tipos_incluidos' => array_map('strval', $tipos),
        ];
    }
}
