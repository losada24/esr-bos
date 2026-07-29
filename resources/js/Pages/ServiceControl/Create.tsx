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
  defaultServiceSource?: 'ESR' | 'ESW'
  externalDefaults?: Partial<ServiceControlFormData>
  pageTitle?: string
  submitRouteName?: string
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
  defaultServiceSource = 'ESR',
  externalDefaults = {},
  pageTitle = 'Create Service Control',
  submitRouteName = 'service-control.store',
}: CreateProps) {
  const isStandalone = !order.id
  const isBm = defaultType === 'bm' && !isStandalone
  const newClientDefaults = order.client?.id ? {} : (externalDefaults.new_client ?? {})
  const { data, setData, post, processing, errors } = useForm<ServiceControlFormData>({
    order_id: order.id ?? null,
    client_id: order.client?.id ?? '',
    new_client: {
      name: '',
      phone: '',
      email: '',
      other_phone: '',
      secondary_email: '',
      ...newClientDefaults,
    },
    service_name: externalDefaults.service_name ?? (order.name ?? ''),
    service_id: externalDefaults.service_id ?? '',
    external_order_id: externalDefaults.external_order_id ?? '',
    is_bm: isBm,
    service_source: externalDefaults.service_source ?? defaultServiceSource,
    creation_source: externalDefaults.creation_source ?? 'MANUAL',
    request_origin: externalDefaults.request_origin ?? 'SERVICE',
    service_type: ['GLASS'],
    description: externalDefaults.description ?? '',
    requires_part: false,
    requested_parts: false,
    parts_available: false,
    service_status: 'Order In Review',
    priority: 'MEDIUM',
    cost: externalDefaults.cost ?? '',
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
    external_company_contact_id: externalDefaults.external_company_contact_id ?? '',
    external_owner_id: externalDefaults.external_owner_id ?? '',
    external_owner_name: externalDefaults.external_owner_name ?? '',
    external_owner_email: externalDefaults.external_owner_email ?? '',
    attachments: [],
  })

  return (
    <ServiceControlForm
      auth={auth}
      flash={flash}
      title={pageTitle}
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
        post(route(submitRouteName), { forceFormData: true })
      }}
    />
  )
}
