<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt | {{ $order->code ?? 'Order' }} | Acie Fraiche</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            color: #0f0b05;
            padding: 20px;
        }
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #0f0b05;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .receipt-header h1 {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 1px;
            margin-bottom: 4px;
        }
        .receipt-header p {
            font-size: 11px;
            color: rgba(0,0,0,0.6);
            margin: 2px 0;
        }
        .order-meta {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 12px;
            padding: 8px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.2);
        }
        .order-meta-col {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .order-meta-label {
            color: rgba(0,0,0,0.6);
            font-size: 11px;
        }
        .order-meta-value {
            font-weight: 700;
            font-size: 13px;
        }
        .items-section {
            margin-bottom: 12px;
            border-bottom: 1px dashed rgba(0,0,0,0.2);
            padding-bottom: 12px;
        }
        .item-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 8px;
            gap: 8px;
        }
        .item-name {
            flex: 1;
            font-weight: 600;
        }
        .item-qty {
            text-align: center;
            min-width: 30px;
            color: rgba(0,0,0,0.7);
        }
        .item-price {
            text-align: right;
            min-width: 50px;
            font-weight: 600;
        }
        .totals-section {
            border-bottom: 2px dashed #0f0b05;
            padding-bottom: 12px;
            margin-bottom: 12px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 6px;
        }
        .total-row.final {
            font-size: 14px;
            font-weight: 700;
            margin-top: 8px;
        }
        .payment-method {
            text-align: center;
            font-size: 11px;
            color: rgba(0,0,0,0.6);
            margin-bottom: 12px;
        }
        .receipt-footer {
            text-align: center;
            font-size: 11px;
            color: rgba(0,0,0,0.6);
            padding-top: 12px;
            border-top: 1px dashed rgba(0,0,0,0.2);
        }
        .customer-section {
            background: rgba(82, 55, 0, 0.05);
            border: 1px solid rgba(82, 55, 0, 0.1);
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 12px;
        }
        .customer-section strong {
            display: block;
            margin-bottom: 4px;
            color: #523700;
        }
        .kitchen-note {
            background: #fff7ed;
            border: 1px dashed #fed7aa;
            border-radius: 6px;
            padding: 10px;
            margin-bottom: 12px;
            font-size: 11px;
            color: #9a3412;
        }
        .kitchen-note strong {
            display: block;
            margin-bottom: 4px;
            color: #92400e;
        }
        .actions {
            text-align: center;
            padding-top: 12px;
            border-top: 1px dashed rgba(0,0,0,0.2);
        }
        button {
            background: #523700;
            color: white;
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 700;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s;
            margin: 0 6px;
        }
        button:hover {
            background: #3d2700;
        }
        button.secondary {
            background: #fff;
            color: #523700;
            border: 2px solid #523700;
        }
        button.secondary:hover {
            background: #f5f5f5;
        }
        @media print {
            body {
                background: white;
                padding: 0;
            }
            .receipt-container {
                max-width: 100%;
                box-shadow: none;
                border-radius: 0;
            }
            .actions {
                display: none;
            }
            * {
                box-shadow: none !important;
                border-color: #0f0b05 !important;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="receipt-header">
            <h1>ACIE FRAICHE</h1>
            <p>Restaurant · Kitchen · Delivery</p>
        </div>

        <div class="order-meta">
            <div class="order-meta-col">
                <div class="order-meta-label">Order Code</div>
                <div class="order-meta-value">{{ $order->code ?? 'N/A' }}</div>
            </div>
            <div class="order-meta-col">
                <div class="order-meta-label">Channel</div>
                <div class="order-meta-value">{{ strtoupper($order->channel ?? 'WEB') }}</div>
            </div>
            <div class="order-meta-col">
                <div class="order-meta-label">Time</div>
                <div class="order-meta-value">{{ $order->created_at?->format('H:i') ?? 'N/A' }}</div>
            </div>
        </div>

        @if ($order->customer_name || $order->customer_phone)
        <div class="customer-section">
            <strong>Customer</strong>
            {{ $order->customer_name ?? 'Walk-in' }}
            @if ($order->customer_phone)
            <div>{{ $order->customer_phone }}</div>
            @endif
        </div>
        @endif

        @if ($order->kitchen_note)
        <div class="kitchen-note">
            <strong>Kitchen Note</strong>
            {{ $order->kitchen_note }}
        </div>
        @endif

        <div class="items-section">
            @forelse ($order->items ?? [] as $item)
            <div class="item-row">
                <div class="item-name">{{ $item->name ?? 'Item' }}</div>
                <div class="item-qty">{{ $item->quantity ?? 1 }}</div>
                <div class="item-price">₦{{ number_format($item->total ?? 0, 2) }}</div>
            </div>
            @empty
            <div style="text-align: center; color: rgba(0,0,0,0.5); font-size: 12px; padding: 8px;">
                No items
            </div>
            @endforelse
        </div>

        <div class="totals-section">
            <div class="total-row">
                <span>Subtotal</span>
                <span>₦{{ number_format($order->subtotal ?? 0, 2) }}</span>
            </div>
            @if ($order->discount && $order->discount > 0)
            <div class="total-row">
                <span>Discount</span>
                <span>-₦{{ number_format($order->discount, 2) }}</span>
            </div>
            @endif
            @if ($order->tax && $order->tax > 0)
            <div class="total-row">
                <span>Tax</span>
                <span>₦{{ number_format($order->tax, 2) }}</span>
            </div>
            @endif
            <div class="total-row final">
                <span>Total</span>
                <span>₦{{ number_format($order->total ?? 0, 2) }}</span>
            </div>
        </div>

        @if ($order->payments && $order->payments->count() > 0)
        @php
            $payment = $order->payments->first();
        @endphp
        <div class="payment-method">
            <strong>Paid via {{ ucfirst($payment->method ?? 'Cash') }}</strong>
            <div style="font-size: 10px; margin-top: 4px;">
                {{ $payment->paid_at?->format('M d, Y H:i') ?? 'N/A' }}
            </div>
        </div>
        @endif

        <div class="receipt-footer">
            <p>Thank you for your order!</p>
            <p style="margin-top: 4px;">Printed: {{ now()->format('M d, Y H:i:s') }}</p>
        </div>

        <div class="actions">
            <button type="button" onclick="window.print()">Print Receipt</button>
            <button type="button" class="secondary" onclick="window.close()">Close</button>
        </div>
    </div>

    <script>
        // Auto-print on load (optional)
        // Uncomment to auto-print when page loads
        // window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
