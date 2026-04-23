import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import Pagination from '@/Components/Pagination'
import { Head, Link, router, useForm } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type PageProps, type PaginatorLink } from '@/types'

type CommissionRow = {
  id: number
  order_id?: number | null
  order_name?: string | null
  order_number?: string | number | null
  invoice_number?: string | null
  commission_status: string
  beneficiary_name?: string | null
  beneficiary_relation?: string | null
  commission_total: number
  events_count: number
  latest_changed_at?: string | null
  latest_action?: string | null
  latest_summary?: string | null
  latest_user_name?: string | null
  latest_period_label?: string | null
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

type CommissionHistoryProps = PageProps & {
  commissions: {
    data: CommissionRow[]
    links: PaginatorLink[]
  }
  periods: PeriodOption[]
  filters: HistoryFilters
  availableActions: string[]
  totals: {
    commissions: number
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

export default function CommissionHistory ({
  auth,
  commissions,
  periods,
  filters,
  availableActions,
  totals
}: CommissionHistoryProps) {
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
    <AuthenticatedLayout auth={auth} pageTitle="Commission History">
      <Head title="Commission History" />

      <form
        className="mb-6 grid gap-4 md:grid-cols-6"
        onSubmit={(event) => {
          event.preventDefault()
          router.get(route('report.commissions.history'), {
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
            placeholder="Order, beneficiary, action"
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
            onClick={() => { router.get(route('report.commissions.history')) }}
          >
            Reset
          </button>
        </div>
      </form>

      <div className="mb-4 grid gap-3 md:grid-cols-2">
        <div className="rounded border bg-white p-4 text-sm">
          <span className="font-semibold">Commissions in range:</span> {totals.commissions}
        </div>
        <div className="rounded border bg-white p-4 text-sm">
          <span className="font-semibold">Audit events in range:</span> {totals.events}
        </div>
      </div>

      <div className="table-responsive rounded border bg-white">
        <table className="table-auto w-full">
          <thead className="bg-gray-100">
            <tr className="text-left">
              <th className="px-4 py-3">Commission</th>
              <th className="px-4 py-3">Order</th>
              <th className="px-4 py-3">Beneficiary</th>
              <th className="px-4 py-3">Total</th>
              <th className="px-4 py-3">Events</th>
              <th className="px-4 py-3">Latest Event</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {commissions.data.length === 0 && (
              <tr>
                <td className="border-t px-4 py-4 text-center text-slate-500" colSpan={7}>
                  No commission history found for the selected filters.
                </td>
              </tr>
            )}
            {commissions.data.map((commission) => (
              <tr key={commission.id}>
                <td className="border-t px-4 py-4 align-top">
                  <div className="font-medium">#{commission.id}</div>
                  <div className="text-xs text-slate-500">{commission.commission_status}</div>
                </td>
                <td className="border-t px-4 py-4 align-top">
                  {commission.order_id ? (
                    <Link className="text-primary underline" href={route('report.commissions.edit-order', commission.order_id)}>
                      {commission.order_name ?? `Order #${commission.order_id}`}
                    </Link>
                  ) : '-'}
                  <div className="text-xs text-slate-500">
                    {commission.invoice_number || commission.order_number
                      ? `#${commission.invoice_number ?? commission.order_number}`
                      : '-'}
                  </div>
                </td>
                <td className="border-t px-4 py-4 align-top">
                  <div>{commission.beneficiary_name ?? '-'}</div>
                  <div className="text-xs text-slate-500">{commission.beneficiary_relation ?? '-'}</div>
                </td>
                <td className="border-t px-4 py-4 align-top">{formatCurrency(commission.commission_total)}</td>
                <td className="border-t px-4 py-4 align-top">{commission.events_count}</td>
                <td className="border-t px-4 py-4 align-top">
                  <div>{commission.latest_action ?? '-'}</div>
                  <div className="text-xs text-slate-500">{commission.latest_changed_at ?? '-'}</div>
                  <div className="mt-1 text-xs text-slate-600">{commission.latest_summary ?? '-'}</div>
                  <div className="mt-1 text-xs text-slate-500">
                    {commission.latest_user_name ?? 'System'}
                    {commission.latest_period_label ? ` · ${commission.latest_period_label}` : ''}
                  </div>
                </td>
                <td className="border-t px-4 py-4 align-top">
                  <Link
                    className="btn btn-sm btn-outline-primary"
                    href={`${route('report.commissions.history.show', commission.id)}${appliedQueryString}`}
                  >
                    View
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Pagination links={commissions.links} />
    </AuthenticatedLayout>
  )
}
