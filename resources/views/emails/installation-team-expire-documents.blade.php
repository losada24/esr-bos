<div>
    <p>Good day:</p>
    <p>This email is a confirmation that you have documents that are about to expire soon.</p> 
    <p>Remember that these documents must be updated before the expiration date. Otherwise, you will not be able to receive payment for your services until they are updated</p>
    <p><span style="font-weight: bold;">Company Name:</span> {{ $installationTeam->company_name}}</p>
    <p><span style="font-weight: bold;">Installer Name:</span> {{ $installationTeam->user->name}}</p>
    @if ($compensation_date)
    <p><span style="font-weight: bold;">Expiration date Worker Compensation:</span> {{ \Carbon\Carbon::parse($installationTeam->worker_compensation_expiration_date)->format('m-d-Y') }}</p>
    @endif
    @if ($liability_date)
    <p><span style="font-weight: bold;">Expiration date Liability:</span> {{ \Carbon\Carbon::parse($installationTeam->liability_expiration_date)->format('m-d-Y')}}</p>
    @endif
    <p>Thank you.</p>
    <hr/>
    <p>Buen día:</p>
    <p>Este correo electrónico es una confirmación de que tienes documentos que están por expirar pronto.</p> 
    <p>Recuerde que estos documentos deben ser actualizados antes de la fecha de vencimiento. De lo contrario, no podrá cobrar sus servicios hasta que se hayan actualizado.</p>
    <p><span style="font-weight: bold;">Nombre de la Compañía:</span> {{ $installationTeam->company_name}}</p>
    <p><span style="font-weight: bold;">Nombre del Instaldor:</span> {{ $installationTeam->user->name}}</p>
    @if ($compensation_date)
    <p><span style="font-weight: bold;">Fecha de expiración Compensación por accidentes de trabajo</span> {{ \Carbon\Carbon::parse($installationTeam->worker_compensation_expiration_date)->format('m-d-Y') }}</p>
     @endif
    @if ($liability_date)
    <p><span style="font-weight: bold;">Fecha de expiración Seguro de responsabilidad civil:</span> {{ \Carbon\Carbon::parse($installationTeam->liability_expiration_date)->format('m-d-Y') }}</p>
    @endif
    <p>Thank you.</p>
</div>