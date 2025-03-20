<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Crédito #{{ $credit->id }}</title>
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
        <h1>Recibo de Crédito</h1>
        <p>No. {{ $credit->id }}</p>
        <p>Fecha: {{ $credit->created_at->format('d/m/Y') }}</p>
    </div>

    <div class="client">
        <h3>Cliente</h3>
        <p>{{ $client->name }}<br>
        {{ $client->identification }}</p>
    </div>

    <div class="details">
        <h3>Detalles del Crédito</h3>
        <table>
            <tr>
                <th>Monto Total</th>
                <td>$ {{ number_format($credit->total_amount, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Cuota Inicial</th>
                <td>$ {{ number_format($credit->initial_payment, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Tasa de Interés</th>
                <td>{{ $credit->interest_rate }}%</td>
            </tr>
            <tr>
                <th>No. Cuotas</th>
                <td>{{ $credit->installments }}</td>
            </tr>
        </table>
    </div>

    <div class="products">
        <h3>Productos</h3>
        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
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
    </div>

    <div class="installments">
        <h3>Plan de Pagos</h3>
        <table>
            <thead>
                <tr>
                    <th>No. Cuota</th>
                    <th>Monto</th>
                    <th>Vencimiento</th>
                </tr>
            </thead>
            <tbody>
                @foreach($installments as $installment)
                <tr>
                    <td>{{ $installment->installment_number }}</td>
                    <td>$ {{ number_format($installment->amount, 0, ',', '.') }}</td>
                    <td>{{ $installment->due_date->format('d/m/Y') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
