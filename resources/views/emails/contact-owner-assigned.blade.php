@php
    $owner = $client->user;
    $creator = $client->createdByUser;
    $companies = $client->companyContacts;

    if ($companies->isEmpty() && $client->companyContact) {
        $companies = collect([$client->companyContact]);
    }

    $referral = $client->referral;
    $referrer = $referral?->referrerClient ?? $referral?->referrerUser;
    $addresses = $client->clientAddress;
    $primaryAddress = trim((string) ($client->address ?? ''));
@endphp

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact assigned</title>
</head>
<body style="margin:0;padding:0;background:#f2f8fc;font-family:Arial,Helvetica,sans-serif;color:#243247;">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f8fc;padding:28px 12px;">
    <tr>
        <td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border-radius:14px;overflow:hidden;box-shadow:0 8px 28px rgba(57,123,165,.12);">
                <tr>
                    <td style="padding:30px 34px;background:#4f9bc7;color:#ffffff;">
                        <div style="font-size:12px;letter-spacing:1.4px;text-transform:uppercase;opacity:.78;">{{ config('app.name') }}</div>
                        <div style="font-size:27px;font-weight:700;margin-top:8px;">
                            {{ $isNewContact ? 'New contact assigned' : 'Contact owner updated' }}
                        </div>
                        <div style="font-size:15px;margin-top:8px;opacity:.88;">
                            {{ $client->name ?: ('Contact #' . $client->id) }}
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="padding:28px 34px 10px;">
                        <p style="margin:0 0 12px;font-size:16px;">Hello {{ $recipientName ?: 'there' }},</p>
                        <p style="margin:0;line-height:1.6;color:#526174;">
                            {{ $isNewContact ? 'A new contact has been created and assigned.' : 'The assigned owner for this contact has changed.' }}
                            The complete contact information is included below.
                        </p>
                    </td>
                </tr>

                @if($client->vip_clients)
                    <tr>
                        <td style="padding:16px 34px 0;">
                            <div style="padding:14px 16px;background:#fff7df;border:1px solid #f0d37a;border-radius:9px;color:#765b0b;">
                                <strong>VIP contact</strong>
                                @if(trim((string) $client->vip_notes) !== '')
                                    <div style="margin-top:6px;line-height:1.5;">{{ $client->vip_notes }}</div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endif

                <tr>
                    <td style="padding:24px 34px 8px;">
                        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#397fa8;border-bottom:2px solid #dceef8;padding-bottom:8px;">Contact information</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:4px 34px 12px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.5;">
                            <tr>
                                <td width="50%" valign="top" style="padding:9px 12px 9px 0;"><strong>Name</strong><br><span style="color:#526174;">{{ $client->name ?: 'Not specified' }}</span></td>
                                <td width="50%" valign="top" style="padding:9px 0 9px 12px;"><strong>Contact ID</strong><br><span style="color:#526174;">#{{ $client->id }}</span></td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding:9px 12px 9px 0;"><strong>Primary phone</strong><br><span style="color:#526174;">{{ $client->phone ?: 'Not specified' }}</span></td>
                                <td valign="top" style="padding:9px 0 9px 12px;"><strong>Other phone</strong><br><span style="color:#526174;">{{ $client->other_phone ?: 'Not specified' }}</span></td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding:9px 12px 9px 0;"><strong>Primary email</strong><br><span style="color:#526174;">{{ $client->email ?: 'Not specified' }}</span></td>
                                <td valign="top" style="padding:9px 0 9px 12px;"><strong>Secondary email</strong><br><span style="color:#526174;">{{ $client->secondary_email ?: 'Not specified' }}</span></td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding:9px 12px 9px 0;"><strong>Contact type</strong><br><span style="color:#526174;">{{ $client->contact_type ?: 'Not specified' }}</span></td>
                                <td valign="top" style="padding:9px 0 9px 12px;"><strong>Source</strong><br><span style="color:#526174;">{{ $client->source ?: 'Not specified' }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:12px 34px 8px;">
                        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#397fa8;border-bottom:2px solid #dceef8;padding-bottom:8px;">Assignment and origin</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:4px 34px 12px;">
                        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;line-height:1.5;">
                            <tr>
                                <td width="50%" valign="top" style="padding:9px 12px 9px 0;"><strong>Assigned owner</strong><br><span style="color:#526174;">{{ $owner?->name ?: 'Not assigned' }}{{ $owner?->email ? ' · ' . $owner->email : '' }}</span></td>
                                <td width="50%" valign="top" style="padding:9px 0 9px 12px;"><strong>Created by</strong><br><span style="color:#526174;">{{ $creator?->name ?: 'Not specified' }}{{ $creator?->email ? ' · ' . $creator->email : '' }}</span></td>
                            </tr>
                            <tr>
                                <td valign="top" style="padding:9px 12px 9px 0;"><strong>Created</strong><br><span style="color:#526174;">{{ optional($client->getRawOriginal('created_at') ? \Carbon\Carbon::parse($client->getRawOriginal('created_at')) : null)?->format('M d, Y h:i A') ?: 'Not specified' }}</span></td>
                                <td valign="top" style="padding:9px 0 9px 12px;"><strong>Last updated</strong><br><span style="color:#526174;">{{ optional($client->getRawOriginal('updated_at') ? \Carbon\Carbon::parse($client->getRawOriginal('updated_at')) : null)?->format('M d, Y h:i A') ?: 'Not specified' }}</span></td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding:12px 34px 8px;">
                        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#397fa8;border-bottom:2px solid #dceef8;padding-bottom:8px;">Address and companies</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 34px 12px;font-size:14px;line-height:1.6;color:#526174;">
                        <strong style="color:#243247;">Primary address</strong><br>
                        {{ $primaryAddress !== '' ? $primaryAddress : 'Not specified' }}

                        @if($addresses->isNotEmpty())
                            <div style="margin-top:14px;"><strong style="color:#243247;">Saved addresses</strong></div>
                            @foreach($addresses as $address)
                                <div style="padding-top:5px;">
                                    {{ $address->address ?: 'Address not specified' }}
                                    @if($address->appointment_date)
                                        · Appointment: {{ \Carbon\Carbon::parse($address->appointment_date)->format('M d, Y h:i A') }}
                                    @endif
                                    @if($address->notes)
                                        <br><span style="font-size:13px;">Notes: {{ $address->notes }}</span>
                                    @endif
                                </div>
                            @endforeach
                        @endif

                        <div style="margin-top:14px;"><strong style="color:#243247;">Associated companies</strong></div>
                        @forelse($companies as $company)
                            <div style="padding-top:5px;">
                                {{ $company->name ?: 'Unnamed company' }}
                                @if($company->phone) · {{ $company->phone }} @endif
                                @if($company->email) · {{ $company->email }} @endif
                            </div>
                        @empty
                            <div style="padding-top:5px;">Not specified</div>
                        @endforelse
                    </td>
                </tr>

                <tr>
                    <td style="padding:12px 34px 8px;">
                        <div style="font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#397fa8;border-bottom:2px solid #dceef8;padding-bottom:8px;">Referral details</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:10px 34px 18px;font-size:14px;line-height:1.6;color:#526174;">
                        @if($referral)
                            <strong style="color:#243247;">Type:</strong> {{ $referral->type ?: 'Not specified' }}<br>
                            <strong style="color:#243247;">Name:</strong> {{ $referrer?->name ?: $referral->name ?: 'Not specified' }}<br>
                            <strong style="color:#243247;">Phone:</strong> {{ $referrer?->phone ?: $referral->phone ?: 'Not specified' }}<br>
                            <strong style="color:#243247;">Email:</strong> {{ $referrer?->email ?: $referral->email ?: 'Not specified' }}
                        @else
                            Not specified
                        @endif
                    </td>
                </tr>

                <tr>
                    <td align="center" style="padding:10px 34px 30px;">
                        <a href="{{ $contactUrl }}" style="display:inline-block;padding:13px 24px;background:#4f9bc7;color:#ffffff;text-decoration:none;border-radius:8px;font-size:14px;font-weight:700;">Open contact</a>
                    </td>
                </tr>
                <tr>
                    <td style="padding:18px 34px;background:#e9f4fa;color:#667487;font-size:12px;line-height:1.5;text-align:center;">
                        This notification was generated automatically by {{ config('app.name') }}.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
