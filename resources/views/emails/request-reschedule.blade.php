@php

    $orderNotes = $order->notes()
        ->with('user')
        ->orderBy('created_at')
        ->get();
@endphp


<p>Hello,</p>

<p>
    An order has been moved to the <strong>REQUEST RE-SCHEDULE</strong> status.
</p>

<p>
      <strong>Order Name:</strong> {{ $order->name ?? 'Not specified' }}
     <strong>Owners:</strong>
      <ul>
        @foreach ($order->owners as $owner)
          <li>{{ $owner->name }}</li>
        @endforeach
      </ul>
    
</p>
@if($orderNotes->isNotEmpty())
    
    <p><strong>Associated Notes:</strong></p>
    <ul>
        @foreach($orderNotes as $note)
            <li>
                @php
                    
                    $author = $note->user?->name;
                    $timestamp = $note->created_at
                        ? \Carbon\Carbon::parse($note->created_at)->format('m/d/Y h:i A')
                        : null;
                @endphp
                <span>{!! nl2br(e($note->content)) !!}</span>
                @if($author || $timestamp)
                    <br>
                    <small>
                        @if($author)
                            By {{ $author }}
                        @endif
                        @if($author && $timestamp)
                            ·
                        @endif
                        @if($timestamp)
                            {{ $timestamp }}
                        @endif
                    </small>
                @endif
            </li>
        @endforeach
    </ul>
@endif

<p>Thank you,<br>{{ config('app.name') }}</p>
