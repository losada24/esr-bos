import { useForm } from '@inertiajs/react'
import ServiceControlForm from './ServiceControlForm'
import { type PageProps, type ServiceControl } from '@/types'

type ShowProps = PageProps & {
  serviceControl: ServiceControl
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
}

export default function Show ({
  auth,
  flash,
  serviceControl,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
}: ShowProps) {
  const { data, setData } = useForm({
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
      title="Service Control Details"
      mode="show"
      order={serviceControl.order as any}
      serviceControl={serviceControl}
      data={data}
      setData={setData}
      serviceTypeOptions={serviceTypeOptions}
      serviceStatusOptions={serviceStatusOptions}
      priorityOptions={priorityOptions}
      closureResultOptions={closureResultOptions}
    />
  )
}
