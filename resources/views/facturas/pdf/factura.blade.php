<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $factura->numero }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        .container {
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 3px solid #0ea5e9;
            padding-bottom: 20px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: middle;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            text-align: right;
            vertical-align: middle;
        }
        .logo {
            max-width: 180px;
            height: auto;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #0ea5e9;
        }
        .company-info {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #0ea5e9;
        }
        .invoice-number {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box h3 {
            font-size: 12px;
            color: #0ea5e9;
            text-transform: uppercase;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        .info-box .label {
            color: #666;
            font-size: 10px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .details-table th {
            background-color: #0ea5e9;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }
        .details-table td {
            padding: 12px 10px;
            border-bottom: 1px solid #eee;
        }
        .details-table .text-right {
            text-align: right;
        }
        .totals-section {
            width: 300px;
            margin-left: auto;
        }
        .totals-table {
            width: 100%;
        }
        .totals-table td {
            padding: 8px 10px;
        }
        .totals-table .label {
            text-align: right;
            color: #666;
        }
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        .totals-table .total-row {
            background-color: #0ea5e9;
            color: white;
            font-size: 16px;
        }
        .totals-table .total-row td {
            padding: 12px 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-pendiente { background-color: #ffc107; color: #000; }
        .status-pagada { background-color: #198754; color: #fff; }
        .status-parcial { background-color: #0dcaf0; color: #000; }
        .status-vencida { background-color: #dc3545; color: #fff; }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        .payment-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .payment-info h4 {
            color: #0ea5e9;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">INTERVEREDANET</div>
                <div class="company-info">
                    Servicios de Internet<br>
                    @if($factura->cliente->proyecto)
                        Proyecto: {{ $factura->cliente->proyecto->nombre }}<br>
                    @endif
                </div>
            </div>
            <div class="header-right">
                <div class="invoice-title">FACTURA</div>
                <div class="invoice-number">{{ $factura->numero }}</div>
            </div>
        </div>

        <!-- Info Section -->
        <div class="info-section">
            <div class="info-box">
                <h3>Datos del Cliente</h3>
                <p><strong>{{ $factura->cliente->nombre }}</strong></p>
                <p><span class="label">Código:</span> {{ $factura->cliente->codigo }}</p>
                @if($factura->cliente->documento)
                <p><span class="label">{{ $factura->cliente->tipo_documento }}:</span> {{ $factura->cliente->documento }}</p>
                @endif
                <p><span class="label">Dirección:</span> {{ $factura->cliente->direccion }}</p>
                @if($factura->cliente->celular)
                <p><span class="label">Teléfono:</span> {{ $factura->cliente->celular }}</p>
                @endif
            </div>
            <div class="info-box" style="text-align: right;">
                <h3>Datos de la Factura</h3>
                <p><span class="label">Fecha Emisión:</span> {{ $factura->fecha_emision->format('d/m/Y') }}</p>
                <p><span class="label">Fecha Vencimiento:</span> {{ $factura->fecha_vencimiento->format('d/m/Y') }}</p>
                <p><span class="label">Período:</span> <strong>{{ $factura->periodo }}</strong></p>
                <p style="margin-top: 10px;">
                    <span class="status-badge status-{{ $factura->estado }}">{{ ucfirst($factura->estado) }}</span>
                </p>
            </div>
        </div>

        <!-- Details Table -->
        <table class="details-table">
            <thead>
                <tr>
                    <th style="width: 60%;">Descripción</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Precio Unit.</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong>{{ $factura->concepto ?: 'Servicio de Internet' }}</strong><br>
                        <span style="color: #666; font-size: 11px;">
                            Plan: {{ $factura->servicio->planServicio->nombre ?? 'N/A' }} 
                            ({{ $factura->servicio->planServicio->velocidad_bajada ?? 0 }}/{{ $factura->servicio->planServicio->velocidad_subida ?? 0 }} Mbps)
                        </span>
                    </td>
                    <td class="text-right">1</td>
                    <td class="text-right">${{ number_format($factura->subtotal, 0, ',', '.') }}</td>
                    <td class="text-right">${{ number_format($factura->subtotal, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotal:</td>
                    <td class="value">${{ number_format($factura->subtotal, 0, ',', '.') }}</td>
                </tr>
                @if($factura->descuento > 0)
                <tr>
                    <td class="label">Descuento:</td>
                    <td class="value" style="color: #198754;">-${{ number_format($factura->descuento, 0, ',', '.') }}</td>
                </tr>
                @endif
                @if($factura->recargo > 0)
                <tr>
                    <td class="label">Recargo:</td>
                    <td class="value" style="color: #dc3545;">+${{ number_format($factura->recargo, 0, ',', '.') }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>TOTAL:</td>
                    <td style="text-align: right;">${{ number_format($factura->total, 0, ',', '.') }}</td>
                </tr>
                @if($factura->saldo > 0 && $factura->saldo < $factura->total)
                <tr>
                    <td class="label">Abonado:</td>
                    <td class="value" style="color: #198754;">${{ number_format($factura->total - $factura->saldo, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label"><strong>Saldo Pendiente:</strong></td>
                    <td class="value" style="color: #dc3545;"><strong>${{ number_format($factura->saldo, 0, ',', '.') }}</strong></td>
                </tr>
                @endif
            </table>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <h4>Información de Pago</h4>
            <p>Puede realizar su pago a través de:</p>
            <p>• Efectivo con nuestros cobradores autorizados</p>
            <p>• Transferencia bancaria</p>
            <p>• Nequi / Daviplata</p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>INTERVEREDANET</strong> - Servicios de Internet</p>
            <p>Gracias por su preferencia</p>
            <p style="margin-top: 10px;">Documento generado el {{ now()->format('d/m/Y H:i') }}</p>
        </div>
    </div>
</body>
</html>
