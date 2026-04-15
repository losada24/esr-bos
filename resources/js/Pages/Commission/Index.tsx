import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { type PageProps } from '@/types'

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
}

type CommissionIndexProps = PageProps & {
  rows: CommissionRow[]
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

export default function CommissionIndex ({
  auth,
  rows,
  totals,
  selectedStatus,
  availableStatuses,
  selectedCommissionStatus,
  availableCommissionStatuses,
  beneficiarySearch,
  startDate,
  endDate
}: CommissionIndexProps) {
  const { data, setData } = useForm({
    status: selectedStatus ?? '',
    commission_status: selectedCommissionStatus ?? '',
    beneficiary: beneficiarySearch ?? '',
    start_date: startDate,
    end_date: endDate
  })
  const exportQuery = `?${new URLSearchParams({
    status: data.status,
    commission_status: data.commission_status,
    beneficiary: data.beneficiary,
    start_date: data.start_date,
    end_date: data.end_date
  }).toString()}`

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Commissions">
      <Head title="Commissions" />

      <form
        className="mb-6 grid gap-4 md:grid-cols-5"
        onSubmit={(event) => {
          event.preventDefault()
          router.get(route('report.commissions'), data, { preserveState: true })
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

        <div className="md:col-span-5 flex gap-2">
          <button type="submit" className="btn btn-primary">Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => {
              router.get(route('report.commissions'))
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

      <div className="mb-4 grid gap-3 md:grid-cols-5">
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Orders</span><span className="text-xl font-semibold">{totals.orders}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Commissions</span><span className="text-xl font-semibold">{totals.commissions}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Total</span><span className="text-xl font-semibold">{formatCurrency(totals.total_commission)}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Paid</span><span className="text-xl font-semibold">{formatCurrency(totals.total_paid)}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Pending</span><span className="text-xl font-semibold">{formatCurrency(totals.total_pending)}</span></div>
      </div>

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
                  <Link className="btn btn-sm btn-primary" href={route('report.commissions.edit-order', row.order_id)}>
                    Manage
                  </Link>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </AuthenticatedLayout>
  )
}
