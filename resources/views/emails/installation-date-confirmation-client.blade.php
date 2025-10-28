<div>
    <p>Good day, <strong>{{ $order->client->name ?? 'Valued Customer' }}</strong>:</p>
    @if($order->status === \App\Enum\OrderStatusEnum::RESCHEDULE->value)
        <p>Your installation for {{$order->name}} was rescheduled for {{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y') }}.</p>
    @else
        <p>Your delivery for {{$order->name}} is confirmed for {{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y') }} and your installation will start on {{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y') }}. Please remember to remove small objects from the installation area as well as curtains so that the installation flows more quickly and smoothly.If your products include doors, please have the lock you wish to install on hand so it can be installed at the time of installation. Otherwise, the one currently in use will be used.
In the case of sliding glass doors, the product comes with the lock.</p>
    @endif
    <p>Please do not reply to this email. This mailbox is not monitored. If you have any questions or need further assistance, please contact us at 786 732 0362 Ext 104: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>
    <hr/>
    <p>Buen día, <strong>{{ $order->client->name ?? 'Estimado Cliente' }}</strong>:</p>
    @if($order->status === \App\Enum\OrderStatusEnum::RESCHEDULE->value)
        <p>La instalación de su orden {{$order->name}} fue reprogramada para: {{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y') }}.</p>
    @else
        <p>La entrega de su orden {{$order->name}} está confirmada para: {{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y') }} y su instalación para: {{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y') }}. Por favor recuerde remover los pequeños objetos del área de instalación así como las cortinas en modo que el trabajo fluya más rápida y tranquilamente.Si entre sus productos hay puertas tenga a mano la cerradura que desea instalar para que se le pueda colocar al momento de la instalación. De lo contrario se usará la que tenga actualmente. En el caso de las puertas de corredera o sliding Glass nuestro producto viene ya con la cerradura.  </p>
    @endif
    <p>Por favor, no responda a este correo electrónico. Esta bandeja de entrada no se supervisa. Si tiene alguna pregunta o necesita alguna información, comuníquese con el área de operaciones a través de 786 732 0362 Ext 104: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>

    <p>Thank you.</p>
</div>