<?php

use App\Services\ConciliacionDespachos\Normalizador;

uses(Tests\TestCase::class);

beforeEach(function () {
    $this->n = new Normalizador();
});

test('texto trimea y colapsa espacios', function () {
    expect($this->n->texto('  hola   mundo  '))->toBe('hola mundo');
    expect($this->n->texto(null))->toBe('');
});

test('numero interpreta formato ES', function () {
    expect($this->n->numero('1.234,56'))->toBe(1234.56);
    expect($this->n->numero('1234,56'))->toBe(1234.56);
    expect($this->n->numero('1,234'))->toBe(1234.0);
});

test('numero interpreta formato EN', function () {
    expect($this->n->numero('1,234.56'))->toBe(1234.56);
    expect($this->n->numero('1234.56'))->toBe(1234.56);
});

test('numero devuelve null para basura', function () {
    expect($this->n->numero('abc'))->toBeNull();
    expect($this->n->numero(''))->toBeNull();
    expect($this->n->numero(null))->toBeNull();
});

test('numero acepta enteros y flotantes nativos', function () {
    expect($this->n->numero(42))->toBe(42.0);
    expect($this->n->numero(3.14))->toBe(3.14);
});

test('fecha parsea formato dia-mes-anio con barras', function () {
    expect($this->n->fecha('25/12/2025'))->toBe('2025-12-25');
});

test('fecha parsea formato ISO', function () {
    expect($this->n->fecha('2025-12-25'))->toBe('2025-12-25');
});

test('fecha parsea formato dia-mes-anio con puntos', function () {
    expect($this->n->fecha('25.12.2025'))->toBe('2025-12-25');
});

test('fecha devuelve null para basura', function () {
    expect($this->n->fecha('no-fecha'))->toBeNull();
    expect($this->n->fecha(''))->toBeNull();
});

test('cantidad comparable solo para categorias salientes', function () {
    $cats = ['salida', 'traslado_salida'];

    expect($this->n->cantidadComparable(-100.0, 'salida', $cats))->toBe(100.0);
    expect($this->n->cantidadComparable(100.0, 'salida', $cats))->toBe(100.0);
    expect($this->n->cantidadComparable(50.0, 'traslado_salida', $cats))->toBe(50.0);
    expect($this->n->cantidadComparable(50.0, 'entrada', $cats))->toBe(0.0);
    expect($this->n->cantidadComparable(-50.0, 'anulacion', $cats))->toBe(0.0);
    expect($this->n->cantidadComparable(null, 'salida', $cats))->toBe(0.0);
});

test('uom compatible si coincide o es equivalente', function () {
    $mapa = ['ea' => ['pcs', 'und']];

    expect($this->n->uomCompatible('EA', 'ea', $mapa))->toBeTrue();
    expect($this->n->uomCompatible('ea', 'pcs', $mapa))->toBeTrue();
    expect($this->n->uomCompatible('ea', 'kg', $mapa))->toBeFalse();
    expect($this->n->uomCompatible('', 'kg', $mapa))->toBeTrue();
});

test('nave desde ubicacion D1 y D2', function () {
    $mapa = ['D1' => 'Nave3', 'D2' => 'Nave7'];

    expect($this->n->naveDesdeUbicacion('D1-01', $mapa))->toBe('Nave3');
    expect($this->n->naveDesdeUbicacion('D2-15', $mapa))->toBe('Nave7');
    expect($this->n->naveDesdeUbicacion('X9', $mapa))->toBeNull();
});
