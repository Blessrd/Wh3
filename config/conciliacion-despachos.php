<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Tipos de movimiento SAP
    |--------------------------------------------------------------------------
    | Categorías:
    |   salida          -> cuenta como piezas que salen (comparable > 0)
    |   entrada         -> no suma al despacho
    |   traslado_salida -> movimiento de salida entre ubicaciones (almacén)
    |   traslado_entrada-> contrapartida de traslado entre ubicaciones
    |   anulacion       -> reversa / anulación
    */

    'tipos_movimiento' => [
        '101' => ['texto' => 'Entrada de mercancía', 'categoria' => 'entrada'],
        '102' => ['texto' => 'Anulación entrada de mercancía', 'categoria' => 'anulacion'],
        '301' => ['texto' => 'Entrada sin pedido', 'categoria' => 'entrada'],
        '302' => ['texto' => 'Anulación entrada sin pedido', 'categoria' => 'anulacion'],
        '311' => ['texto' => 'Transferencia entre almacenes (entrada)', 'categoria' => 'entrada'],
        '312' => ['texto' => 'Anulación transferencia entre almacenes', 'categoria' => 'anulacion'],
        '313' => ['texto' => 'Transferencia salida de ubicación', 'categoria' => 'traslado_salida'],
        '314' => ['texto' => 'Anulación transferencia salida de ubicación', 'categoria' => 'anulacion'],
        '315' => ['texto' => 'Transferencia entrada de ubicación', 'categoria' => 'traslado_entrada'],
        '316' => ['texto' => 'Anulación transferencia entrada de ubicación', 'categoria' => 'anulacion'],
        '601' => ['texto' => 'Salida de mercancía (venta)', 'categoria' => 'salida'],
        '602' => ['texto' => 'Anulación salida de mercancía', 'categoria' => 'anulacion'],
        '701' => ['texto' => 'Entrada de inventario', 'categoria' => 'entrada'],
        '702' => ['texto' => 'Salida de inventario', 'categoria' => 'salida'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorías que suman cantidad comparable (piezas salientes)
    |--------------------------------------------------------------------------
    */

    'categorias_comparables' => ['salida', 'traslado_salida'],

    /*
    |--------------------------------------------------------------------------
    | Naves por código de ubicación (prefijo)
    |--------------------------------------------------------------------------
    */

    'naves' => [
        'D1' => 'Nave3',
        'D2' => 'Nave7',
    ],

    /*
    |--------------------------------------------------------------------------
    | Unidades de medida compatibles (clave normalizada => equivalentes)
    | Si dos UoM no están empatadas ni son idénticas => UNIDAD INCOMPATIBLE
    |--------------------------------------------------------------------------
    */

    'unidades_compatibles' => [
        // 'ea' => ['pcs', 'und', 'pza'],
        // 'kg' => ['kgs'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filas de vista previa mostradas al cargar un archivo
    |--------------------------------------------------------------------------
    */

    'preview_filas' => 10,

];
