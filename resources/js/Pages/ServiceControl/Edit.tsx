import { router, useForm } from '@inertiajs/react'
import ServiceControlForm, { type ServiceControlFormData } from './ServiceControlForm'
import { type PageProps, type ServiceControl } from '@/types'

type EditProps = PageProps & {
  serviceControl: ServiceControl
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
  areaOptions: string[]
  bmInvoiceStatusOptions: string[]
  requesterOptions: any[]
  assigneeOptions: any[]
}

export default function Edit ({
  auth,
  flash,
  serviceControl,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
  areaOptions,
  bmInvoiceStatusOptions,
  requesterOptions,
  assigneeOptions,
}: EditProps) {
  const { data, setData, put, processing, errors } = useForm<ServiceControlFormData>({
    order_id: serviceControl.order?.id ?? null,
    client_id: serviceControl.client_id ?? serviceControl.client?.id ?? '',
    new_client: {
      name: '',
      phone: '',
      email: '',
      other_phone: '',
      secondary_email: '',
    },
    service_name: serviceControl.service_name ?? '',
    service_id: serviceControl.service_id ?? '',
    is_bm: Boolean(serviceControl.is_bm),
    service_type: serviceControl.service_type ?? '',
    description: serviceControl.description ?? '',
    requires_part: Boolean(serviceControl.requires_part),
    requested_parts: Boolean(serviceControl.requested_parts),
    parts_available: Boolean(serviceControl.parts_available),
    service_status: serviceControl.service_status ?? '',
    priority: serviceControl.priority ?? '',
    cost: serviceControl.cost ?? '',
    area: serviceControl.area ?? '',
    requester_type: serviceControl.requester_type ?? '',
    requester_id: serviceControl.requester_id ?? '',
    requester_role: serviceControl.requester_role ?? '',
    assignee_type: serviceControl.assignee_type ?? '',
    assignee_id: serviceControl.assignee_id ?? '',
    assignee_role: serviceControl.assignee_role ?? '',
    target_date: serviceControl.target_date ?? '',
    service_created_date: serviceControl.service_created_date ?? '',
    service_id_requested_date: serviceControl.service_id_requested_date ?? '',
    eta_date: serviceControl.eta_date ?? '',
    parts_received_date: serviceControl.parts_received_date ?? '',
    part_delivered_date: serviceControl.part_delivered_date ?? '',
    scheduled_date: serviceControl.scheduled_date ?? '',
    executed_date: serviceControl.executed_date ?? '',
    closure_result: serviceControl.closure_result ?? '',
    observations: serviceControl.observations ?? '',
    bm_quantity: serviceControl.bm_quantity ?? '',
    bm_requested_date: serviceControl.bm_requested_date ?? '',
    bm_picked_up_by: serviceControl.bm_picked_up_by ?? '',
    bm_pickup_date: serviceControl.bm_pickup_date ?? '',
    bm_invoice_number: serviceControl.bm_invoice_number ?? '',
    bm_invoice_status: serviceControl.bm_invoice_status ?? 'PENDING',
  })

  const destroy = () => {
    if (!window.confirm('Are you sure you want to delete this service control?')) return

    router.delete(route('service-control.destroy', serviceControl.id), {
      preserveScroll: true,
    })
  }

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
      areaOptions={areaOptions}
      bmInvoiceStatusOptions={bmInvoiceStatusOptions}
      requesterOptions={requesterOptions}
      assigneeOptions={assigneeOptions}
      onDelete={destroy}
      onSubmit={(event) => {
        event.preventDefault()
        put(route('service-control.update', serviceControl.id))
      }}
    />
  )
}
