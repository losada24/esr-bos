<div>
<p> Good day:</p>
    <p>Your delivery is confirmed for {{$order->delivery_date}} and your installation will start on {{$order->installation_date}}.  Please remember to remove small objects from the installation area as well as curtains so that the installation flows more quickly and smoothly.</p>
    <p>You will receive a copy of the Permit Card to keep on hand/visible at the property. The rest of the permit documents will be given to you by the project supervisor. You will receive a small sign with the company details to put on during the work prior your authorization.</p>
    <p>The balance due on the delivery day  is: {{$order->cost_delivery}}. You can make the payment by handing over a check, making a Zelle to Reylos Glass Inc. (702 525 1698), by wire transfer or paying with a card through the office. See attached form.</p>
    <p>Please do not reply to this email. This mailbox is not monitored. If you have any questions or need further assistance, please contact us at  786 732 0362 Ext 104 :  <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>
    <hr/>
    <p>Buen día:</p>
    <p>Su entrega está confirmado para:{{$order->delivery_date}} y su instalación para:{{$order->installation_date}} . Por favor recuerde remover los pequeños objetos del área de instalación así como las cortinas en modo que el trabajo fluya más rápida y tranquilamente.</p>
    <p>Usted va a recibir una copia del “Permit card” para que tenga disponible/ visible en la propiedad. El resto de los documentos del permiso le serán entregados por el supervisor del proyecto. Se va a llevar un pequeño Sing con los datos de la compañía para poner durante el trabajo previa autorización.</p>
    <p>El monto a pagar el día de la entrega  es de: {{$order->cost_delivery}} . Puede realizar el pago entregando un cheque, realizando un Zelle a Reylos Glass Inc. (702 525 1698), por wire transfer o pagando con tarjeta. Ver formulario adjuntos.   </p>
    <p>Por favor, no responda a este correo electrónico. Esta bandeja de entrada no se supervisa. Si tiene alguna pregunta o necesita alguna información, comuníquese con el área de operaciones a través de 786 732 0362 Ext 104: <a href="mailto:{{$order->user->email}}">{{$order->user->email}}</a>.</p>

    <p>Thank you.</p>
</div>