import { Head, Link, router } from '@inertiajs/react'
import { useEffect, useMemo, useState } from 'react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import EditIcon from '@/Components/Icons/EditIcon'
import EyeIcon from '@/Components/Icons/EyeIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import { type PageProps, type ServiceControl } from '@/types'

type IndexProps = PageProps & {
  serviceControls: ServiceControl[]
  filters: {
    search?: string
    status?: string
    priority?: string
    type?: string
  }
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
}

type ServiceControlFilterState = {
  type: 'services' | 'bm'
  search: string
  status: string
  priority: string
}

const FILTER_STORAGE_KEY = 'service-control-filters'

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

const humanizeList = (values: string[] | string | null | undefined): string => {
  const list = Array.isArray(values) ? values : (values ? [values] : [])

  return list.length > 0 ? list.map((value) => humanize(value)).join(', ') : 'N/A'
}

function buildFilterState (filters: ServiceControlFilterState): ServiceControlFilterState {
  const type = filters.type === 'bm' ? 'bm' : 'services'

  return {
    type,
    search: filters.search ?? '',
    status: type === 'services' ? (filters.status ?? '') : '',
    priority: type === 'services' ? (filters.priority ?? '') : '',
  }
}

function readStoredFilters (): ServiceControlFilterState | null {
  if (typeof window === 'undefined') return null

  const raw = window.localStorage.getItem(FILTER_STORAGE_KEY)
  if (!raw) return null

  try {
    const parsed = JSON.parse(raw) as Partial<ServiceControlFilterState>
    return buildFilterState({
      type: parsed.type === 'bm' ? 'bm' : 'services',
      search: parsed.search ?? '',
      status: parsed.status ?? '',
      priority: parsed.priority ?? '',
    })
  } catch {
    window.localStorage.removeItem(FILTER_STORAGE_KEY)
    return null
  }
}

function storeFilters (filters: ServiceControlFilterState) {
  if (typeof window === 'undefined') return
  window.localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(buildFilterState(filters)))
}

function clearStoredFilters () {
  if (typeof window === 'undefined') return
  window.localStorage.removeItem(FILTER_STORAGE_KEY)
}

function hasExplicitFilterParams (): boolean {
  if (typeof window === 'undefined') return false
  const params = new URLSearchParams(window.location.search)

  return ['type', 'search', 'status', 'priority'].some((key) => {
    const value = params.get(key)
    return value !== null && value !== ''
  })
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
  const activeType = filters.type === 'bm' ? 'bm' : 'services'
  const isBmView = activeType === 'bm'
  const appliedFilters = useMemo(() => buildFilterState({
    type: activeType,
    search: filters.search ?? '',
    status: filters.status ?? '',
    priority: filters.priority ?? '',
  }), [activeType, filters.search, filters.status, filters.priority])
  const exportQuery = `?${new URLSearchParams({
    type: activeType,
    search,
    status: isBmView ? '' : status,
    priority: isBmView ? '' : priority,
  }).toString()}`

  useEffect(() => {
    const savedFilters = readStoredFilters()

    if (!hasExplicitFilterParams() && savedFilters) {
      router.get(route('service-control.index'), savedFilters, {
        replace: true,
        preserveScroll: true,
        preserveState: false,
      })

      return
    }

    if (!hasExplicitFilterParams()) return

    storeFilters(appliedFilters)
  }, [appliedFilters])

  const visitType = (type: 'services' | 'bm') => {
    const nextFilters = buildFilterState({
      type,
      search,
      status: type === 'services' ? status : '',
      priority: type === 'services' ? priority : '',
    })

    storeFilters(nextFilters)
    router.get(route('service-control.index'), nextFilters, {
      preserveState: true,
      replace: true,
    })
  }

  const applyFilters = () => {
    visitType(activeType)
  }

  const destroy = (serviceControl: ServiceControl) => {
    if (!window.confirm('Are you sure you want to delete this service control?')) return

    router.delete(route('service-control.destroy', serviceControl.id), {
      preserveScroll: true,
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
          onSelectOrder={(orderId, order) => {
            const assignedCount = isBmView
              ? Number(order?.assigned_bm_count ?? 0)
              : Number(order?.assigned_services_count ?? 0)
            const message = isBmView
              ? 'This order already has an assigned BM. Do you want to associate another BM?'
              : 'This order already has an assigned service. Do you want to associate another service?'

            if (assignedCount > 0 && !window.confirm(message)) {
              return
            }

            router.visit(route('service-control.create', { order_id: orderId, type: activeType }))
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
          {!isBmView && (
            <>
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
            </>
          )}
          <button type="button" className="btn btn-primary" onClick={applyFilters}>Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => {
              clearStoredFilters()
              setSearch('')
              setStatus('')
              setPriority('')
              router.get(route('service-control.index'), { type: activeType }, { replace: true })
            }}
          >
            Reset
          </button>
          <a
            className="btn btn-outline-primary"
            href={route('service-control.pdf') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a
            className="btn btn-outline-primary"
            href={route('service-control.excel') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            Export Excel
          </a>
          <Link href={route('service-control.create', { type: activeType })} className="btn btn-outline-primary">
            New Standalone Service
          </Link>
        </div>
      }
    >
      <Head title="Service Control" />

      <div className="space-y-6">
        <div className="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
          Search a sold order above to create a new service control associated with that order, or use New Standalone Service for an unlinked service.
        </div>

        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            onClick={() => { visitType('services') }}
            className={activeType === 'services' ? 'btn btn-primary' : 'btn btn-outline-primary'}
          >
            Services
          </button>
          <button
            type="button"
            onClick={() => { visitType('bm') }}
            className={activeType === 'bm' ? 'btn btn-primary' : 'btn btn-outline-primary'}
          >
            BM
          </button>
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold text-slate-800">{isBmView ? 'Recent BM Records' : 'Recent Services'}</h2>
              <p className="text-sm text-slate-400">{isBmView ? 'BM records linked to sold orders.' : 'Latest operational service records linked to sold orders.'}</p>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
              {serviceControls.length} items
            </span>
          </div>

          <div className="table-responsive">
            <table className="w-full whitespace-nowrap">
              <thead>
                <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  {!isBmView && (
                    <>
                      <th className="px-4 py-3">Order</th>
                      <th className="px-4 py-3">Client</th>
                      <th className="px-4 py-3">Service</th>
                    </>
                  )}
                  {isBmView
                    ? (
                      <>
                        <th className="px-4 py-3">Order Name</th>
                        <th className="px-4 py-3">Supervisor</th>
                        <th className="px-4 py-3">QTY</th>
                        <th className="px-4 py-3">Request Date</th>
                        <th className="px-4 py-3">Picked Up By</th>
                        <th className="px-4 py-3">Invoice #</th>
                        <th className="px-4 py-3">Invoice Status</th>
                      </>
                      )
                    : (
                      <>
                        <th className="px-4 py-3">Type</th>
                        <th className="px-4 py-3">Status</th>
                        <th className="px-4 py-3">Priority</th>
                      </>
                      )}
                  {!isBmView && <th className="px-4 py-3">Open Days</th>}
                  <th className="px-4 py-3">Updated</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {serviceControls.map((serviceControl) => {
                  const isOverdue = !isBmView && (serviceControl.is_missing_service_id_overdue || serviceControl.is_missing_eta_overdue)

                  return (
                    <tr key={serviceControl.id} className={`border-t text-sm ${isOverdue ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-slate-200 text-slate-600'}`}>
                      {!isBmView && (
                        <>
                          <td className="px-4 py-4 align-top">
                            <div className={isOverdue ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>{serviceControl.order?.name ?? 'Order'}</div>
                            <div className={isOverdue ? 'text-xs text-rose-600' : 'text-xs text-slate-400'}>#{serviceControl.order?.order_number ?? 'N/A'}</div>
                            {isOverdue && (
                              <div className="mt-1 text-[11px] font-semibold text-rose-700">
                                {serviceControl.is_missing_service_id_overdue ? 'Missing Service ID over 5 days' : 'Missing ETA Date over 5 days'}
                              </div>
                            )}
                          </td>
                          <td className="px-4 py-4 align-top">
                            <div>{serviceControl.order?.client?.name ?? 'No client'}</div>
                            <div className={isOverdue ? 'text-xs text-rose-600' : 'text-xs text-slate-400'}>{serviceControl.order?.client?.phone ?? 'No phone'}</div>
                          </td>
                          <td className="px-4 py-4 align-top">
                            <div className={isOverdue ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>{serviceControl.service_name ?? 'N/A'}</div>
                            <div className={isOverdue ? 'text-xs text-rose-600' : 'text-xs text-slate-400'}>{serviceControl.service_id ?? 'No service ID'}</div>
                          </td>
                        </>
                      )}
                      {isBmView
                        ? (
                          <>
                            <td className="px-4 py-4 align-top">{serviceControl.order?.name ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">{serviceControl.order?.supervisor?.name ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">{serviceControl.bm_quantity ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">{serviceControl.bm_requested_date ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">{serviceControl.bm_picked_up_by ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">{serviceControl.bm_invoice_number ?? 'N/A'}</td>
                            <td className="px-4 py-4 align-top">
                              <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                {humanize(serviceControl.bm_invoice_status)}
                              </span>
                            </td>
                          </>
                          )
                        : (
                          <>
                            <td className="px-4 py-4 align-top">{humanizeList(serviceControl.service_type)}</td>
                            <td className="px-4 py-4 align-top">
                              <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                                {humanize(serviceControl.service_status)}
                              </span>
                            </td>
                            <td className="px-4 py-4 align-top">{humanize(serviceControl.priority)}</td>
                          </>
                          )}
                      {!isBmView && <td className="px-4 py-4 align-top">{serviceControl.open_days ?? 0}</td>}
                      <td className="px-4 py-4 align-top">
                        <div className="text-xs text-slate-400">{serviceControl.updated_at ? new Date(serviceControl.updated_at).toLocaleString() : 'N/A'}</div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div className="flex items-center gap-2">
                          <Link href={route('service-control.show', serviceControl.id)} title="View">
                            <EyeIcon />
                          </Link>
                          <Link href={route('service-control.edit', serviceControl.id)} title="Edit">
                            <EditIcon />
                          </Link>
                          <button type="button" title="Delete" onClick={() => { destroy(serviceControl) }}>
                            <DeleteIcon />
                          </button>
                        </div>
                      </td>
                    </tr>
                  )
                })}
                {serviceControls.length === 0 && (
                  <tr>
                    <td colSpan={isBmView ? 9 : 9} className="px-4 py-10 text-center text-sm text-slate-400">
                      No {isBmView ? 'BM records' : 'services'} found for the selected filters.
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
