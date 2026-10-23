@if(count($filas) === 0)
    <div class="cd-empty">No hay registros incompletos o sospechosos.</div>
@else
<div class="cd-table-wrap">
    <table class="table table-sm mb-0">
        <thead>
            <tr>
                <th>Archivo Origen</th>
                <th>Fila</th>
                <th>Campo Afectado</th>
                <th>Descripción del Problema</th>
                <th>Número de Parte</th>
                <th>Lote</th>
            </tr>
        </thead>
        <tbody class="cd-filter-body">
            @foreach($filas as $f)
                <tr
                    data-material="{{ $f['numero_parte'] }}"
                    data-lote="{{ $f['lote'] }}"
                >
                    <td>
                        <span class="badge {{ $f['origen'] === 'SAP' ? 'bg-info' : 'bg-primary' }}">
                            {{ $f['origen'] }}
                        </span>
                    </td>
                    <td>{{ $f['fila'] }}</td>
                    <td>{{ $f['campo'] }}</td>
                    <td style="white-space:normal;max-width:380px;">{{ $f['problema'] }}</td>
                    <td>{{ $f['numero_parte'] !== '' ? $f['numero_parte'] : '—' }}</td>
                    <td>{{ $f['lote'] !== '' ? $f['lote'] : '—' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
