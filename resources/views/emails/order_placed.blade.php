<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation</title>
</head>
<body>
    <h2>Hi {{ $order->user->name }},</h2>
    <p>Thank you for your order! Your order ID is <strong>#{{ $order->id }}</strong>.</p>

    <h3>Order Details:</h3>
    <ul>
        @foreach($order->items as $item)
            <li>
                {{ $item['name'] }} x {{ $item['quantity'] }} = {{ number_format($item['total'], 2) }}
            </li>
        @endforeach
    </ul>

    <p><strong>Total Price:</strong> {{ number_format($order->total_price, 2) }}</p>
    <p>We will notify you once your order is shipped.</p>

    <p>Thanks,<br>AgroApp Team</p>
</body>
</html>
