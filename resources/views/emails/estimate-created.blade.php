
<div>
    <p>I hope this email finds you well. We wanted to inform you that a new estimate has been created in the system.</p>
    <p>Estimate Details:</p>
    <p><strong>Name:</strong> {{ $name }}</p>
    <p><strong>Estimate Number:</strong> {{ $quote_number }}</p>
    <p><strong>Date Created:</strong> {{ $created_at }}</p>

    {{-- <p>Estimate Summary</p> --}}
    {{-- @include('emails.products-summary', ['order' => $order, 'role' => $role]) --}}
    <p>Please log in to the system to review the details and take any necessary actions. If you have any questions or concerns regarding this estimate, feel free to contact us or reply to this email.</p>
</div>