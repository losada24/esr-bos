import { useForm } from '@inertiajs/react'
import ServiceControlForm, { type ServiceControlFormData } from './ServiceControlForm'
import { type PageProps, type ServiceControlOrderSummary } from '@/types'

type CreateProps = PageProps & {
  order: ServiceControlOrderSummary
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
  areaOptions: string[]
  bmInvoiceStatusOptions: string[]
  requesterOptions: any[]
  assigneeOptions: any[]
  defaultType?: 'services' | 'bm'
}

export default function Create ({
  auth,
  flash,
  order,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
  areaOptions,
  bmInvoiceStatusOptions,
  requesterOptions,
  assigneeOptions,
  defaultType = 'services',
}: CreateProps) {
  const isStandalone = !order.id
  const isBm = defaultType === 'bm' && !isStandalone
  const { data, setData, post, processing, errors } = useForm<ServiceControlFormData>({
    order_id: order.id ?? null,
    client_id: order.client?.id ?? '',
    new_client: {
      name: '',
      phone: '',
      email: '',
      other_phone: '',
      secondary_email: '',
    },
    service_name: `${order.name ?? 'Order'} ${isBm ? 'BM' : 'Services'}`,
    service_id: '',
    is_bm: isBm,
    service_type: ['OTHER'],
    description: '',
    requires_part: false,
    requested_parts: false,
    parts_available: false,
    service_status: 'PENDING',
    priority: 'MEDIUM',
    cost: '',
    area: '',
    requester_type: '',
    requester_id: '',
    requester_role: '',
    assignee_type: '',
    assignee_id: '',
    assignee_role: '',
    target_date: '',
    service_created_date: order.today_date ?? '',
    service_id_requested_date: '',
    eta_date: '',
    parts_received_date: '',
    part_delivered_date: '',
    scheduled_date: '',
    executed_date: '',
    closure_result: '',
    observations: '',
    bm_quantity: '',
    bm_requested_date: '',
    bm_picked_up_by: '',
    bm_pickup_date: '',
    bm_invoice_number: '',
    bm_invoice_status: 'PENDING',
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
      areaOptions={areaOptions}
      bmInvoiceStatusOptions={bmInvoiceStatusOptions}
      requesterOptions={requesterOptions}
      assigneeOptions={assigneeOptions}
      onSubmit={(event) => {
        event.preventDefault()
        post(route('service-control.store'))
      }}
    />
  )
}
