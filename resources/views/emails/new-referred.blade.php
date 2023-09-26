<div>
    <p>Someone just signed up using a referral link.</p>
    <p><strong>Name</strong> {{ $name }}</p>
    <p><strong>Email</strong> {{ $email }}</p>
    @if (!empty($phone))
      <p><strong>Phone</strong> {{ $phone }}</p>
    @endif
    @if (!empty($notes))
      <p><strong>Notes</strong> {{ $notes }}</p>
    @endif
    <p>Team {{ config('app.name') }}</p>
</div>