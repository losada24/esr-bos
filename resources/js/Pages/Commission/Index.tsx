import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { type PageProps } from '@/types'
import { useEffect, useMemo, useState } from 'react'

interface CommissionRow {
  key: string
  order_id: number
  order_status: string
  order_name: string
  owner_names: string
  accounting_status: string
  accounting_status_date: string | null
  beneficiary_name: string | null
  beneficiary_relation: string | null
  commission_id: number | null
  commission_status: string | null
  commission_total: number
  paid_amount: number
  pending_amount: number
  next_payment_amount: number
  next_payment_status: string | null
  can_delete: boolean
}

interface ReviewPaymentRow {
  id: number
  order_id: number
  order_status: string
  order_name: string
  order_number: string | number | null
  invoice_number: string | null
  owner_names: string
  accounting_status: string
  accounting_status_date: string | null
  project_payment_method: string | null
  type_of_financing: string | null
  project_amount: number
  commission_id: number
  commission_status: string
  commission_total: number
  commission_fee: number
  commission_base: number
  commission_percentage: number | null
  commission_paid: number
  commission_pending: number
  beneficiary_name: string
  beneficiary_relation: string
  sequence: number
  payment_kind: string
  payment_status: string
  payment_amount: number
  payment_base_amount: number
  payment_other_cost_amount: number
  paid_at: string | null
  commission_period_id: number | null
}

interface PeriodOption {
  id: number
  label: string
  start_date: string
  end_date: string
}

interface OrderSearchResult {
  id: number
  name: string | null
  status: string | null
  client: string | null
  company: string | null
  owner: string | null
}

type CommissionFilterState = {
  view: 'commissions' | 'payments'
  status: string
  commission_status: string
  beneficiary: string
  start_date: string
  end_date: string
}

const FILTER_STORAGE_KEY = 'commission-report-filters'

type CommissionIndexProps = PageProps & {
  rows: CommissionRow[]
  reviewPayments: ReviewPaymentRow[]
  periods: PeriodOption[]
  totals: {
    orders: number
    commissions: number
    total_commission: number
    total_paid: number
    total_pending: number
  }
  selectedStatus: string | null
  availableStatuses: string[]
  selectedCommissionStatus: string | null
  availableCommissionStatuses: string[]
  selectedView: 'commissions' | 'payments'
  beneficiarySearch: string
  startDate: string
  endDate: string
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

function paymentKindLabel(paymentKind: string): string {
  return paymentKind === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular'
}

function buildFilterState(filters: CommissionFilterState): CommissionFilterState {
  return {
    view: filters.view === 'payments' ? 'payments' : 'commissions',
    status: filters.status ?? '',
    commission_status: filters.commission_status ?? '',
    beneficiary: filters.beneficiary ?? '',
    start_date: filters.start_date ?? '',
    end_date: filters.end_date ?? ''
  }
}

function readStoredFilters(): CommissionFilterState | null {
  if (typeof window === 'undefined') return null

  const raw = window.localStorage.getItem(FILTER_STORAGE_KEY)
  if (!raw) return null

  try {
    const parsed = JSON.parse(raw) as Partial<CommissionFilterState>

    return buildFilterState({
      view: parsed.view === 'payments' ? 'payments' : 'commissions',
      status: parsed.status ?? '',
      commission_status: parsed.commission_status ?? '',
      beneficiary: parsed.beneficiary ?? '',
      start_date: parsed.start_date ?? '',
      end_date: parsed.end_date ?? ''
    })
  } catch {
    window.localStorage.removeItem(FILTER_STORAGE_KEY)
    return null
  }
}

function storeFilters(filters: CommissionFilterState) {
  if (typeof window === 'undefined') return

  window.localStorage.setItem(FILTER_STORAGE_KEY, JSON.stringify(buildFilterState(filters)))
}

function clearStoredFilters() {
  if (typeof window === 'undefined') return

  window.localStorage.removeItem(FILTER_STORAGE_KEY)
}

function hasExplicitFilterParams(): boolean {
  if (typeof window === 'undefined') return false

  const params = new URLSearchParams(window.location.search)

  return ['status', 'commission_status', 'beneficiary', 'start_date', 'end_date'].some((key) => {
    const value = params.get(key)
    return value !== null && value !== ''
  })
}

function SearchAnyOrderPanel() {
  const [query, setQuery] = useState('')
  const [results, setResults] = useState<OrderSearchResult[]>([])
  const [loading, setLoading] = useState(false)
  const hasQuery = query.trim().length > 0

  useEffect(() => {
    const search = query.trim()

    if (search === '') {
      setResults([])
      setLoading(false)
      return
    }

    const controller = new AbortController()
    let ignore = false
    setLoading(true)

    const handle = window.setTimeout(async () => {
      try {
        const response = await fetch(route('order.search', {
          q: search,
          module: 'commissions',
          origin: 'commissions',
          limit: 8
        }), {
          headers: {
            Accept: 'application/json'
          },
          signal: controller.signal
        })

        if (!response.ok) {
          throw new Error('Order search request failed')
        }

        const payload = await response.json()
        if (ignore) return

        setResults(Array.isArray(payload?.results) ? payload.results : [])
      } catch (error) {
        if (!ignore && error instanceof Error && error.name !== 'AbortError') {
          console.error(error)
          setResults([])
        }
      } finally {
        if (!ignore) {
          setLoading(false)
        }
      }
    }, 250)

    return () => {
      ignore = true
      controller.abort()
      window.clearTimeout(handle)
    }
  }, [query])

  return (
    <div className="mb-6 rounded border bg-white p-4">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div>
          <h2 className="text-lg font-semibold">Search Any Order</h2>
          <p className="text-sm text-slate-500">
            Open any order here to create or manage commissions without changing the current report filters.
          </p>
        </div>
        <div className="rounded border bg-slate-50 px-3 py-2 text-sm text-slate-600">
          This search includes all order statuses.
        </div>
      </div>

      <div className="mt-4">
        <label htmlFor="order-search" className="mb-1 block font-semibold">Order Search</label>
        <input
          id="order-search"
          className="form-input"
          value={query}
          placeholder="Search by order name, order #, client, company, owner, or address"
          onChange={(event) => { setQuery(event.target.value) }}
        />
        <p className="mt-2 text-xs text-slate-500">
          Select a result to open that order in the commission manager.
        </p>
      </div>

      {hasQuery && (
        <div className="mt-4 overflow-hidden rounded border">
          {loading && (
            <div className="px-4 py-3 text-sm text-slate-500">Searching orders...</div>
          )}

          {!loading && results.length === 0 && (
            <div className="px-4 py-3 text-sm text-slate-500">No orders found.</div>
          )}

          {!loading && results.length > 0 && (
            <div className="divide-y">
              {results.map((result) => (
                <button
                  key={result.id}
                  type="button"
                  className="flex w-full items-start justify-between gap-4 px-4 py-3 text-left transition hover:bg-slate-50"
                  onClick={() => { router.visit(route('report.commissions.edit-order', result.id)) }}
                >
                  <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="font-semibold text-slate-800">{result.name ?? `Order #${result.id}`}</span>
                      <span className="rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-slate-600">
                        {result.status ?? 'Unknown'}
                      </span>
                    </div>
                    <div className="mt-1 text-sm text-slate-500">
                      Client: {result.client ?? 'No client'}
                    </div>
                    <div className="text-xs text-slate-500">
                      Company: {result.company ?? 'No company'} · Owner: {result.owner ?? 'No owner'}
                    </div>
                  </div>
                  <span className="shrink-0 rounded bg-primary px-3 py-1 text-sm font-semibold text-white">
                    Manage
                  </span>
                </button>
              ))}
            </div>
          )}
        </div>
      )}
    </div>
  )
}

export default function CommissionIndex ({
  auth,
  rows,
  reviewPayments,
  periods,
  totals,
  selectedStatus,
  availableStatuses,
  selectedCommissionStatus,
  availableCommissionStatuses,
  selectedView,
  beneficiarySearch,
  startDate,
  endDate
}: CommissionIndexProps) {
  const appliedFilters = useMemo(() => buildFilterState({
    view: selectedView ?? 'commissions',
    status: selectedStatus ?? '',
    commission_status: selectedCommissionStatus ?? '',
    beneficiary: beneficiarySearch ?? '',
    start_date: startDate,
    end_date: endDate
  }), [selectedView, selectedStatus, selectedCommissionStatus, beneficiarySearch, startDate, endDate])

  const [viewMode, setViewMode] = useState<'commissions' | 'payments'>(selectedView ?? 'commissions')

  const { data, setData } = useForm({
    ...appliedFilters
  })

  const bulkPayForm = useForm({
    commission_period_id: periods[0]?.id ?? 0,
    payment_ids: [] as number[]
  })

  const exportQuery = `?${new URLSearchParams({
    view: viewMode,
    status: data.status,
    commission_status: data.commission_status,
    beneficiary: data.beneficiary,
    start_date: data.start_date,
    end_date: data.end_date
  }).toString()}`

  const selectedPaymentIds = bulkPayForm.data.payment_ids
  const allVisiblePaymentsSelected = reviewPayments.length > 0 && reviewPayments.every((payment) => selectedPaymentIds.includes(payment.id))

  const selectedPayments = useMemo(
    () => reviewPayments.filter((payment) => selectedPaymentIds.includes(payment.id)),
    [reviewPayments, selectedPaymentIds]
  )

  const selectedPaymentsTotal = selectedPayments.reduce((sum, payment) => sum + payment.payment_amount, 0)

  useEffect(() => {
    const savedFilters = readStoredFilters()

    if (!hasExplicitFilterParams() && savedFilters) {
      router.get(route('report.commissions'), savedFilters, {
        replace: true,
        preserveScroll: true,
        preserveState: false
      })

      return
    }

    if (!hasExplicitFilterParams()) {
      return
    }

    storeFilters(appliedFilters)
  }, [appliedFilters])

  function togglePaymentSelection(paymentId: number) {
    if (selectedPaymentIds.includes(paymentId)) {
      bulkPayForm.setData('payment_ids', selectedPaymentIds.filter((id) => id !== paymentId))
      return
    }

    bulkPayForm.setData('payment_ids', [...selectedPaymentIds, paymentId])
  }

  function toggleSelectAllVisible() {
    if (allVisiblePaymentsSelected) {
      bulkPayForm.setData('payment_ids', [])
      return
    }

    bulkPayForm.setData('payment_ids', reviewPayments.map((payment) => payment.id))
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Commissions">
      <Head title="Commissions" />

      <SearchAnyOrderPanel />

      <form
        className="mb-6 grid gap-4 md:grid-cols-5"
        onSubmit={(event) => {
          event.preventDefault()
          const nextFilters = buildFilterState({ ...data, view: viewMode })
          storeFilters(nextFilters)
          router.get(route('report.commissions'), nextFilters, { preserveState: true })
        }}
      >
        <div>
          <label htmlFor="status" className="mb-1 block font-semibold">Accounting Status</label>
          <select
            id="status"
            className="form-select"
            value={data.status}
            onChange={(event) => { setData('status', event.target.value) }}
          >
            <option value="">All</option>
            {availableStatuses.map((status) => (
              <option key={status} value={status}>{status}</option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="commission_status" className="mb-1 block font-semibold">Commission Status</label>
          <select
            id="commission_status"
            className="form-select"
            value={data.commission_status}
            onChange={(event) => { setData('commission_status', event.target.value) }}
          >
            <option value="">All</option>
            {availableCommissionStatuses.map((status) => (
              <option key={status} value={status}>{status}</option>
            ))}
          </select>
        </div>

        <div>
          <label htmlFor="beneficiary" className="mb-1 block font-semibold">Beneficiary</label>
          <input
            id="beneficiary"
            className="form-input"
            value={data.beneficiary}
            onChange={(event) => { setData('beneficiary', event.target.value) }}
          />
        </div>

        <div>
          <label htmlFor="start_date" className="mb-1 block font-semibold">Start Date</label>
          <input
            id="start_date"
            type="date"
            className="form-input"
            value={data.start_date}
            onChange={(event) => { setData('start_date', event.target.value) }}
          />
        </div>

        <div>
          <label htmlFor="end_date" className="mb-1 block font-semibold">End Date</label>
          <input
            id="end_date"
            type="date"
            className="form-input"
            value={data.end_date}
            onChange={(event) => { setData('end_date', event.target.value) }}
          />
        </div>

        <div className="md:col-span-5 flex flex-wrap gap-2">
          <button type="submit" className="btn btn-primary">Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => {
              clearStoredFilters()
              router.get(route('report.commissions'), { view: viewMode })
            }}
          >
            Reset
          </button>
          <a
            className="btn btn-outline-primary"
            href={route('report.commissions.pdf') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a
            className="btn btn-outline-primary"
            href={route('report.commissions.excel') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            Export Excel
          </a>
          <Link className="btn btn-outline-primary" href={route('commission-periods.index')}>
            Commission Periods
          </Link>
        </div>
      </form>

      <div className="mb-4 grid gap-3 md:grid-cols-6">
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Orders</span><span className="text-xl font-semibold">{totals.orders}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Commissions</span><span className="text-xl font-semibold">{totals.commissions}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Total</span><span className="text-xl font-semibold">{formatCurrency(totals.total_commission)}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Paid</span><span className="text-xl font-semibold">{formatCurrency(totals.total_paid)}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Pending</span><span className="text-xl font-semibold">{formatCurrency(totals.total_pending)}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Review Payments</span><span className="text-xl font-semibold">{reviewPayments.length}</span></div>
      </div>

      <div className="mb-4 flex flex-wrap gap-2">
        <button
          type="button"
          className={`btn ${viewMode === 'commissions' ? 'btn-primary' : 'btn-outline-primary'}`}
          onClick={() => {
            setViewMode('commissions')
            setData('view', 'commissions')
            storeFilters({ ...appliedFilters, view: 'commissions' })
          }}
        >
          Commissions View
        </button>
        <button
          type="button"
          className={`btn ${viewMode === 'payments' ? 'btn-primary' : 'btn-outline-primary'}`}
          onClick={() => {
            setViewMode('payments')
            setData('view', 'payments')
            storeFilters({ ...appliedFilters, view: 'payments' })
          }}
        >
          Payments View
        </button>
      </div>

      {viewMode === 'commissions' && (
        <div className="table-responsive">
          <table className="table-auto w-full border-collapse">
            <thead className="bg-gray-100">
              <tr className="text-left">
                <th className="px-4 py-3">Accounting Status</th>
                <th className="px-4 py-3">Order Status</th>
                <th className="px-4 py-3">Order</th>
                <th className="px-4 py-3">Owners</th>
                <th className="px-4 py-3">Beneficiary</th>
                <th className="px-4 py-3">Relation</th>
                <th className="px-4 py-3">Commission Status</th>
                <th className="px-4 py-3">Next Payment</th>
                <th className="px-4 py-3">Paid</th>
                <th className="px-4 py-3">Pending</th>
                <th className="px-4 py-3">Total</th>
                <th className="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              {rows.length === 0 && (
                <tr>
                  <td className="border-t px-4 py-4 text-center" colSpan={12}>No commissions found.</td>
                </tr>
              )}
              {rows.map((row) => (
                <tr key={row.key}>
                  <td className="border-t px-4 py-4">
                    <div>{row.accounting_status}</div>
                    <div className="text-xs text-slate-500">{row.accounting_status_date ?? '-'}</div>
                  </td>
                  <td className="border-t px-4 py-4">{row.order_status}</td>
                  <td className="border-t px-4 py-4">{row.order_name}</td>
                  <td className="border-t px-4 py-4">{row.owner_names || '-'}</td>
                  <td className="border-t px-4 py-4">{row.beneficiary_name ?? 'No commission yet'}</td>
                  <td className="border-t px-4 py-4">{row.beneficiary_relation ?? '-'}</td>
                  <td className="border-t px-4 py-4">{row.commission_status ?? '-'}</td>
                  <td className="border-t px-4 py-4">
                    {row.next_payment_status ? `${row.next_payment_status} · ${formatCurrency(row.next_payment_amount)}` : '-'}
                  </td>
                  <td className="border-t px-4 py-4">{formatCurrency(row.paid_amount)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(row.pending_amount)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(row.commission_total)}</td>
                  <td className="border-t px-4 py-4">
                    <div className="flex flex-wrap gap-2">
                      <Link className="btn btn-sm btn-primary" href={route('report.commissions.edit-order', row.order_id)}>
                        Manage
                      </Link>
                      {row.commission_id !== null && row.can_delete && (
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-danger"
                          onClick={() => {
                            if (!window.confirm(`Delete commission for ${row.beneficiary_name ?? 'this beneficiary'}?`)) {
                              return
                            }

                            router.delete(route('report.commissions.destroy', row.commission_id!), {
                              preserveScroll: true,
                              preserveState: true
                            })
                          }}
                        >
                          Delete
                        </button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {viewMode === 'payments' && (
        <div className="space-y-4">
          <div className="rounded border p-4">
            <div className="mb-4 flex flex-wrap items-end gap-4">
              <div className="min-w-72">
                <label className="mb-1 block font-semibold">Commission Period</label>
                <select
                  className="form-select"
                  value={bulkPayForm.data.commission_period_id}
                  onChange={(event) => { bulkPayForm.setData('commission_period_id', Number(event.target.value)) }}
                >
                  <option value={0}>Select period</option>
                  {periods.map((period) => (
                    <option key={period.id} value={period.id}>
                      {period.label} ({period.start_date} to {period.end_date})
                    </option>
                  ))}
                </select>
              </div>

              <div className="rounded border bg-slate-50 px-3 py-2 text-sm">
                <div><span className="font-semibold">Selected payments:</span> {selectedPaymentIds.length}</div>
                <div><span className="font-semibold">Selected total:</span> {formatCurrency(selectedPaymentsTotal)}</div>
              </div>

              <button
                type="button"
                className="btn btn-primary"
                disabled={bulkPayForm.data.commission_period_id === 0 || selectedPaymentIds.length === 0 || bulkPayForm.processing}
                onClick={() => {
                  bulkPayForm.post(route('report.commissions.payments.bulk-pay'), {
                    preserveScroll: true
                  })
                }}
              >
                Add Selected Review Payments To Period
              </button>
            </div>

            {periods.length === 0 && (
              <div className="rounded border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                No open commission periods available. Create one first in <Link className="underline" href={route('commission-periods.index')}>Commission Periods</Link>.
              </div>
            )}

            <p className="text-sm text-slate-500">
              This view only lists payments currently in <span className="font-semibold">REVIEW</span> and not yet assigned to a period. Select one, many, or all visible payments to add them to an open period. They will stay in <span className="font-semibold">REVIEW</span> until that period is closed.
            </p>
          </div>

          <div className="table-responsive">
            <table className="table-auto w-full border-collapse">
              <thead className="bg-gray-100">
                <tr className="text-left">
                  <th className="px-4 py-3">
                    <input
                      type="checkbox"
                      checked={allVisiblePaymentsSelected}
                      onChange={() => { toggleSelectAllVisible() }}
                    />
                  </th>
                  <th className="px-4 py-3">Accounting Status</th>
                  <th className="px-4 py-3">Order</th>
                  <th className="px-4 py-3">Commission</th>
                  <th className="px-4 py-3">#Invoice</th>
                  <th className="px-4 py-3">Beneficiary</th>
                  <th className="px-4 py-3">Project Payment Method</th>
                  <th className="px-4 py-3">Type Of Financing</th>
                  <th className="px-4 py-3">Project Amount</th>
                  <th className="px-4 py-3">Commission Fee</th>
                  <th className="px-4 py-3">Base</th>
                  <th className="px-4 py-3">Percentage</th>
                  <th className="px-4 py-3">Total Commission</th>
                  <th className="px-4 py-3">Pending</th>
                  <th className="px-4 py-3">Payment Type</th>
                  <th className="px-4 py-3">Payment</th>
                  <th className="px-4 py-3">Other Cost</th>
                  <th className="px-4 py-3">Total Payment</th>
                  <th className="px-4 py-3">Payment Status</th>
                  <th className="px-4 py-3">Actions</th>
                </tr>
              </thead>
              <tbody>
                {reviewPayments.length === 0 && (
                  <tr>
                    <td className="border-t px-4 py-4 text-center" colSpan={20}>No review payments found with the current filters.</td>
                  </tr>
                )}

                {reviewPayments.map((payment) => (
                  <tr key={payment.id}>
                    <td className="border-t px-4 py-4">
                      <input
                        type="checkbox"
                        checked={selectedPaymentIds.includes(payment.id)}
                        onChange={() => { togglePaymentSelection(payment.id) }}
                      />
                    </td>
                    <td className="border-t px-4 py-4">
                      <div>{payment.accounting_status}</div>
                      <div className="text-xs text-slate-500">{payment.accounting_status_date ?? '-'}</div>
                    </td>
                    <td className="border-t px-4 py-4">
                      <div className="font-semibold">{payment.order_name}</div>
                      <div className="text-xs text-slate-500">{payment.order_status} · {payment.owner_names || '-'}</div>
                    </td>
                    <td className="border-t px-4 py-4">
                      <div>#{payment.commission_id}</div>
                      <div className="text-xs text-slate-500">{payment.commission_status}</div>
                    </td>
                    <td className="border-t px-4 py-4">
                      {payment.invoice_number || (payment.order_number ? `#${payment.order_number}` : '-')}
                    </td>
                    <td className="border-t px-4 py-4">
                      <div>{payment.beneficiary_name}</div>
                      <div className="text-xs text-slate-500">{payment.beneficiary_relation}</div>
                    </td>
                    <td className="border-t px-4 py-4">{payment.project_payment_method ?? '-'}</td>
                    <td className="border-t px-4 py-4">{payment.type_of_financing ?? '-'}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.project_amount)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.commission_fee)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.commission_base)}</td>
                    <td className="border-t px-4 py-4">{payment.commission_percentage !== null ? `${payment.commission_percentage}%` : '-'}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.commission_total)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.commission_pending)}</td>
                    <td className="border-t px-4 py-4">{paymentKindLabel(payment.payment_kind)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.payment_base_amount)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.payment_other_cost_amount)}</td>
                    <td className="border-t px-4 py-4">{formatCurrency(payment.payment_amount)}</td>
                    <td className="border-t px-4 py-4">{payment.payment_status}</td>
                    <td className="border-t px-4 py-4">
                      <Link className="btn btn-sm btn-outline-primary" href={route('report.commissions.edit-order', payment.order_id)}>
                        Manage
                      </Link>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      )}
    </AuthenticatedLayout>
  )
}
