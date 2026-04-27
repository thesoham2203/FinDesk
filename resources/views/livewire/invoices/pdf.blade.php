<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .invoice-details {
            margin: 20px 0;
        }

        .invoice-details table {
            width: 100%;
        }

        .invoice-details td {
            padding: 5px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .items-table th {
            background-color: #f0f0f0;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }

        .items-table .text-right {
            text-align: right;
        }

        .totals {
            width: 50%;
            margin-left: auto;
            margin: 20px 0;
        }

        .totals table {
            width: 100%;
        }

        .totals td {
            padding: 5px;
        }

        .totals .total-row {
            font-weight: bold;
            font-size: 14px;
            border-top: 2px solid #333;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>INVOICE</h1>
        <p>Invoice #: {{ $invoice->invoice_number }}</p>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <td>
                    <strong>From:</strong><br>
                    {{ config('app.name') }}
                </td>
                <td style="text-align: right;">
                    <strong>Bill To:</strong><br>
                    {{ $invoice->client->name }}<br>
                    {{ $invoice->client->email }}<br>
                    @if($invoice->client->phone)
                        {{ $invoice->client->phone }}<br>
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="invoice-details">
        <table>
            <tr>
                <td><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</td>
                <td style="text-align: right;"><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}
                </td>
            </tr>
            <tr>
                <td><strong>Status:</strong> {{ $invoice->status->label() }}</td>
            </tr>
        </table>
    </div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Tax Rate</th>
                <th class="text-right">Line Total</th>
                <th class="text-right">Tax</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->lineItems as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $invoice->currency->symbol() }}{{ number_format($item->unit_price / 100, 2) }}
                    </td>
                    <td class="text-right">{{ $item->taxRate?->percentage ?? '-' }}%</td>
                    <td class="text-right">{{ $invoice->currency->symbol() }}{{ number_format($item->line_total / 100, 2) }}
                    </td>
                    <td class="text-right">{{ $invoice->currency->symbol() }}{{ number_format($item->tax_amount / 100, 2) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr>
                <td>Subtotal:</td>
                <td class="text-right">{{ $invoice->formatted_subtotal }}</td>
            </tr>
            <tr>
                <td>Tax Total:</td>
                <td class="text-right">{{ $invoice->formatted_tax_total }}</td>
            </tr>
            <tr class="total-row">
                <td>Grand Total:</td>
                <td class="text-right">{{ $invoice->formatted_total }}</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
        <div style="margin-top: 20px; padding: 10px; background-color: #f9f9f9;">
            <strong>Notes:</strong><br>
            {{ $invoice->notes }}
        </div>
    @endif
</body>

</html>