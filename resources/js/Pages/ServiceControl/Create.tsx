import { useForm } from '@inertiajs/react'
import ServiceControlForm from './ServiceControlForm'
import { type PageProps, type ServiceControlOrderSummary } from '@/types'

type CreateProps = PageProps & {
  order: ServiceControlOrderSummary
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
}

export default function Create ({
  auth,
  flash,
  order,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
}: CreateProps) {
  const { data, setData, post, processing, errors } = useForm({
    order_id: order.id,
    service_name: '',
    service_id: '',
    service_type: '',
    description: '',
    requires_part: false,
    requested_parts: false,
    parts_available: false,
    service_status: 'PENDING',
    priority: 'MEDIUM',
    target_date: '',
    scheduled_date: '',
    executed_date: '',
    closure_result: '',
    observations: '',
  })

  return (
    <ServiceControlForm
      auth={auth}
      flash={flash}
      title="Create Service Control"
      mode="create"
      order={order}
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
        post(route('service-control.store'))
      }}
    />
  )
}
