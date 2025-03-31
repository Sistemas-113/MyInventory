<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Crédito MDO-{{ str_pad($credit->id, 6, '0', STR_PAD_LEFT) }}</title>
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
        
        body, h3, .company-name, .invoice-number, .products-table, .installments-table, .footer {
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

        .client-info, .credit-details {
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

        .products-table, .installments-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            background: white;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .products-table th, .installments-table th {
            background-color: var(--primary-color);
            color: white;
            padding: 5px;
            font-size: 10px;
            text-align: center;
            text-transform: uppercase;
        }

        .products-table td, .installments-table td {
            padding: 5px;
            font-size: 10px;
            vertical-align: middle;
            border: 1px solid var(--border-color);
        }

        .products-table tr:nth-child(even),
        .installments-table tr:nth-child(even) {
            background-color: var(--highlight-color);
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
        
        
        <div class="invoice-number">
        <div class="company-name">
            Mundo Digital OP
        </div>   
        <div class="logo-section">
            <img src="{{ public_path('images/logo.png') }}" alt="Mundo Digital OP" class="logo">
        </div>
            <div style="font-weight: bold; color: var(--primary-color); font-size: 14px;">RECIBO DE CRÉDITO</div>
            <div class="number-highlight" style="font-size: 16px;">No. MDO-{{ str_pad($credit->id, 6, '0', STR_PAD_LEFT) }}</div>
            <div>Fecha: {{ $credit->created_at->format('d/m/Y h:i A') }}</div>
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
            </div>
        </div>

        <div class="credit-details info-section">
            <h3>DETALLES DEL CRÉDITO</h3>
            <div class="info-content">
                <div class="info-row">
                    <div class="info-item">
                        <strong>Monto Total:</strong>
                        <span>$ {{ number_format($credit->total_amount, 0, ',', '.') }}</span>
                    </div>
                    <div class="info-item">
                        <strong>Cuota Inicial:</strong>
                        <span>$ {{ number_format($credit->initial_payment, 0, ',', '.') }}</span>
                    </div>
                </div>
                    <div class="info-item">
                        <strong>No. Cuotas:</strong>
                        <span>{{ $credit->installments }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="tables-container">
        <div class="table-wrapper">
            <h3>PRODUCTOS ADQUIRIDOS</h3>
            <table class="products-table">
                <thead>
                    <tr>
                        <th class="product-name">PRODUCTO</th>
                        <th class="quantity">CANT.</th>
                        <th class="price">PRECIO</th>
                        <th class="subtotal">SUBTOTAL</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($details as $detail)
                    <tr>
                        <td class="product-name">{{ $detail->product_name }}</td>
                        <td class="quantity">{{ $detail->quantity }}</td>
                        <td class="price">$ {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                        <td class="subtotal">$ {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="table-wrapper">
            <h3>PLAN DE PAGOS</h3>
            <table class="installments-table">
                <thead>
                    <tr>
                        <th class="number">NO. CUOTA</th>
                        <th class="amount">MONTO</th>
                        <th class="date">VENCIMIENTO</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($installments as $installment)
                    <tr>
                        <td class="number">{{ $installment->installment_number }}</td>
                        <td class="amount">$ {{ number_format($installment->amount, 0, ',', '.') }}</td>
                        <td class="date">{{ $installment->due_date->format('d/m/Y') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <p><strong>¡Gracias por su compra!</strong></p>
        <p>Mundo Digital OP - Todo en Tecnología y Electrodomésticos</p>
    </div>
</body>
</html>
