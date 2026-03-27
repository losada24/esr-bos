@php
    $appointmentStart = $order->schedule_appointment
        ? \Carbon\Carbon::parse($order->schedule_appointment, config('app.timezone'))
        : null;

    $appointmentDateEn = $appointmentStart?->copy()->format('F j, Y');
    $appointmentTimeEn = $appointmentStart?->copy()->format('g:i A');
    $appointmentDateEs = $appointmentStart?->copy()->locale('es')->translatedFormat('j \d\e F \d\e Y');
    $appointmentTimeEs = $appointmentStart?->copy()->format('g:i A');

    $clientName = trim((string) ($order->client?->name ?? 'Valued Client'));
@endphp

<p>Hello {{ $clientName }},</p>

<p>
    We’re pleased to confirm your upcoming consultation with Reylos Glass.
</p>

<p><strong>Appointment Details:</strong></p>
<p>
    Date: <strong>{{ $appointmentDateEn }}</strong><br>
    Time: <strong>{{ $appointmentTimeEn }}</strong>
</p>

<p>
    During your consultation, we’ll take the time to understand your needs, evaluate your property, and provide expert recommendations for hurricane impact windows and/or doors, tailored specifically to your home and budget.
</p>

<p>
    Our goal is to give you clarity, confidence, and the best possible solution for your project.
</p>

<p>
    If you need to make any changes or have questions prior to your appointment, please don’t hesitate to reach out.
</p>

<p>
    Thank you again for the opportunity to earn your business. We look forward to meeting you.
</p>

<p>Warm regards,<br>Reylos Glass</p>

<hr style="margin: 24px 0; border: 0; border-top: 1px solid #d1d5db;">

<p>Hola {{ $clientName }},</p>

<p>
    Nos complace confirmar su próxima consulta con Reylos Glass.
</p>

<p><strong>Detalles de la cita:</strong></p>
<p>
    Fecha: <strong>{{ $appointmentDateEs }}</strong><br>
    Hora: <strong>{{ $appointmentTimeEs }}</strong>
</p>

<p>
    Durante su consulta, tomaremos el tiempo para entender sus necesidades, evaluar su propiedad y brindarle recomendaciones expertas para ventanas y/o puertas de impacto contra huracanes, adaptadas específicamente a su hogar y presupuesto.
</p>

<p>
    Nuestro objetivo es ofrecerle claridad, confianza y la mejor solución para su proyecto.
</p>

<p>
    Si necesita hacer algún cambio o tiene preguntas antes de su cita, no dude en comunicarse con nosotros.
</p>

<p>
    Gracias nuevamente por la oportunidad de servirle. Esperamos verle pronto.
</p>

<p>Saludos cordiales,<br>Reylos Glass</p>
