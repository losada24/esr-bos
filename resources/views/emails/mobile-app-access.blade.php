<div>
    <p>Hi {{ $name }},</p>
    <p>Your mobile app access has been created. Use the credentials below to sign in:</p>
    <p><strong>Email:</strong> {{ $email }}</p>
    <p><strong>Password:</strong> {{ $password }}</p>
    <p>Please change your password after your first login.</p>
    @if (!empty($appStoreUrl) || !empty($playStoreUrl))
        <p>Download the app:</p>
        <ul>
            @if (!empty($appStoreUrl))
                <li><a href="{{ $appStoreUrl }}">App Store</a></li>
            @endif
            @if (!empty($playStoreUrl))
                <li><a href="{{ $playStoreUrl }}">Google Play</a></li>
            @endif
        </ul>
    @endif
    <p>If you need help, please contact our support team.</p>
</div>
