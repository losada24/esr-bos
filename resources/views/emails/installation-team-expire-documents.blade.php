<div>
    <p>Good day,</p>
    <p>This email is a confirmation that you have documents expirin soon.</p>
    <p style="font-weight: bold;">{{$installationTeam->user->name}}</p>
    <p><span style="font-weight: bold;">Worker Compensation:</span> {{ $installationTeam->worker_compensation_expiration_date }}</p>
    <p><span style="font-weight: bold;">Liability:</span> {{ $installationTeam->liability_expiration_date }}</p>
    <p>Thank you.</p>
</div>