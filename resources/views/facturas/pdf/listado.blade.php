<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 6px; }
        .resumen { margin: 0 0 12px; color: #92400e; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 4px 6px; }
        th { background: #0f172a; color: #fff; text-align: left; }
        td.num, th.num { text-align: right; }
        tr:nth-child(even) td { background: #f8fafc; }
        .faltante td { color: #b91c1c; }
    </style>
</head>
<body>
    <h1>{{ $titulo }}</h1>
    @if($resumen)
        <p class="resumen">
            Faltan {{ $resumen['faltantes'] }} de {{ $resumen['facturas'] }} facturas por pago.
            Saldo del mes: ${{ number_format($resumen['saldo'], 0, ',', '.') }}
        </p>
    @endif
    <table>
        <thead>
            <tr>
                <th>Número</th>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Celular</th>
                <th>Proyecto</th>
                <th>Periodo</th>
                <th class="num">Total</th>
                <th class="num">{{ $consulta ? 'Saldo del mes' : 'Saldo' }}</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($facturas as $factura)
                @php
                    $saldo = $consulta
                        ? max(0, (float) $factura->total - (float) $factura->pagado_en_mes)
                        : (float) $factura->saldo;
                @endphp
                <tr class="{{ $saldo > 0 ? 'faltante' : '' }}">
                    <td>{{ $factura->numeroMostrar() }}</td>
                    <td>{{ $factura->cliente->nombre }}</td>
                    <td>{{ $factura->cliente->documento }}</td>
                    <td>{{ $factura->cliente->celular ?: $factura->cliente->telefono }}</td>
                    <td>{{ $factura->cliente->proyecto->nombre ?? 'Sin proyecto' }}</td>
                    <td>{{ $factura->periodo }}</td>
                    <td class="num">${{ number_format($factura->total, 0, ',', '.') }}</td>
                    <td class="num">${{ number_format($saldo, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($factura->estado) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
