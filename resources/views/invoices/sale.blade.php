<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura #{{ $sale->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; }
        .header { text-align: center; margin-bottom: 30px; }
        .details { margin: 20px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .totals { margin-top: 20px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Factura de Venta</h1>
        <p>No. {{ $sale->id }}</p>
        <p>Fecha: {{ $sale->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="client-info">
        <h3>Cliente</h3>
        <p>
            <strong>Nombre:</strong> {{ $client->name }}<br>
            <strong>Identificación:</strong> {{ $client->identification }}<br>
            <strong>Dirección:</strong> {{ $client->address }}<br>
            <strong>Teléfono:</strong> {{ $client->phone }}
        </p>
    </div>

    <div class="products">
        <h3>Productos</h3>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Proveedor</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($details as $detail)
                <tr>
                    <td>{{ $detail->product_name }}</td>
                    <td>{{ $detail->provider->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>$ {{ number_format($detail->unit_price, 0, ',', '.') }}</td>
                    <td>$ {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="totals">
        <p>
            <strong>Total:</strong> $ {{ number_format($sale->total_amount, 0, ',', '.') }}
        </p>
        <p>
            <strong>Método de Pago:</strong> 
            {{ $sale->payment_type === 'cash' ? 'Contado' : 'Crédito' }}
        </p>
    </div>
</body>
</html>
