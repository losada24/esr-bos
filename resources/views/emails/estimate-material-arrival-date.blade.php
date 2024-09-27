<div>
    <p>Good day,</p>
    <p>We expect that your material for order <strong>{{$order->name}}</strong> arrives on <strong>{{$order->delivery_date}}</strong>.</p>
    <p>Please consider this is not a definitive date but estimated, you will receive a call to confirm in advance.</p>
    <p>Please do not reply to this email. This mailbox is not monitored. If you have any questions or need further assistance, please contact us at: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>
    <p>Thank you.</p>
    <hr/>
    <p>Buen día,</p> 
    <p>Esperamos que su material para el pedido <strong>{{$order->name}}</strong> llegue el <strong>{{$order->delivery_date}}</strong>.</p> 
    <p>Por favor, tenga en cuenta que esta no es una fecha definitiva, sino estimada. Recibirá una llamada para confirmar con antelación.</p> 
    <p>Por favor, no responda a este correo electrónico. Esta bandeja de entrada no se monitorea. Si tiene alguna pregunta o necesita más ayuda, contáctenos en: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p> 
    <p>Gracias.</p>
</div>