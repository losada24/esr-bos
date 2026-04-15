import { useForm } from '@inertiajs/react'
import ServiceControlForm from './ServiceControlForm'
import { type PageProps, type ServiceControl } from '@/types'

type EditProps = PageProps & {
  serviceControl: ServiceControl
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
}

export default function Edit ({
  auth,
  flash,
  serviceControl,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
}: EditProps) {
  const { data, setData, put, processing, errors } = useForm({
    order_id: Number(serviceControl.order?.id ?? 0),
    service_name: serviceControl.service_name ?? '',
    service_id: serviceControl.service_id ?? '',
    service_type: serviceControl.service_type ?? '',
    description: serviceControl.description ?? '',
    requires_part: Boolean(serviceControl.requires_part),
    requested_parts: Boolean(serviceControl.requested_parts),
    parts_available: Boolean(serviceControl.parts_available),
    service_status: serviceControl.service_status ?? '',
    priority: serviceControl.priority ?? '',
    target_date: serviceControl.target_date ?? '',
    scheduled_date: serviceControl.scheduled_date ?? '',
    executed_date: serviceControl.executed_date ?? '',
    closure_result: serviceControl.closure_result ?? '',
    observations: serviceControl.observations ?? '',
  })

  return (
    <ServiceControlForm
      auth={auth}
      flash={flash}
      title="Edit Service Control"
      mode="edit"
      order={serviceControl.order as any}
      serviceControl={serviceControl}
      data={data}
      setData={setData}
      processing={processing}
      errors={errors}
      serviceTypeOptions={serviceTypeOptions}
      serviceStatusOptions={serviceStatusOptions}
      priorityOptions={priorityOptions}
      closureResultOptions={closureResultOptions}
      onSubmit={(event) => {
        event.preventDefault()
        put(route('service-control.update', serviceControl.id))
      }}
    />
  )
}
