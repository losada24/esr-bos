<div>
    <p>Good day,</p>
    <p>We expect that your delivery for order <strong>{{$order->name}}</strong> will be on <strong>{{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y')}}</strong>, and your installation will start on <strong>{{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y')}}   </strong>.</p>
    <p>Please note that these are estimated dates and not definitive. You will receive a call in advance to confirm.</p>
    <p>Please do not reply to this email. This mailbox is not monitored. If you have any questions or need further assistance, please contact us at: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>
    <p>Thank you.</p>
    <hr/>
    <p>Buen día,</p>
    <p>Esperamos que su entrega para la orden <strong>{{$order->name}}</strong> sea el <strong>{{ \Carbon\Carbon::parse($order->delivery_date)->format('m-d-Y')}}</strong>, y que la instalación comience el <strong>{{ \Carbon\Carbon::parse($order->installation_date)->format('m-d-Y')}}</strong>.</p>
    <p>Tenga en cuenta que estas son fechas estimadas y no definitivas. Recibirá una llamada con anticipación para confirmar.</p>
    <p>Por favor, no responda a este correo electrónico. Esta bandeja de entrada no se supervisa. Si tiene alguna pregunta o necesita más ayuda, comuníquese con nosotros a través de: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>
    <p>Gracias.</p>
    </div>