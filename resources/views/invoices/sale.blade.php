<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura #MDO-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 0.5cm;
            size: letter portrait;
        }
        
        :root {
            --primary-color: #004d99;
            --secondary-color: #f8f9fa;
            --border-color: #dee2e6;
            --text-color: #333;
            --highlight-color: #e3f2fd;
        }
        
        body, h3, .company-name, .invoice-number, .products-table, .footer {
            font-family: 'Roboto', sans-serif;
            color: var(--text-color);
        }
        
        body {
            background-color: #f5f5f5;
            padding: 10px;
            font-size: 10px;
        }
        
        .header {
            display: grid;
            grid-template-columns: 150px 1fr 150px;
            gap: 5px;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 2px solid var(--primary-color);
            align-items: center;
            background-color: var(--highlight-color);
            border-radius: 5px;
            padding: 5px;
        }
        
        .logo {
            max-width: 120px;
            height: auto;
        }
        
        .company-name {
            font-size: 20px;
            font-weight: bold;
            text-align: center;
        }
        
        .invoice-number {
            text-align: right;
            padding: 5px;
            background: var(--secondary-color);
            border-radius: 5px;
            font-size: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .number-highlight {
            color: var(--primary-color);
            font-size: 12px;
            font-weight: bold;
        }

        .client-info, .payment-conditions {
            border: 1px solid var(--border-color);
            padding: 10px;
            border-radius: 5px;
            background: white;
            margin-bottom: 10px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        h3 {
            color: var(--primary-color);
            font-size: 12px;
            margin: 0 0 10px 0;
            border-bottom: 1px solid var(--primary-color);
            padding-bottom: 3px;
        }

        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .products-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 5px;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .products-table td {
            padding: 5px;
            font-size: 10px;
            vertical-align: middle;
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .products-table tr:nth-child(even) {
            background-color: var(--highlight-color);
        }

        .totals-section {
            display: grid;
            grid-template-columns: 1fr 250px;
            margin-top: 10px;
        }

        .payment-info {
            padding: 10px;
            background: var(--secondary-color);
            border-radius: 5px;
            font-size: 10px;
        }

        .total-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px;
            text-align: right;
        }

        .total-label {
            font-weight: bold;
        }

        .grand-total {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid var(--primary-color);
            font-size: 12px;
            color: var(--primary-color);
            font-weight: bold;
        }

        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
            text-align: center;
            font-size: 10px;
            color: #666;
        }

        .footer p {
            margin: 3px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo-section">
            <img src="{{ public_path('images/logo.png') }}" alt="Mundo Digital OP" class="logo">
        </div>
        <div class="company-name">
            Mundo Digital OP
        </div>
        <div class="invoice-number">
            <div style="font-weight: bold; color: var(--primary-color); font-size: 14px;">FACTURA DE VENTA</div>
            <div class="number-highlight" style="font-size: 16px;">No. MDO-{{ str_pad($sale->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div>Fecha: {{ $sale->created_at->format('d/m/Y h:i A') }}</div>
        </div>
    </div>

    <div class="invoice-info">
        <div class="client-info">
            <h3>INFORMACIÓN DEL CLIENTE</h3>
            <div class="info-content">
                <div class="info-row">
                    <div class="info-item">
                        <strong>Nombre:</strong>
                        <span>{{ $client->name }}</span>
                    </div>
                    <div class="info-item">
                        <strong>Identificación:</strong>
                        <span>{{ $client->identification }}</span>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-item">
                        <strong>Dirección:</strong>
                        <span>{{ $client->address ?: 'No especificada' }}</span>
                    </div>
                    <div class="info-item">
                        <strong>Teléfono:</strong>
                        <span>{{ $client->phone }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="payment-conditions info-section">
            <h3>CONDICIONES DE PAGO</h3>
            <div class="info-content">
                <div class="info-row">
                    <strong>Método:</strong>
                    <span>{{ match($sale->payment_type) {
                        'cash' => 'Contado',
                        'credit' => 'Crédito',
                        'card' => 'Tarjeta',
                        default => $sale->payment_type
                    } }}</span>
                </div>
                @if($sale->payment_type === 'credit')
                <div class="info-row">
                    <strong>Cuotas:</strong>
                    <span>{{ $sale->installments }}</span>
                </div>
                <div class="info-row">
                    <strong>Interés:</strong>
                    <span>{{ $sale->interest_rate }}%</span>
                </div>
                <div class="info-row">
                    <strong>Primera Cuota:</strong>
                    <span>{{ Carbon\Carbon::parse($sale->first_payment_date)->format('d/m/Y') }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    <table class="products-table">
        <thead>
            <tr>
                <th>PRODUCTO</th>
                <th>CANT.</th>
                <th>PRECIO UNIT.</th>
                <th>SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details as $detail)
            <tr>
                <td>{{ $detail->product_name }}</td>
                <td>{{ $detail->quantity }}</td>
                <td>$ {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                <td>$ {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals-section">
        <div class="payment-info">
            @if($sale->payment_type === 'credit')
            <div class="info-title">INFORMACIÓN DE CRÉDITO</div>
            <div>Valor de cada cuota: $ {{ number_format($sale->total_amount / $sale->installments, 0, ',', '.') }}</div>
            <div>Primera cuota: {{ Carbon\Carbon::parse($sale->first_payment_date)->format('d/m/Y') }}</div>
            @endif
        </div>
        <div class="total-grid">
            <span class="total-label">Subtotal:</span>
            <span>$ {{ number_format($sale->total_amount, 0, ',', '.') }}</span>
            
            @if($sale->payment_type === 'credit')
            <span class="total-label">Interés ({{ $sale->interest_rate }}%):</span>
            <span>$ {{ number_format(($sale->total_amount * $sale->interest_rate / 100), 0, ',', '.') }}</span>
            
            <span class="total-label">Cuota Inicial:</span>
            <span>$ {{ number_format($sale->initial_payment, 0, ',', '.') }}</span>
            @endif
            
            <div class="grand-total" style="grid-column: span 2">
                TOTAL A PAGAR: $ {{ number_format($sale->total_amount, 0, ',', '.') }}
            </div>
        </div>
    </div>

    <div class="footer">
        <p><strong>¡Gracias por su compra!</strong></p>
        <p>Mundo Digital OP - Todo en Tecnología y Electrodomésticos</p>
    </div>
</body>
</html>
