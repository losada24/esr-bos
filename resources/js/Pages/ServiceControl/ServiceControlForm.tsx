import { type FormEvent } from 'react'
import { Head, Link } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import HistoryTimeline from './HistoryTimeline'
import { type PageProps, type ServiceControl, type ServiceControlOrderSummary } from '@/types'

type ServiceControlFormData = {
  order_id: number
  service_name: string
  service_id: string
  service_type: string
  description: string
  requires_part: boolean
  requested_parts: boolean
  parts_available: boolean
  service_status: string
  priority: string
  target_date: string
  scheduled_date: string
  executed_date: string
  closure_result: string
  observations: string
}

type Props = PageProps & {
  title: string
  mode: 'create' | 'edit' | 'show'
  order: ServiceControlOrderSummary
  serviceControl?: ServiceControl | null
  data: ServiceControlFormData
  setData: (key: keyof ServiceControlFormData, value: ServiceControlFormData[keyof ServiceControlFormData]) => void
  processing?: boolean
  errors?: Partial<Record<keyof ServiceControlFormData, string>>
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
  closureResultOptions: string[]
  onSubmit?: (event: FormEvent<HTMLFormElement>) => void
}

const humanize = (value: string | null | undefined): string => {
  if (!value) return 'N/A'
  return value
    .toLowerCase()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

const FieldError = ({ message }: { message?: string }) => {
  if (!message) return null
  return <p className="mt-1 text-xs font-medium text-rose-600">{message}</p>
}

const ReadonlyItem = ({ label, value }: { label: string, value?: string | null }) => (
  <div className="rounded-xl border border-slate-200/80 bg-slate-50 px-4 py-3">
    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{label}</p>
    <p className="mt-1 text-sm font-medium text-slate-700">{value && value.trim() !== '' ? value : 'N/A'}</p>
  </div>
)

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
  onSubmit,
}: Props) {
  const isReadOnly = mode === 'show'
  const currentRecord = serviceControl ?? null
  const openDays = currentRecord?.open_days ?? 0
  const relatedServices = Array.isArray(order.service_controls) ? order.service_controls : []

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={title}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <Link href={route('service-control.index')} className="btn btn-outline-primary">
            Back
          </Link>
          <Link href={route('frontdesk.order_view', { id: order.id })} className="btn btn-outline-info">
            View Order
          </Link>
          {mode === 'show' && currentRecord && (
            <Link href={route('service-control.edit', currentRecord.id)} className="btn btn-primary">
              Edit Service Control
            </Link>
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
                Order #{order.order_number ?? 'N/A'}
                {order.order_type ? ` · ${humanize(order.order_type)}` : ''}
              </p>
            </div>
            <div className="flex flex-wrap gap-2">
              <span className="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-sky-700">
                Today {order.today_date ?? 'N/A'}
              </span>
              {currentRecord?.service_status && (
                <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-600">
                  {currentRecord.service_status}
                </span>
              )}
            </div>
          </div>

          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <ReadonlyItem label="Client Name" value={order.client?.name ?? null} />
            <ReadonlyItem label="Client Phone" value={order.client?.phone ?? null} />
            <ReadonlyItem label="Client Email" value={order.client?.email ?? null} />
            <ReadonlyItem label="Supervisor" value={order.supervisor?.name ?? null} />
            <ReadonlyItem label="Address" value={order.address_label ?? null} />
            <ReadonlyItem label="Client Details" value={order.client?.contact_type ?? order.client?.secondary_email ?? order.client?.other_phone ?? null} />
            <ReadonlyItem label="Company" value={order.company?.name ?? null} />
            <ReadonlyItem label="Company Data" value={order.company ? [order.company.email, order.company.phone].filter(Boolean).join(' · ') : null} />
          </div>

          {(order.client?.vip_clients || order.client?.vip_notes) && (
            <div className="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
              <div className="flex flex-wrap items-center gap-2">
                <span className="rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-rose-700">VIP</span>
                <span>{order.client?.vip_notes ?? 'No VIP notes recorded.'}</span>
              </div>
            </div>
          )}

          {relatedServices.length > 0 && (
            <div className="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
              <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Related Service Controls</h3>
                <span className="text-xs text-slate-400">{relatedServices.length} linked</span>
              </div>
              <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                {relatedServices.map((item) => (
                  <Link
                    key={item.id}
                    href={route('service-control.edit', item.id)}
                    className="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm transition hover:border-sky-300"
                  >
                    <div className="flex items-center justify-between gap-2">
                      <p className="text-sm font-semibold text-slate-700">{item.service_name?.trim() || humanize(item.service_type)}</p>
                      <span className="text-xs text-slate-400">{item.open_days ?? 0} days</span>
                    </div>
                    <p className="mt-1 text-xs text-slate-500">Service ID: {item.service_id?.trim() || 'N/A'}</p>
                    <p className="mt-1 text-xs uppercase tracking-wide text-slate-400">{item.service_status}</p>
                    <p className="mt-2 text-xs text-slate-500">{item.priority}</p>
                  </Link>
                ))}
              </div>
            </div>
          )}
        </div>

        <form onSubmit={onSubmit} className="panel space-y-6">
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div>
              <label htmlFor="service_name" className="text-sm font-semibold text-slate-700">Service Name</label>
              <input
                id="service_name"
                type="text"
                value={data.service_name}
                onChange={(event) => { setData('service_name', event.target.value) }}
                disabled={isReadOnly}
                className="form-input mt-1"
              />
              <FieldError message={errors.service_name} />
            </div>

            <div>
              <label htmlFor="service_id" className="text-sm font-semibold text-slate-700">Service ID</label>
              <input
                id="service_id"
                type="text"
                value={data.service_id}
                onChange={(event) => { setData('service_id', event.target.value) }}
                disabled={isReadOnly}
                className="form-input mt-1"
              />
              <FieldError message={errors.service_id} />
            </div>

            <div>
              <label htmlFor="service_type" className="text-sm font-semibold text-slate-700">Service Type</label>
              <select
                id="service_type"
                value={data.service_type}
                onChange={(event) => { setData('service_type', event.target.value) }}
                disabled={isReadOnly}
                className="form-select mt-1"
              >
                <option value="">Select</option>
                {serviceTypeOptions.map((option) => (
                  <option key={option} value={option}>{humanize(option)}</option>
                ))}
              </select>
              <FieldError message={errors.service_type} />
            </div>

            <div>
              <label htmlFor="service_status" className="text-sm font-semibold text-slate-700">Service Status</label>
              <select
                id="service_status"
                value={data.service_status}
                onChange={(event) => { setData('service_status', event.target.value) }}
                disabled={isReadOnly}
                className="form-select mt-1"
              >
                <option value="">Select</option>
                {serviceStatusOptions.map((option) => (
                  <option key={option} value={option}>{humanize(option)}</option>
                ))}
              </select>
              <FieldError message={errors.service_status} />
            </div>

            <div>
              <label htmlFor="priority" className="text-sm font-semibold text-slate-700">Priority</label>
              <select
                id="priority"
                value={data.priority}
                onChange={(event) => { setData('priority', event.target.value) }}
                disabled={isReadOnly}
                className="form-select mt-1"
              >
                <option value="">Select</option>
                {priorityOptions.map((option) => (
                  <option key={option} value={option}>{humanize(option)}</option>
                ))}
              </select>
              <FieldError message={errors.priority} />
            </div>

            <div>
              <label htmlFor="target_date" className="text-sm font-semibold text-slate-700">Target Date</label>
              <input
                id="target_date"
                type="date"
                value={data.target_date}
                onChange={(event) => { setData('target_date', event.target.value) }}
                disabled={isReadOnly}
                className="form-input mt-1"
              />
              <FieldError message={errors.target_date} />
            </div>

            <div>
              <label htmlFor="scheduled_date" className="text-sm font-semibold text-slate-700">Scheduled Date</label>
              <input
                id="scheduled_date"
                type="date"
                value={data.scheduled_date}
                onChange={(event) => { setData('scheduled_date', event.target.value) }}
                disabled={isReadOnly}
                className="form-input mt-1"
              />
              <FieldError message={errors.scheduled_date} />
            </div>

            <div>
              <label htmlFor="executed_date" className="text-sm font-semibold text-slate-700">Executed Date</label>
              <input
                id="executed_date"
                type="date"
                value={data.executed_date}
                onChange={(event) => { setData('executed_date', event.target.value) }}
                disabled={isReadOnly}
                className="form-input mt-1"
              />
              <FieldError message={errors.executed_date} />
            </div>

            <div>
              <label htmlFor="closure_result" className="text-sm font-semibold text-slate-700">Closure Result</label>
              <select
                id="closure_result"
                value={data.closure_result}
                onChange={(event) => { setData('closure_result', event.target.value) }}
                disabled={isReadOnly}
                className="form-select mt-1"
              >
                <option value="">Select</option>
                {closureResultOptions.map((option) => (
                  <option key={option} value={option}>{humanize(option)}</option>
                ))}
              </select>
              <FieldError message={errors.closure_result} />
            </div>

            <ReadonlyItem label="Open Days" value={String(openDays)} />
            <ReadonlyItem label="Opened At" value={currentRecord?.opened_at ?? order.today_date ?? null} />
          </div>

          <div className="grid gap-4 md:grid-cols-3">
            <label className="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
              <input
                type="checkbox"
                checked={Boolean(data.requires_part)}
                onChange={(event) => { setData('requires_part', event.target.checked) }}
                disabled={isReadOnly}
                className="form-checkbox"
              />
              <span className="text-sm font-medium text-slate-700">Requires Part</span>
            </label>

            <label className="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
              <input
                type="checkbox"
                checked={Boolean(data.requested_parts)}
                onChange={(event) => { setData('requested_parts', event.target.checked) }}
                disabled={isReadOnly}
                className="form-checkbox"
              />
              <span className="text-sm font-medium text-slate-700">Requested Parts</span>
            </label>

            <label className="inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
              <input
                type="checkbox"
                checked={Boolean(data.parts_available)}
                onChange={(event) => { setData('parts_available', event.target.checked) }}
                disabled={isReadOnly}
                className="form-checkbox"
              />
              <span className="text-sm font-medium text-slate-700">Parts Available</span>
            </label>
          </div>

          <div className="grid gap-4 xl:grid-cols-2">
            <div>
              <label htmlFor="description" className="text-sm font-semibold text-slate-700">Description</label>
              <textarea
                id="description"
                value={data.description}
                onChange={(event) => { setData('description', event.target.value) }}
                disabled={isReadOnly}
                rows={5}
                className="form-textarea mt-1"
              />
              <FieldError message={errors.description} />
            </div>

            <div>
              <label htmlFor="observations" className="text-sm font-semibold text-slate-700">Observations</label>
              <textarea
                id="observations"
                value={data.observations}
                onChange={(event) => { setData('observations', event.target.value) }}
                disabled={isReadOnly}
                rows={5}
                className="form-textarea mt-1"
              />
              <FieldError message={errors.observations} />
            </div>
          </div>

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
