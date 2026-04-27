import { Head, Link, router } from '@inertiajs/react'
import { useState } from 'react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import { type PageProps, type ServiceControl } from '@/types'

type IndexProps = PageProps & {
  serviceControls: ServiceControl[]
  filters: {
    search?: string
    status?: string
    priority?: string
  }
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
}

const MODULE_OPTIONS = [
  { value: 'service_control', label: 'Service Control' }
]

const humanize = (value: string | null | undefined): string => {
  if (!value) return 'N/A'
  return value
    .toLowerCase()
    .replace(/_/g, ' ')
    .replace(/\b\w/g, (char) => char.toUpperCase())
}

export default function Index ({
  auth,
  serviceControls,
  filters,
  serviceStatusOptions,
  priorityOptions,
}: IndexProps) {
  const [search, setSearch] = useState(filters.search ?? '')
  const [status, setStatus] = useState(filters.status ?? '')
  const [priority, setPriority] = useState(filters.priority ?? '')

  const applyFilters = () => {
    router.get(route('service-control.index'), {
      search,
      status,
      priority,
    }, {
      preserveState: true,
      replace: true,
    })
  }

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      pageTitle="Service Control"
      leftActions={
        <OrderGlobalSearch
          origin="service_control"
          modules={MODULE_OPTIONS}
          defaultModule="service_control"
          className="w-full max-w-[520px]"
          onSelectOrder={(orderId) => {
            router.visit(route('service-control.create', { order_id: orderId }))
          }}
        />
      }
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <input
            type="text"
            value={search}
            onChange={(event) => { setSearch(event.target.value) }}
            placeholder="Filter existing services..."
            className="form-input w-56"
          />
          <select value={status} onChange={(event) => { setStatus(event.target.value) }} className="form-select w-48">
            <option value="">All statuses</option>
            {serviceStatusOptions.map((option) => (
              <option key={option} value={option}>{humanize(option)}</option>
            ))}
          </select>
          <select value={priority} onChange={(event) => { setPriority(event.target.value) }} className="form-select w-40">
            <option value="">All priorities</option>
            {priorityOptions.map((option) => (
              <option key={option} value={option}>{humanize(option)}</option>
            ))}
          </select>
          <button type="button" className="btn btn-primary" onClick={applyFilters}>Apply</button>
        </div>
      }
    >
      <Head title="Service Control" />

      <div className="space-y-6">
        <div className="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
          Search a sold order above to create a new service control associated with that order. Existing modules keep their current order view behavior.
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold text-slate-800">Recent Service Controls</h2>
              <p className="text-sm text-slate-400">Latest operational service records linked to sold orders.</p>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
              {serviceControls.length} items
            </span>
          </div>

          <div className="table-responsive">
            <table className="w-full whitespace-nowrap">
              <thead>
                <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  <th className="px-4 py-3">Order</th>
                  <th className="px-4 py-3">Client</th>
                  <th className="px-4 py-3">Service</th>
                  <th className="px-4 py-3">Type</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Priority</th>
                  <th className="px-4 py-3">Open Days</th>
                  <th className="px-4 py-3">Updated</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {serviceControls.map((serviceControl) => (
                  <tr key={serviceControl.id} className="border-t border-slate-200 text-sm text-slate-600">
                    <td className="px-4 py-4 align-top">
                      <div className="font-semibold text-slate-700">{serviceControl.order?.name ?? 'Order'}</div>
                      <div className="text-xs text-slate-400">#{serviceControl.order?.order_number ?? 'N/A'}</div>
                    </td>
                    <td className="px-4 py-4 align-top">
                      <div>{serviceControl.order?.client?.name ?? 'No client'}</div>
                      <div className="text-xs text-slate-400">{serviceControl.order?.client?.phone ?? 'No phone'}</div>
                    </td>
                    <td className="px-4 py-4 align-top">
                      <div className="font-semibold text-slate-700">{serviceControl.service_name ?? 'N/A'}</div>
                      <div className="text-xs text-slate-400">{serviceControl.service_id ?? 'No service ID'}</div>
                    </td>
                    <td className="px-4 py-4 align-top">{humanize(serviceControl.service_type)}</td>
                    <td className="px-4 py-4 align-top">
                      <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                        {humanize(serviceControl.service_status)}
                      </span>
                    </td>
                    <td className="px-4 py-4 align-top">{humanize(serviceControl.priority)}</td>
                    <td className="px-4 py-4 align-top">{serviceControl.open_days ?? 0}</td>
                    <td className="px-4 py-4 align-top">
                      <div className="text-xs text-slate-400">{serviceControl.updated_at ? new Date(serviceControl.updated_at).toLocaleString() : 'N/A'}</div>
                    </td>
                    <td className="px-4 py-4 align-top">
                      <div className="flex items-center gap-2">
                        <Link href={route('service-control.show', serviceControl.id)} className="btn btn-sm btn-outline-info">
                          View
                        </Link>
                        <Link href={route('service-control.edit', serviceControl.id)} className="btn btn-sm btn-outline-primary">
                          Edit
                        </Link>
                      </div>
                    </td>
                  </tr>
                ))}
                {serviceControls.length === 0 && (
                  <tr>
                    <td colSpan={9} className="px-4 py-10 text-center text-sm text-slate-400">
                      No service controls found for the selected filters.
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AuthenticatedCalendarLayout>
  )
}
