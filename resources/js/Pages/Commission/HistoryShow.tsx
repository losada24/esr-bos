import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import Pagination from '@/Components/Pagination'
import { Head, Link, router, useForm } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type PageProps, type PaginatorLink } from '@/types'

type AuditRow = {
  id: number
  changed_at?: string | null
  user_name: string
  action: string
  action_label: string
  target_label: string
  order_id?: number | null
  order_name?: string | null
  beneficiary_name?: string | null
  period_label?: string | null
  source: string
  summary: string
  details: Array<{
    field: string
    before: string
    after: string
  }>
  advanced_details: Array<{
    field: string
    before: string
    after: string
  }>
}

type PeriodOption = {
  id: number
  label: string
  status: string
}

type HistoryFilters = {
  search: string
  order: string
  period_id?: number | null
  user: string
  action: string
  start_date: string
  end_date: string
}

type CommissionHistoryShowProps = PageProps & {
  commission: {
    id: number
    status: string
    beneficiary_name?: string | null
    beneficiary_relation?: string | null
    commission_total: number
    paid_amount: number
    pending_amount: number
    order_id?: number | null
    order_name?: string | null
    order_number?: string | number | null
    invoice_number?: string | null
    order_status?: string | null
    payments_count: number
  }
  audits: {
    data: AuditRow[]
    links: PaginatorLink[]
  }
  periods: PeriodOption[]
  filters: HistoryFilters
  availableActions: string[]
  totals: {
    events: number
  }
}

function formatDateForPicker(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

function buildQueryString(filters: Record<string, string>): string {
  const params = new URLSearchParams()

  Object.entries(filters).forEach(([key, value]) => {
    if (value !== '') {
      params.set(key, value)
    }
  })

  const queryString = params.toString()

  return queryString !== '' ? `?${queryString}` : ''
}

export default function CommissionHistoryShow ({
  auth,
  commission,
  audits,
  periods,
  filters,
  availableActions,
  totals
}: CommissionHistoryShowProps) {
  const { data, setData } = useForm({
    search: filters.search,
    order: filters.order,
    period_id: filters.period_id ? String(filters.period_id) : '',
    user: filters.user,
    action: filters.action,
    start_date: filters.start_date,
    end_date: filters.end_date
  })

  const appliedQueryString = buildQueryString({
    search: filters.search,
    order: filters.order,
    period_id: filters.period_id ? String(filters.period_id) : '',
    user: filters.user,
    action: filters.action,
    start_date: filters.start_date,
    end_date: filters.end_date
  })

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={`Commission #${commission.id} History`}
      actions={
        <Link className="btn btn-outline-primary" href={`${route('report.commissions.history')}${appliedQueryString}`}>
          Back To History
        </Link>
      }
    >
      <Head title={`Commission #${commission.id} History`} />

      <div className="mb-6 rounded border bg-white p-4">
        <div className="grid gap-3 md:grid-cols-4">
          <div>
            <span className="block text-sm text-slate-500">Commission</span>
            <span className="font-semibold">#{commission.id}</span>
            <div className="text-xs text-slate-500">{commission.status}</div>
          </div>
          <div>
            <span className="block text-sm text-slate-500">Beneficiary</span>
            <span className="font-semibold">{commission.beneficiary_name ?? '-'}</span>
            <div className="text-xs text-slate-500">{commission.beneficiary_relation ?? '-'}</div>
          </div>
          <div>
            <span className="block text-sm text-slate-500">Order</span>
            {commission.order_id ? (
              <Link className="font-semibold text-primary underline" href={route('report.commissions.edit-order', commission.order_id)}>
                {commission.order_name ?? `Order #${commission.order_id}`}
              </Link>
            ) : (
              <span className="font-semibold">-</span>
            )}
            <div className="text-xs text-slate-500">
              {commission.invoice_number || commission.order_number
                ? `#${commission.invoice_number ?? commission.order_number}`
                : commission.order_status ?? '-'}
            </div>
          </div>
          <div>
            <span className="block text-sm text-slate-500">Amounts</span>
            <div className="font-semibold">Total: {formatCurrency(commission.commission_total)}</div>
            <div className="text-xs text-slate-500">Paid: {formatCurrency(commission.paid_amount)} | Pending: {formatCurrency(commission.pending_amount)}</div>
            <div className="text-xs text-slate-500">Payments: {commission.payments_count}</div>
          </div>
        </div>
      </div>

      <form
        className="mb-6 grid gap-4 md:grid-cols-6"
        onSubmit={(event) => {
          event.preventDefault()
          router.get(route('report.commissions.history.show', commission.id), {
            ...data,
            period_id: data.period_id || undefined
          }, { preserveState: true })
        }}
      >
        <div>
          <label className="mb-1 block font-semibold">Search</label>
          <input
            className="form-input"
            value={data.search}
            onChange={(event) => { setData('search', event.target.value) }}
            placeholder="Beneficiary, action, period"
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">Order</label>
          <input
            className="form-input"
            value={data.order}
            onChange={(event) => { setData('order', event.target.value) }}
            placeholder="Order name, #, invoice"
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">Period</label>
          <select className="form-select" value={data.period_id} onChange={(event) => { setData('period_id', event.target.value) }}>
            <option value="">All</option>
            {periods.map((period) => (
              <option key={period.id} value={period.id}>{period.label} ({period.status})</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block font-semibold">User</label>
          <input
            className="form-input"
            value={data.user}
            onChange={(event) => { setData('user', event.target.value) }}
            placeholder="User name"
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">Action</label>
          <select className="form-select" value={data.action} onChange={(event) => { setData('action', event.target.value) }}>
            <option value="">All</option>
            {availableActions.map((action) => (
              <option key={action} value={action}>{action}</option>
            ))}
          </select>
        </div>
        <div>
          <label className="mb-1 block font-semibold">Start Date</label>
          <Flatpickr
            options={{ mode: 'single', dateFormat: 'Y-m-d', position: 'auto right' }}
            value={data.start_date || undefined}
            className="form-input"
            onChange={([date]) => { setData('start_date', date ? formatDateForPicker(date) : '') }}
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">End Date</label>
          <Flatpickr
            options={{ mode: 'single', dateFormat: 'Y-m-d', position: 'auto right' }}
            value={data.end_date || undefined}
            className="form-input"
            onChange={([date]) => { setData('end_date', date ? formatDateForPicker(date) : '') }}
          />
        </div>

        <div className="md:col-span-5 flex flex-wrap gap-2">
          <button type="submit" className="btn btn-primary">Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => { router.get(route('report.commissions.history.show', commission.id)) }}
          >
            Reset
          </button>
        </div>
      </form>

      <div className="mb-4 rounded border bg-white p-4 text-sm">
        <span className="font-semibold">Events for this commission:</span> {totals.events}
      </div>

      <div className="table-responsive rounded border bg-white">
        <table className="table-auto w-full">
          <thead className="bg-gray-100">
            <tr className="text-left">
              <th className="px-4 py-3">Date</th>
              <th className="px-4 py-3">User</th>
              <th className="px-4 py-3">Action</th>
              <th className="px-4 py-3">Target</th>
              <th className="px-4 py-3">Period</th>
              <th className="px-4 py-3">Summary</th>
            </tr>
          </thead>
          <tbody>
            {audits.data.length === 0 && (
              <tr>
                <td className="border-t px-4 py-4 text-center text-slate-500" colSpan={6}>
                  No audit events found for this commission with the selected filters.
                </td>
              </tr>
            )}
            {audits.data.map((audit) => (
              <tr key={audit.id}>
                <td className="border-t px-4 py-4 align-top">{audit.changed_at ?? '-'}</td>
                <td className="border-t px-4 py-4 align-top">{audit.user_name}</td>
                <td className="border-t px-4 py-4 align-top">{audit.action_label}</td>
                <td className="border-t px-4 py-4 align-top">{audit.target_label}</td>
                <td className="border-t px-4 py-4 align-top">{audit.period_label ?? '-'}</td>
                <td className="border-t px-4 py-4 align-top">
                  <div className="font-medium">{audit.summary}</div>
                  {audit.details.length > 0 && (
                    <details className="mt-2 text-sm text-slate-600">
                      <summary className="cursor-pointer">Details</summary>
                      <div className="mt-2 space-y-1">
                        {audit.details.map((detail) => (
                          <div key={`${audit.id}-${detail.field}`}>
                            <span className="font-semibold">{detail.field}:</span> {detail.before} {'->'} {detail.after}
                          </div>
                        ))}
                      </div>
                    </details>
                  )}
                  {audit.advanced_details.length > 0 && (
                    <details className="mt-2 text-sm text-slate-500">
                      <summary className="cursor-pointer">Advanced Details</summary>
                      <div className="mt-2 space-y-2">
                        {audit.advanced_details.map((detail) => (
                          <div key={`${audit.id}-advanced-${detail.field}`}>
                            <div className="font-semibold">{detail.field}</div>
                            <div>Before: {detail.before}</div>
                            <div>After: {detail.after}</div>
                          </div>
                        ))}
                      </div>
                    </details>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Pagination links={audits.links} />
    </AuthenticatedLayout>
  )
}
