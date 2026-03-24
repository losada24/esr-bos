<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authorize.Net Payment</title>
</head>
<body>
    <p>Redirecting to Authorize.Net payment form...</p>

    <form id="authorize-net-payment-form" method="post" action="{{ $formUrl }}">
        <input type="hidden" name="token" value="{{ $token }}">
        <noscript>
            <button type="submit">Continue to payment</button>
        </noscript>
    </form>

    <script>
        document.getElementById('authorize-net-payment-form').submit();
    </script>
</body>
</html>
