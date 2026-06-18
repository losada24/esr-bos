import { type FormEvent, useEffect, useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import Select from 'react-select'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import HistoryTimeline from './HistoryTimeline'
import { type PageProps, type ServiceControl, type ServiceControlOrderSummary } from '@/types'

type PartyOption = {
  value: string
  label: string
  type: 'user' | 'client'
  id: number
  role: string
}

type ClientOption = {
  id: number
  name?: string | null
  phone?: string | null
  email?: string | null
  other_phone?: string | null
  secondary_email?: string | null
  label: string
}

type SelectOption = {
  value: string
  label: string
}

export type ServiceControlFormData = {
  order_id: number | null
  client_id: number | string | null
  new_client: {
    name: string
    phone: string
    email: string
    other_phone: string
    secondary_email: string
  }
  service_name: string
  service_id: string
  is_bm: boolean
  service_type: string[]
  description: string
  requires_part: boolean
  requested_parts: boolean
  parts_available: boolean
  service_status: string
  priority: string
  cost: string | number
  area: string
  requester_type: string
  requester_id: string | number
  requester_role: string
  assignee_type: string
  assignee_id: string | number
  assignee_role: string
  target_date: string
  service_created_date: string
  service_id_requested_date: string
  eta_date: string
  parts_received_date: string
  part_delivered_date: string
  scheduled_date: string
  executed_date: string
  closure_result: string
  observations: string
  bm_quantity: string | number
  bm_requested_date: string
  bm_picked_up_by: string
  bm_pickup_date: string
  bm_invoice_number: string
  bm_invoice_status: string
}

type Props = PageProps & {
  title: string
  mode: 'create' | 'edit' | 'show'
  order: ServiceControlOrderSummary
  serviceControl?: ServiceControl | null
  data: ServiceControlFormData
  setData: ((data: ServiceControlFormData) => void)
    & ((callback: (data: ServiceControlFormData) => ServiceControlFormData) => void)
    & (<K extends keyof ServiceControlFormData>(key: K, value: ServiceControlFormData[K]) => void)
  processing?: boolean
  errors?: Partial<Record<string, string>>
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
  areaOptions: string[]
  bmInvoiceStatusOptions: string[]
  requesterOptions: PartyOption[]
  assigneeOptions: PartyOption[]
  onSubmit?: (event: FormEvent<HTMLFormElement>) => void
  onDelete?: () => void
}

const humanize = (value: string | null | undefined): string => {
  if (!value) return 'N/A'
  return value.toLowerCase().replace(/_/g, ' ').replace(/\b\w/g, (char) => char.toUpperCase())
}

const humanizeList = (values: string[] | string | null | undefined): string => {
  const list = Array.isArray(values) ? values : (values ? [values] : [])

  return list.length > 0 ? list.map((value) => humanize(value)).join(', ') : 'N/A'
}

const FieldError = ({ message }: { message?: string }) => {
  if (!message) return null
  return <p className="mt-1 text-xs font-medium text-rose-600">{message}</p>
}

const ReadonlyItem = ({ label, value }: { label: string, value?: string | number | null }) => (
  <div className="rounded-lg border border-slate-200/80 bg-slate-50 px-4 py-3">
    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{label}</p>
    <p className="mt-1 text-sm font-medium text-slate-700">{value !== null && value !== undefined && String(value).trim() !== '' ? value : 'N/A'}</p>
  </div>
)

const formatDate = (date: Date) => {
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${date.getFullYear()}-${month}-${day}`
}

export default function ServiceControlForm ({
  auth,
  title,
  mode,
  order,
  serviceControl,
  data,
  setData,
  processing = false,
  errors = {},
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
  closureResultOptions,
  areaOptions,
  bmInvoiceStatusOptions,
  requesterOptions = [],
  assigneeOptions = [],
  onSubmit,
  onDelete,
}: Props) {
  const isReadOnly = mode === 'show'
  const currentRecord = serviceControl ?? null
  const isStandalone = !order.id
  const relatedServices = Array.isArray(order.service_controls) ? order.service_controls : []
  const fallbackRequesterOptions: PartyOption[] = [
    order.client?.id ? { value: `client:${order.client.id}:client`, label: `${order.client.name ?? 'Client'} - Client`, type: 'client', id: Number(order.client.id), role: 'client' } : null,
    order.seller?.id ? { value: `user:${order.seller.id}:seller`, label: `${order.seller.name ?? 'Seller'} - Seller`, type: 'user', id: Number(order.seller.id), role: 'seller' } : null,
    ...(order.owners ?? []).map((owner) => ({ value: `user:${owner.id}:seller`, label: `${owner.name} - Seller`, type: 'user' as const, id: Number(owner.id), role: 'seller' })),
    order.supervisor?.id ? { value: `user:${order.supervisor.id}:supervisor`, label: `${order.supervisor.name ?? 'Supervisor'} - Supervisor`, type: 'user', id: Number(order.supervisor.id), role: 'supervisor' } : null,
  ].filter(Boolean) as PartyOption[]
  const fallbackAssigneeOptions: PartyOption[] = [
    order.client?.id ? { value: `client:${order.client.id}:client`, label: `${order.client.name ?? 'Client'} - Client`, type: 'client', id: Number(order.client.id), role: 'client' } : null,
    order.supervisor?.id ? { value: `user:${order.supervisor.id}:supervisor`, label: `${order.supervisor.name ?? 'Supervisor'} - Supervisor`, type: 'user', id: Number(order.supervisor.id), role: 'supervisor' } : null,
  ].filter(Boolean) as PartyOption[]
  const effectiveRequesterOptions = requesterOptions.length > 0 ? requesterOptions : fallbackRequesterOptions
  const effectiveAssigneeOptions = assigneeOptions.length > 0 ? assigneeOptions : fallbackAssigneeOptions
  const serviceTypeSelectOptions: SelectOption[] = serviceTypeOptions.map((option) => ({ value: option, label: humanize(option) }))
  const selectedServiceTypeOptions = serviceTypeSelectOptions.filter((option) => data.service_type.includes(option.value))
  const [clientSearch, setClientSearch] = useState('')
  const [clientResults, setClientResults] = useState<ClientOption[]>([])
  const [clientLoading, setClientLoading] = useState(false)
  const [selectedClientLabel, setSelectedClientLabel] = useState(
    order.client?.id ? `${order.client.name ?? 'Client'} - ${order.client.phone ?? order.client.email ?? 'No contact'}` : ''
  )
  const selectedStandaloneClientOption = isStandalone && data.client_id && selectedClientLabel
    ? {
        value: `client:${data.client_id}:client`,
        label: selectedClientLabel,
        type: 'client' as const,
        id: Number(data.client_id),
        role: 'client',
      }
    : null
  const requesterOptionsWithStandaloneClient = selectedStandaloneClientOption
    ? [selectedStandaloneClientOption, ...effectiveRequesterOptions].filter((option, index, options) => options.findIndex((item) => item.value === option.value) === index)
    : effectiveRequesterOptions

  useEffect(() => {
    if (isReadOnly) return
    const suffix = data.is_bm ? 'BM' : 'Services'
    if (data.service_name.trim() === '' || data.service_name === `${order.name ?? 'Order'} Services` || data.service_name === `${order.name ?? 'Order'} BM`) {
      setData('service_name', `${order.name ?? 'Standalone Service'} ${suffix}`)
    }
  }, [data.is_bm, order.name])

  useEffect(() => {
    if (!isStandalone || isReadOnly || !data.is_bm) return
    setData('is_bm', false)
  }, [isStandalone, isReadOnly, data.is_bm])

  useEffect(() => {
    if (!isStandalone || isReadOnly) return

    const search = clientSearch.trim()
    if (search.length < 2) {
      setClientResults([])
      setClientLoading(false)
      return
    }

    const timeout = window.setTimeout(() => {
      setClientLoading(true)
      fetch(`${route('service-control.clients.search')}?q=${encodeURIComponent(search)}`)
        .then(async (response) => await response.json())
        .then((payload: { results?: ClientOption[] }) => {
          setClientResults(Array.isArray(payload.results) ? payload.results : [])
        })
        .catch(() => {
          setClientResults([])
        })
        .finally(() => {
          setClientLoading(false)
        })
    }, 250)

    return () => {
      window.clearTimeout(timeout)
    }
  }, [isStandalone, isReadOnly, clientSearch])

  const setDate = (key: keyof ServiceControlFormData, dates: Date[]) => {
    const value = dates[0] ? formatDate(dates[0]) : ''
    setData(key, value)
  }

  const setServiceId = (value: string) => {
    if (data.service_id_requested_date !== '' || value.trim() === '') {
      setData('service_id', value)
      return
    }

    setData((currentData) => ({
      ...currentData,
      service_id: value,
      service_id_requested_date: formatDate(new Date()),
    }))
  }

  const setNewClient = (key: keyof ServiceControlFormData['new_client'], value: string) => {
    setData((currentData) => ({
      ...currentData,
      client_id: '',
      requester_type: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_type,
      requester_id: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_id,
      requester_role: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_role,
      new_client: {
        ...currentData.new_client,
        [key]: value,
      },
    }))
    setSelectedClientLabel('')
  }

  const selectClient = (client: ClientOption) => {
    setData((currentData) => ({
      ...currentData,
      client_id: client.id,
      requester_type: 'client',
      requester_id: client.id,
      requester_role: 'client',
      new_client: {
        name: '',
        phone: '',
        email: '',
        other_phone: '',
        secondary_email: '',
      },
    }))
    setSelectedClientLabel(client.label)
    setClientSearch('')
    setClientResults([])
  }

  const clearSelectedClient = () => {
    setData((currentData) => ({
      ...currentData,
      client_id: '',
      requester_type: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_type,
      requester_id: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_id,
      requester_role: currentData.requester_type === 'client' && String(currentData.requester_id) === String(currentData.client_id) ? '' : currentData.requester_role,
    }))
    setSelectedClientLabel('')
  }

  const dateField = (key: keyof ServiceControlFormData, label: string) => (
    <div>
      <label htmlFor={key} className="text-sm font-semibold text-slate-700">{label}</label>
      <Flatpickr
        id={key}
        value={String(data[key] ?? '')}
        options={{ dateFormat: 'Y-m-d' }}
        onChange={(dates) => { setDate(key, dates) }}
        disabled={isReadOnly}
        className="form-input mt-1"
      />
      <FieldError message={errors[key]} />
    </div>
  )

  const requesterValue = data.requester_type && data.requester_id && data.requester_role
    ? `${data.requester_type}:${data.requester_id}:${data.requester_role}`
    : ''
  const assigneeValue = data.assignee_type && data.assignee_id && data.assignee_role
    ? `${data.assignee_type}:${data.assignee_id}:${data.assignee_role}`
    : ''
  const selectedRequester = requesterOptionsWithStandaloneClient.find((option) => option.value === requesterValue) ?? null
  const selectedAssignee = effectiveAssigneeOptions.find((option) => option.value === assigneeValue) ?? null
  const [localRequester, setLocalRequester] = useState<PartyOption | null>(selectedRequester)
  const [localAssignee, setLocalAssignee] = useState<PartyOption | null>(selectedAssignee)
  const selectPortalTarget = typeof document !== 'undefined' ? document.body : undefined
  const selectStyles = {
    control: (base: any) => ({ ...base, minHeight: '40px' }),
    menuPortal: (base: any) => ({ ...base, zIndex: 99999 }),
    menu: (base: any) => ({ ...base, zIndex: 99999 }),
  }

  useEffect(() => {
    setLocalRequester(selectedRequester)
  }, [selectedRequester?.value])

  useEffect(() => {
    setLocalAssignee(selectedAssignee)
  }, [selectedAssignee?.value])

  const applyParty = (prefix: 'requester' | 'assignee', option: PartyOption | null) => {
    const [type = '', id = '', role = ''] = option?.value?.split(':') ?? []

    if (prefix === 'requester') {
      setData((currentData) => ({
        ...currentData,
        requester_type: type,
        requester_id: id,
        requester_role: role,
      }))
      return
    }

    setData((currentData) => ({
      ...currentData,
      assignee_type: type,
      assignee_id: id,
      assignee_role: role,
    }))
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={title}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <Link href={route('service-control.index')} className="btn btn-outline-primary">Back</Link>
          {order.id && <Link href={route('frontdesk.order_view', { id: order.id })} className="btn btn-outline-info">View Order</Link>}
          {mode === 'edit' && onDelete && (
            <button type="button" className="btn btn-outline-danger" onClick={onDelete}>Delete</button>
          )}
          {mode === 'show' && currentRecord && (
            <Link href={route('service-control.edit', currentRecord.id)} className="btn btn-primary">Edit Service Control</Link>
          )}
        </div>
      }
    >
      <Head title={title} />

      <div className="space-y-6">
        <div className="panel space-y-5">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div>
              <h2 className="text-lg font-semibold text-slate-800">{order.name}</h2>
              <p className="text-sm text-slate-500">
                {order.id ? `Order #${order.order_number ?? 'N/A'}${order.order_type ? ` · ${humanize(order.order_type)}` : ''}` : 'Unlinked service'}
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <span className="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700">Today {order.today_date ?? 'N/A'}</span>
              {currentRecord?.service_status && <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">{currentRecord.service_status}</span>}
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <ReadonlyItem label="Client Name" value={order.client?.name ?? null} />
            <ReadonlyItem label="Client Phone" value={order.client?.phone ?? null} />
            <ReadonlyItem label="Client Email" value={order.client?.email ?? null} />
            <ReadonlyItem label="Supervisor" value={order.supervisor?.name ?? null} />
            <ReadonlyItem label="Address" value={order.address_label ?? null} />
            <ReadonlyItem label="Company" value={order.company?.name ?? null} />
            <ReadonlyItem label="Open Days" value={currentRecord?.open_days ?? 0} />
            <ReadonlyItem label="Opened At" value={currentRecord?.opened_at ?? order.today_date ?? null} />
          </div>

          {relatedServices.length > 0 && (
            <div className="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Related Service Controls</h3>
                <span className="text-xs text-slate-400">{relatedServices.length} linked</span>
              </div>
              <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {relatedServices.map((item) => (
                  <Link key={item.id} href={route('service-control.edit', item.id)} className="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-sky-300">
                    <div className="flex items-center justify-between gap-2">
                      <p className="text-sm font-semibold text-slate-700">{item.service_name?.trim() || humanizeList(item.service_type)}</p>
                      <span className="text-xs text-slate-400">{item.open_days ?? 0} days</span>
                    </div>
                    <p className="mt-1 text-xs text-slate-500">Service ID: {item.service_id?.trim() || 'N/A'}</p>
                    <p className="mt-1 text-xs uppercase tracking-wide text-slate-400">{item.service_status}</p>
                  </Link>
                ))}
              </div>
            </div>
          )}
        </div>

        <form onSubmit={onSubmit} className="panel space-y-6">
          {isStandalone && (
            <div className="space-y-4 rounded-lg border border-slate-200 bg-slate-50 p-4">
              <div>
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-500">Client</h3>
                <p className="mt-1 text-xs text-slate-400">Search an existing client or enter the client data below.</p>
              </div>

              {selectedClientLabel ? (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-emerald-200 bg-white px-4 py-3">
                  <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-emerald-600">Selected Client</p>
                    <p className="mt-1 text-sm font-medium text-slate-700">{selectedClientLabel}</p>
                  </div>
                  {!isReadOnly && (
                    <button type="button" onClick={clearSelectedClient} className="btn btn-outline-secondary">
                      Change
                    </button>
                  )}
                </div>
              ) : (
                <>
                  {!isReadOnly && (
                    <div className="relative">
                      <label htmlFor="client_search" className="text-sm font-semibold text-slate-700">Search Client</label>
                      <input
                        id="client_search"
                        type="text"
                        value={clientSearch}
                        onChange={(event) => { setClientSearch(event.target.value) }}
                        className="form-input mt-1"
                        placeholder="Name, phone, or email"
                      />
                      {(clientLoading || clientResults.length > 0) && (
                        <div className="absolute z-30 mt-1 max-h-64 w-full overflow-auto rounded-lg border border-slate-200 bg-white shadow-lg">
                          {clientLoading && <div className="px-4 py-3 text-sm text-slate-500">Searching...</div>}
                          {!clientLoading && clientResults.map((client) => (
                            <button
                              key={client.id}
                              type="button"
                              onClick={() => { selectClient(client) }}
                              className="block w-full px-4 py-3 text-left text-sm hover:bg-slate-50"
                            >
                              <span className="font-medium text-slate-700">{client.name ?? 'Client'}</span>
                              <span className="block text-xs text-slate-400">{client.phone ?? client.email ?? 'No contact'}</span>
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  )}

                  <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div>
                      <label htmlFor="new_client_name" className="text-sm font-semibold text-slate-700">Client Name</label>
                      <input id="new_client_name" type="text" value={data.new_client.name} onChange={(event) => { setNewClient('name', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                      <FieldError message={errors['new_client.name']} />
                    </div>
                    <div>
                      <label htmlFor="new_client_phone" className="text-sm font-semibold text-slate-700">Client Phone</label>
                      <input id="new_client_phone" type="text" value={data.new_client.phone} onChange={(event) => { setNewClient('phone', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                      <FieldError message={errors['new_client.phone']} />
                    </div>
                    <div>
                      <label htmlFor="new_client_email" className="text-sm font-semibold text-slate-700">Client Email</label>
                      <input id="new_client_email" type="email" value={data.new_client.email} onChange={(event) => { setNewClient('email', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                      <FieldError message={errors['new_client.email']} />
                    </div>
                    <div>
                      <label htmlFor="new_client_other_phone" className="text-sm font-semibold text-slate-700">Other Phone</label>
                      <input id="new_client_other_phone" type="text" value={data.new_client.other_phone} onChange={(event) => { setNewClient('other_phone', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                      <FieldError message={errors['new_client.other_phone']} />
                    </div>
                    <div>
                      <label htmlFor="new_client_secondary_email" className="text-sm font-semibold text-slate-700">Secondary Email</label>
                      <input id="new_client_secondary_email" type="email" value={data.new_client.secondary_email} onChange={(event) => { setNewClient('secondary_email', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                      <FieldError message={errors['new_client.secondary_email']} />
                    </div>
                  </div>
                </>
              )}
            </div>
          )}

          {!isStandalone && (
            <label className="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
              <input type="checkbox" checked={Boolean(data.is_bm)} onChange={(event) => { setData('is_bm', event.target.checked) }} disabled={isReadOnly} className="form-checkbox" />
              <span className="text-sm font-medium text-slate-700">BM</span>
            </label>
          )}

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
              <label htmlFor="service_name" className="text-sm font-semibold text-slate-700">Service Name</label>
              <input id="service_name" type="text" value={data.service_name} onChange={(event) => { setData('service_name', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
              <FieldError message={errors.service_name} />
            </div>
            {!data.is_bm && (
              <>
                <div>
                  <label htmlFor="service_id" className="text-sm font-semibold text-slate-700">Service ID</label>
                  <input id="service_id" type="text" value={data.service_id} onChange={(event) => { setServiceId(event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                  <FieldError message={errors.service_id} />
                </div>
                <div>
                  <label className="text-sm font-semibold text-slate-700">Service Type</label>
                  <Select
                    id="service_type"
                    placeholder="Select service type"
                    name="service_type"
                    isMulti
                    isDisabled={isReadOnly}
                    value={selectedServiceTypeOptions}
                    onChange={(value) => {
                      const options = Array.from(value as readonly SelectOption[])
                      setData('service_type', options.map((option) => option.value))
                    }}
                    options={serviceTypeSelectOptions}
                    menuPortalTarget={selectPortalTarget}
                    menuPosition="fixed"
                    styles={selectStyles}
                  />
                  <FieldError message={errors.service_type} />
                </div>
                <div>
                  <label htmlFor="service_status" className="text-sm font-semibold text-slate-700">Service Status</label>
                  <select id="service_status" value={data.service_status} onChange={(event) => { setData('service_status', event.target.value) }} disabled={isReadOnly} className="form-select mt-1">
                    <option value="">Select</option>
                    {serviceStatusOptions.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}
                  </select>
                  <FieldError message={errors.service_status} />
                </div>
                <div>
                  <label htmlFor="priority" className="text-sm font-semibold text-slate-700">Priority</label>
                  <select id="priority" value={data.priority} onChange={(event) => { setData('priority', event.target.value) }} disabled={isReadOnly} className="form-select mt-1">
                    <option value="">Select</option>
                    {priorityOptions.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}
                  </select>
                  <FieldError message={errors.priority} />
                </div>
                <div>
                  <label htmlFor="area" className="text-sm font-semibold text-slate-700">Area</label>
                  <select id="area" value={data.area} onChange={(event) => { setData('area', event.target.value) }} disabled={isReadOnly} className="form-select mt-1">
                    <option value="">Select</option>
                    {areaOptions.map((option) => <option key={option} value={option}>{option}</option>)}
                  </select>
                  <FieldError message={errors.area} />
                </div>
              </>
            )}
          </div>

          {data.is_bm
            ? (
              <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <div>
                  <label htmlFor="bm_quantity" className="text-sm font-semibold text-slate-700">Quantity</label>
                  <input id="bm_quantity" type="number" min="1" value={data.bm_quantity} onChange={(event) => { setData('bm_quantity', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                  <FieldError message={errors.bm_quantity} />
                </div>
                {dateField('bm_requested_date', 'Requested Date')}
                <div>
                  <label htmlFor="bm_picked_up_by" className="text-sm font-semibold text-slate-700">Picked Up By</label>
                  <input id="bm_picked_up_by" type="text" value={data.bm_picked_up_by} onChange={(event) => { setData('bm_picked_up_by', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                  <FieldError message={errors.bm_picked_up_by} />
                </div>
                {dateField('bm_pickup_date', 'Pickup Date')}
                <div>
                  <label htmlFor="bm_invoice_number" className="text-sm font-semibold text-slate-700">BM Invoice #</label>
                  <input id="bm_invoice_number" type="text" value={data.bm_invoice_number} onChange={(event) => { setData('bm_invoice_number', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                  <FieldError message={errors.bm_invoice_number} />
                </div>
                <div>
                  <label htmlFor="bm_invoice_status" className="text-sm font-semibold text-slate-700">Invoice Status</label>
                  <select id="bm_invoice_status" value={data.bm_invoice_status} onChange={(event) => { setData('bm_invoice_status', event.target.value) }} disabled={isReadOnly} className="form-select mt-1">
                    <option value="">Select</option>
                    {bmInvoiceStatusOptions.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}
                  </select>
                  <FieldError message={errors.bm_invoice_status} />
                </div>
              </div>
            )
            : (
              <>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                  <div>
                    <label className="text-sm font-semibold text-slate-700">Service Requester</label>
                    <Select
                      id="service_requester"
                      placeholder="Select requester"
                      name="service_requester"
                      isMulti={false}
                      isDisabled={isReadOnly}
                      value={localRequester}
                      onChange={(value) => {
                        const option = value as PartyOption | null
                        setLocalRequester(option)
                        applyParty('requester', option)
                      }}
                      options={requesterOptionsWithStandaloneClient}
                      menuPortalTarget={selectPortalTarget}
                      menuPosition="fixed"
                      styles={selectStyles}
                    />
                    <FieldError message={errors.requester_id || errors.requester_type || errors.requester_role} />
                  </div>
                  <div>
                    <label className="text-sm font-semibold text-slate-700">Service Assignee</label>
                    <Select
                      id="service_assignee"
                      placeholder="Select assignee"
                      name="service_assignee"
                      isMulti={false}
                      isDisabled={isReadOnly}
                      value={localAssignee}
                      onChange={(value) => {
                        const option = value as PartyOption | null
                        setLocalAssignee(option)
                        applyParty('assignee', option)
                      }}
                      options={effectiveAssigneeOptions}
                      menuPortalTarget={selectPortalTarget}
                      menuPosition="fixed"
                      styles={selectStyles}
                    />
                    <FieldError message={errors.assignee_id || errors.assignee_type || errors.assignee_role} />
                  </div>
                  <div>
                    <label htmlFor="cost" className="text-sm font-semibold text-slate-700">Cost</label>
                    <input id="cost" type="number" min="0" step="0.01" value={data.cost} onChange={(event) => { setData('cost', event.target.value) }} disabled={isReadOnly} className="form-input mt-1" />
                    <FieldError message={errors.cost} />
                  </div>
                  {dateField('service_created_date', 'Service Created Date')}
                  {dateField('service_id_requested_date', 'Obtained Date')}
                  {dateField('eta_date', 'ETA Date')}
                  {dateField('parts_received_date', 'Parts Received Date')}
                  {dateField('part_delivered_date', 'Part Delivered Date')}
                  {dateField('target_date', 'Target Date')}
                  {dateField('scheduled_date', 'Scheduled Date')}
                  {dateField('executed_date', 'Executed Date')}
                  <div>
                    <label htmlFor="closure_result" className="text-sm font-semibold text-slate-700">Closure Result</label>
                    <select id="closure_result" value={data.closure_result} onChange={(event) => { setData('closure_result', event.target.value) }} disabled={isReadOnly} className="form-select mt-1">
                      <option value="">Select</option>
                      {closureResultOptions.map((option) => <option key={option} value={option}>{humanize(option)}</option>)}
                    </select>
                    <FieldError message={errors.closure_result} />
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                  {[
                    ['requires_part', 'Requires Part'],
                    ['requested_parts', 'Requested Parts'],
                    ['parts_available', 'Parts Available'],
                  ].map(([key, label]) => (
                    <label key={key} className="inline-flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                      <input type="checkbox" checked={Boolean(data[key as keyof ServiceControlFormData])} onChange={(event) => { setData(key as keyof ServiceControlFormData, event.target.checked) }} disabled={isReadOnly} className="form-checkbox" />
                      <span className="text-sm font-medium text-slate-700">{label}</span>
                    </label>
                  ))}
                </div>

                <div className="grid gap-4 xl:grid-cols-2">
                  <div>
                    <label htmlFor="description" className="text-sm font-semibold text-slate-700">Description</label>
                    <textarea id="description" value={data.description} onChange={(event) => { setData('description', event.target.value) }} disabled={isReadOnly} rows={5} className="form-textarea mt-1" />
                    <FieldError message={errors.description} />
                  </div>
                  <div>
                    <label htmlFor="observations" className="text-sm font-semibold text-slate-700">Observations</label>
                    <textarea id="observations" value={data.observations} onChange={(event) => { setData('observations', event.target.value) }} disabled={isReadOnly} rows={5} className="form-textarea mt-1" />
                    <FieldError message={errors.observations} />
                  </div>
                </div>
              </>
            )}

          {!isReadOnly && (
            <div className="flex justify-end">
              <button type="submit" disabled={processing} className="btn btn-primary">
                {processing ? 'Saving...' : (mode === 'create' ? 'Create Service Control' : 'Update Service Control')}
              </button>
            </div>
          )}
        </form>

        {currentRecord && (
          <div className="panel space-y-4">
            <div>
              <h2 className="text-base font-semibold text-slate-800">History</h2>
              <p className="text-sm text-slate-400">Operational traceability for this service control.</p>
            </div>
            <HistoryTimeline histories={currentRecord.histories} />
          </div>
        )}
      </div>
    </AuthenticatedLayout>
  )
}
