import { Head, Link, router } from '@inertiajs/react'
import { useEffect, useMemo, useState } from 'react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import EditIcon from '@/Components/Icons/EditIcon'
import EyeIcon from '@/Components/Icons/EyeIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import { type PageProps, type ServiceControl } from '@/types'

type IndexProps = PageProps & {
  serviceControls: ServiceControl[]
  filters: {
    search?: string
    status?: string
    priority?: string
    service_type?: string
    type?: string
  }
  serviceTypeOptions: string[]
  serviceStatusOptions: string[]
  priorityOptions: string[]
}

type ExternalServiceOrder = {
  order_id?: string | number | null
  order_number?: string | number | null
  name?: string | null
  amount?: string | number | null
  company?: {
    name?: string | null
    email?: string | null
    phone?: string | null
    bos_id?: number | null
    exists_in_bos?: boolean
  } | null
  client?: {
    name?: string | null
    email?: string | null
    phone?: string | null
  } | null
  account_manager?: {
    name?: string | null
    email?: string | null
  } | null
}

type ServiceControlFilterState = {
  type: 'services' | 'quotes'
  search: string
  status: string
  priority: string
  service_type: string
}

type CreationOrigin = 'ESR' | 'ESW'

const FILTER_STORAGE_KEY = 'service-control-filters'
const BOS_ORDER_MODULES = [
  { value: 'service_control', label: 'BOS Orders' },
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

const formatCurrency = (value?: string | number | null): string => {
  const numeric = typeof value === 'number' ? value : Number(value)

  return Number.isFinite(numeric)
    ? numeric.toLocaleString('en-US', { style: 'currency', currency: 'USD' })
    : 'N/A'
}

const ETA_ALERT_THRESHOLDS: Record<string, number> = {
  ACCESSORIES: 2,
  COVERS: 2,
  MUNTINS: 2,
  GLASS: 7,
  SCREENS: 7,
  FABRICATION: 7,
  'PANEL FABRICATION': 7,
  'SERVICE MAN': 7,
  DELIVERY: 3,
}

const parseDateOnly = (value?: string | null): Date | null => {
  if (!value) return null
  const [year, month, day] = value.split('-').map(Number)
  if (!year || !month || !day) return null

  return new Date(year, month - 1, day)
}

const daysUntilDate = (value?: string | null): number | null => {
  const date = parseDateOnly(value)
  if (!date) return null

  const today = new Date()
  today.setHours(0, 0, 0, 0)
  date.setHours(0, 0, 0, 0)

  return Math.floor((date.getTime() - today.getTime()) / 86400000)
}

const shouldHighlightEtaAlert = (serviceControl: ServiceControl): boolean => {
  const daysUntilEta = daysUntilDate(serviceControl.eta_date)
  if (daysUntilEta === null || daysUntilEta < 0) return false

  const types = Array.isArray(serviceControl.service_type)
    ? serviceControl.service_type
    : (serviceControl.service_type ? [serviceControl.service_type] : [])

  return types.some((type) => {
    const threshold = ETA_ALERT_THRESHOLDS[String(type).trim().toUpperCase()]

    return threshold !== undefined && daysUntilEta <= threshold
  })
}

const urgencyDotClass = (urgencyStatus?: string | null): string => {
  const normalized = String(urgencyStatus ?? '').trim().toLowerCase()

  if (normalized === '') return 'bg-slate-300'
  if (normalized.startsWith('overdue') || normalized === 'due today') return 'bg-red-500'
  if (normalized.startsWith('due in') || normalized.startsWith('resolved')) return 'bg-green-500'

  return 'bg-slate-300'
}

const orderNumberForServiceControl = (serviceControl: ServiceControl): string | number => {
  const source = String(serviceControl.service_source ?? 'ESR').toUpperCase()

  if (source === 'ESR') {
    return serviceControl.external_order_id ?? 'N/A'
  }

  return serviceControl.order?.parent_order?.order_number ?? serviceControl.order?.order_number ?? 'N/A'
}

function buildFilterState (filters: ServiceControlFilterState): ServiceControlFilterState {
  const type = filters.type === 'quotes' ? 'quotes' : 'services'

  return {
    type,
    search: filters.search ?? '',
    status: filters.status ?? '',
    priority: filters.priority ?? '',
    service_type: filters.service_type ?? ''
  }
}

function readStoredFilters (): ServiceControlFilterState | null {
  if (typeof window === 'undefined') return null

  const raw = window.localStorage.getItem(FILTER_STORAGE_KEY)
  if (!raw) return null

  try {
    const parsed = JSON.parse(raw) as Partial<ServiceControlFilterState>
    return buildFilterState({
      type: parsed.type === 'quotes' ? 'quotes' : 'services',
      search: parsed.search ?? '',
      status: parsed.status ?? '',
      priority: parsed.priority ?? '',
      service_type: parsed.service_type ?? ''
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

  return ['type', 'search', 'status', 'priority', 'service_type'].some((key) => {
    const value = params.get(key)
    return value !== null && value !== ''
  })
}

export default function Index ({
  auth,
  serviceControls,
  filters,
  serviceTypeOptions,
  serviceStatusOptions,
  priorityOptions,
}: IndexProps) {
  const [search, setSearch] = useState(filters.search ?? '')
  const [status, setStatus] = useState(filters.status ?? '')
  const [priority, setPriority] = useState(filters.priority ?? '')
  const [serviceType, setServiceType] = useState(filters.service_type ?? '')
  const [externalSearch, setExternalSearch] = useState('')
  const [externalResults, setExternalResults] = useState<ExternalServiceOrder[]>([])
  const [externalLoading, setExternalLoading] = useState(false)
  const [externalSearched, setExternalSearched] = useState(false)
  const [externalBlockMessage, setExternalBlockMessage] = useState('')
  const [creationOrigin, setCreationOrigin] = useState<CreationOrigin>('ESR')
  const activeType = filters.type === 'quotes' ? 'quotes' : 'services'
  const appliedFilters = useMemo(() => buildFilterState({
    type: activeType,
    search: filters.search ?? '',
    status: filters.status ?? '',
    priority: filters.priority ?? '',
    service_type: filters.service_type ?? ''
  }), [activeType, filters.search, filters.status, filters.priority, filters.service_type])
  const exportQuery = `?${new URLSearchParams({
    type: activeType,
    search,
    status,
    priority,
    service_type: serviceType
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

  const visitType = (type: 'services' | 'quotes') => {
    const nextFilters = buildFilterState({
      type,
      search,
      status,
      priority,
      service_type: serviceType
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

  const changeCreationOrigin = (origin: CreationOrigin) => {
    setCreationOrigin(origin)
    setExternalSearch('')
    setExternalResults([])
    setExternalSearched(false)
  }

  const externalServiceCreateParams = (order: ExternalServiceOrder) => ({
    type: 'services',
    external_order_id: order.order_id ?? '',
    external_order_number: order.order_number ?? '',
    external_order_name: order.name ?? '',
    external_amount: order.amount ?? '',
    external_company_name: order.company?.name ?? '',
    external_company_email: order.company?.email ?? '',
    external_company_phone: order.company?.phone ?? '',
    external_client_name: order.client?.name ?? '',
    external_client_email: order.client?.email ?? '',
    external_client_phone: order.client?.phone ?? '',
    external_owner_name: order.account_manager?.name ?? '',
    external_owner_email: order.account_manager?.email ?? '',
  })

  const companyMissingMessage = (order: ExternalServiceOrder) => {
    const companyName = order.company?.name?.trim() || 'The company from ESR'

    return `${companyName} does not exist in BOS. Create the company before continuing.`
  }

  const canCreateExternalService = (order: ExternalServiceOrder) => {
    return Boolean(order.company?.exists_in_bos)
  }

  const openExternalService = (order: ExternalServiceOrder) => {
    if (!canCreateExternalService(order)) {
      setExternalBlockMessage(companyMissingMessage(order))
      setExternalSearched(true)
      return
    }

    setExternalBlockMessage('')
    router.visit(route('service-control.create', externalServiceCreateParams(order)))
  }

  const searchExternalServices = async () => {
    const text = externalSearch.trim()

    if (text === '') {
      setExternalResults([])
      setExternalSearched(false)
      setExternalBlockMessage('')
      return
    }

    setExternalLoading(true)
    setExternalBlockMessage('')

    try {
      const response = await fetch(`${route('service-control.external-service-orders.search')}?${new URLSearchParams({ search: text }).toString()}`, {
        headers: {
          Accept: 'application/json',
        },
      })
      const payload = await response.json().catch(() => ({}))
      const results = Array.isArray(payload.results) ? payload.results : []

      if (results.length === 1) {
        openExternalService(results[0] as ExternalServiceOrder)
        return
      }

      setExternalResults(results)
      setExternalSearched(true)
    } catch {
      setExternalResults([])
      setExternalSearched(true)
    } finally {
      setExternalLoading(false)
    }
  }

  const destroy = (serviceControl: ServiceControl) => {
    if (!window.confirm('Are you sure you want to delete this service control?')) return

    router.delete(route('service-control.destroy', serviceControl.id), {
      preserveScroll: true,
    })
  }

  const selectBosOrderForEsw = (orderId: number) => {
    router.visit(route('service-control.create', {
      order_id: orderId,
      type: 'services',
      service_source: 'ESW',
    }))
  }

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      pageTitle="Service Control"
      actions={
        <div className="flex flex-wrap items-center justify-end gap-2">
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
              <option key={option} value={option}>{option}</option>
            ))}
          </select>
          <select value={priority} onChange={(event) => { setPriority(event.target.value) }} className="form-select w-40">
            <option value="">All priorities</option>
            {priorityOptions.map((option) => (
              <option key={option} value={option}>{humanize(option)}</option>
            ))}
          </select>
          <select value={serviceType} onChange={(event) => { setServiceType(event.target.value) }} className="form-select w-44">
            <option value="">All service types</option>
            {serviceTypeOptions.map((option) => (
              <option key={option} value={option}>{humanize(option)}</option>
            ))}
          </select>
          <button type="button" className="btn btn-primary" onClick={applyFilters}>Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => {
              clearStoredFilters()
              setSearch('')
              setStatus('')
              setPriority('')
              setServiceType('')
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
        </div>
      }
    >
      <Head title="Service Control" />

      <div className="space-y-6">
        <div className="panel">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div className="grid flex-1 gap-3 lg:grid-cols-[160px_minmax(260px,1fr)_auto]">
              <div>
                <label htmlFor="creation_origin" className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Origin</label>
                <select
                  id="creation_origin"
                  value={creationOrigin}
                  onChange={(event) => { changeCreationOrigin(event.target.value as CreationOrigin) }}
                  className="form-select"
                >
                  <option value="ESR">ESR</option>
                  <option value="ESW">ESW</option>
                </select>
              </div>

              {creationOrigin === 'ESR'
                ? (
                  <>
                    <div>
                      <label htmlFor="external_service_search" className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">ESR Service Order</label>
                      <input
                        id="external_service_search"
                        type="text"
                        value={externalSearch}
                        onChange={(event) => { setExternalSearch(event.target.value) }}
                        onKeyDown={(event) => {
                          if (event.key === 'Enter') {
                            event.preventDefault()
                            void searchExternalServices()
                          }
                        }}
                        placeholder="Search ESR service orders..."
                        className="form-input"
                      />
                    </div>
                    <button type="button" className="btn btn-primary self-end" onClick={() => { void searchExternalServices() }}>
                      {externalLoading ? 'Searching...' : 'Search'}
                    </button>
                  </>
                  )
                : (
                  <div className="lg:col-span-2">
                    <label className="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">BOS Order</label>
                    <OrderGlobalSearch
                      origin="service_control"
                      modules={BOS_ORDER_MODULES}
                      defaultModule="service_control"
                      className="w-full"
                      onSelectOrder={selectBosOrderForEsw}
                    />
                  </div>
                  )}
            </div>

            <Link href={route('service-control.create', { type: 'services', service_source: creationOrigin })} className="btn btn-outline-primary whitespace-nowrap">
              New Service
            </Link>
          </div>
        </div>

        {activeType === 'services' && creationOrigin === 'ESR' && externalSearched && (
          <div className="panel">
            <div className="mb-4 flex items-center justify-between gap-3">
              <div>
                <h2 className="text-base font-semibold text-slate-800">External ESR Service Orders</h2>
                <p className="text-sm text-slate-400">Orders from ESR matching the search and marked as service.</p>
              </div>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                {externalResults.length} items
              </span>
            </div>
            <div className="table-responsive">
              <table className="w-full whitespace-nowrap">
                <thead>
                  <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <th className="px-4 py-3">Order</th>
                    <th className="px-4 py-3">Company</th>
                    <th className="px-4 py-3">Account Manager</th>
                    <th className="px-4 py-3">Amount</th>
                    <th className="px-4 py-3">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {externalResults.map((order, index) => (
                    <tr key={`${order.order_number ?? 'external'}-${index}`} className="border-t border-slate-200 text-sm text-slate-600">
                      <td className="px-4 py-4 align-top">
                        <div className="font-semibold text-slate-700">{order.name ?? 'N/A'}</div>
                        <div className="text-xs text-slate-400">#{order.order_number ?? 'N/A'}</div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div>{order.company?.name ?? 'N/A'}</div>
                        <div className="text-xs text-slate-400">{order.company?.email ?? order.company?.phone ?? 'No contact'}</div>
                        {order.company?.exists_in_bos === false && (
                          <div className="mt-1 text-xs font-semibold text-rose-600">Not created in BOS</div>
                        )}
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div>{order.account_manager?.name ?? 'N/A'}</div>
                        <div className="text-xs text-slate-400">{order.account_manager?.email ?? 'No email'}</div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        {formatCurrency(order.amount)}
                      </td>
                      <td className="px-4 py-4 align-top">
                        <button
                          type="button"
                          onClick={() => { openExternalService(order) }}
                          className={`btn btn-sm ${canCreateExternalService(order) ? 'btn-primary' : 'btn-outline-danger'}`}
                        >
                          {canCreateExternalService(order) ? 'Create Service' : 'Company Required'}
                        </button>
                      </td>
                    </tr>
                  ))}
                  {externalResults.length === 0 && (
                    <tr>
                      <td colSpan={5} className="px-4 py-8 text-center text-sm text-slate-400">
                        No external ESR service orders found for this search.
                      </td>
                    </tr>
                  )}
                </tbody>
              </table>
            </div>
            {externalBlockMessage && (
              <div className="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-700">
                {externalBlockMessage}
              </div>
            )}
          </div>
        )}

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
            onClick={() => { visitType('quotes') }}
            className={activeType === 'quotes' ? 'btn btn-primary' : 'btn btn-outline-primary'}
          >
            Quotes
          </button>
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold text-slate-800">{activeType === 'quotes' ? 'Recent Quotes' : 'Recent Services'}</h2>
              <p className="text-sm text-slate-400">{activeType === 'quotes' ? 'Service requests created by owners from ESR.' : 'Latest operational service records created from Service Control.'}</p>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
              {serviceControls.length} items
            </span>
          </div>

          <div className="table-responsive">
            <table className="w-full whitespace-nowrap">
              <thead>
                <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                  <th className="px-4 py-3">Order #</th>
                  <th className="px-4 py-3">Service/Quote #</th>
                  <th className="px-4 py-3">Origin</th>
                  <th className="px-4 py-3">Service Name</th>
                  <th className="px-4 py-3">Company</th>
                  <th className="px-4 py-3">Client</th>
                  <th className="px-4 py-3">Owner</th>
                  <th className="px-4 py-3">Service Type</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Priority</th>
                  <th className="px-4 py-3">ETA</th>
                  <th className="px-4 py-3">Production Output Date</th>
                  <th className="px-4 py-3">Urgency Status</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {serviceControls.map((serviceControl) => {
                  const isOverdue = serviceControl.is_missing_service_id_overdue || serviceControl.is_missing_eta_overdue
                  const hasEtaAlert = shouldHighlightEtaAlert(serviceControl)
                  const isCompleted = String(serviceControl.service_status ?? '').trim().toUpperCase() === 'COMPLETED'
                  const showMissingAlert = isOverdue && !hasEtaAlert
                  const rowClassName = hasEtaAlert
                    ? 'border-amber-200 bg-amber-50 text-amber-950'
                    : (showMissingAlert
                        ? 'border-rose-200 bg-rose-50 text-rose-900'
                        : (isCompleted ? 'border-emerald-200 bg-emerald-50 text-emerald-950' : 'border-slate-200 text-slate-600'))

                  return (
                    <tr key={serviceControl.id} className={`border-t text-sm ${rowClassName}`}>
                      <td className="px-4 py-4 align-top">
                        <div className={showMissingAlert ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>
                          {orderNumberForServiceControl(serviceControl)}
                        </div>
                        {showMissingAlert && (
                          <div className="mt-1 text-[11px] font-semibold text-rose-700">
                            {serviceControl.is_missing_service_id_overdue ? 'Missing Service ID over 5 days' : 'Missing ETA Date over 5 days'}
                          </div>
                        )}
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div className={showMissingAlert ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>
                          {serviceControl.service_id ?? 'N/A'}
                        </div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        <span className="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                          {serviceControl.service_source ?? 'ESR'}
                        </span>
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div className={showMissingAlert ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>
                          {serviceControl.service_name ?? 'N/A'}
                        </div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        {serviceControl.order?.company?.name ?? 'N/A'}
                      </td>
                      <td className="px-4 py-4 align-top">
                        <div>{serviceControl.order?.client?.name ?? serviceControl.client?.name ?? 'No client'}</div>
                        <div className={showMissingAlert ? 'text-xs text-rose-600' : 'text-xs text-slate-400'}>
                          {serviceControl.order?.client?.phone ?? serviceControl.client?.phone ?? 'No phone'}
                        </div>
                      </td>
                      <td className="px-4 py-4 align-top">
                        {(serviceControl.order?.owners ?? []).length > 0
                          ? serviceControl.order?.owners?.map((owner) => owner.name).join(', ')
                          : (serviceControl.order?.seller?.name ?? 'N/A')}
                      </td>
                      <td className="px-4 py-4 align-top">{humanizeList(serviceControl.service_type)}</td>
                      <td className="px-4 py-4 align-top">
                        <span className="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500">
                          {serviceControl.service_status}
                        </span>
                      </td>
                      <td className="px-4 py-4 align-top">{humanize(serviceControl.priority)}</td>
                      <td className="px-4 py-4 align-top">{serviceControl.eta_date ?? 'N/A'}</td>
                      <td className="px-4 py-4 align-top">{serviceControl.parts_received_date ?? 'N/A'}</td>
                      <td className="px-4 py-4 align-top">
                        <span className="inline-flex items-center gap-2 whitespace-normal text-xs font-semibold text-slate-600">
                          <span className={`h-3 w-3 shrink-0 rounded-full ${urgencyDotClass(serviceControl.urgency_status)}`} />
                          <span>{serviceControl.urgency_status ?? 'N/A'}</span>
                        </span>
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
                    <td colSpan={14} className="px-4 py-10 text-center text-sm text-slate-400">
                      No {activeType === 'quotes' ? 'quotes' : 'services'} found for the selected filters.
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
