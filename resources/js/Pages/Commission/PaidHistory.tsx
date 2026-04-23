import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import Pagination from '@/Components/Pagination'
import { Head, Link, router, useForm } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type PageProps, type PaginatorLink } from '@/types'

type PaidHistoryRow = {
  payment_id: number
  commission_id?: number | null
  payment_sequence: number
  payment_kind?: string
  paid_at?: string | null
  period_id?: number | null
  period_label?: string | null
  order_id?: number | null
  order_name?: string | null
  order_number?: string | number | null
  invoice_number?: string | null
  order_status?: string | null
  beneficiary_name?: string | null
  beneficiary_relation?: string | null
  project_amount: number
  commission_fee: number
  financing_fee_amount: number
  commission_base: number
  commission_total: number
  payment_base_amount: number
  payment_other_cost_amount: number
  payment_total: number
  payment_status: string
}

type PeriodOption = {
  id: number
  label: string
  status: string
}

type PaidHistoryProps = PageProps & {
  payments: {
    data: PaidHistoryRow[]
    links: PaginatorLink[]
  }
  periods: PeriodOption[]
  filters: {
    search: string
    period_id?: number | null
    start_date: string
    end_date: string
  }
  totals: {
    payments: number
    total_paid: number
  }
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

function paymentKindLabel(paymentKind?: string): string {
  return paymentKind === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular'
}

function formatDateForPicker(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

export default function PaidHistory ({
  auth,
  payments,
  periods,
  filters,
  totals
}: PaidHistoryProps) {
  const { data, setData } = useForm({
    search: filters.search,
    period_id: filters.period_id ? String(filters.period_id) : '',
    start_date: filters.start_date,
    end_date: filters.end_date
  })

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Paid Commission History">
      <Head title="Paid Commission History" />

      <form
        className="mb-6 grid gap-4 md:grid-cols-4"
        onSubmit={(event) => {
          event.preventDefault()
          router.get(route('report.commissions.paid-history'), {
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
            placeholder="Order, invoice, beneficiary"
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

        <div className="md:col-span-4 flex flex-wrap gap-2">
          <button type="submit" className="btn btn-primary">Filter</button>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => { router.get(route('report.commissions.paid-history')) }}
          >
            Reset
          </button>
        </div>
      </form>

      <div className="mb-4 grid gap-3 md:grid-cols-2">
        <div className="rounded border bg-white p-4 text-sm">
          <span className="font-semibold">Paid payments:</span> {totals.payments}
        </div>
        <div className="rounded border bg-white p-4 text-sm">
          <span className="font-semibold">Total paid:</span> {formatCurrency(totals.total_paid)}
        </div>
      </div>

      <div className="table-responsive rounded border bg-white">
        <table className="table-auto w-full">
          <thead className="bg-gray-100">
            <tr className="text-left">
              <th className="px-4 py-3">Paid At</th>
              <th className="px-4 py-3">Period</th>
              <th className="px-4 py-3">Order</th>
              <th className="px-4 py-3">Beneficiary</th>
              <th className="px-4 py-3">Commission</th>
              <th className="px-4 py-3">Payment Type</th>
              <th className="px-4 py-3">Payment</th>
              <th className="px-4 py-3">Other Cost</th>
              <th className="px-4 py-3">Total Paid</th>
            </tr>
          </thead>
          <tbody>
            {payments.data.length === 0 && (
              <tr>
                <td className="border-t px-4 py-4 text-center text-slate-500" colSpan={9}>
                  No paid commission history found for the selected filters.
                </td>
              </tr>
            )}
            {payments.data.map((payment) => (
              <tr key={payment.payment_id}>
                <td className="border-t px-4 py-4 align-top">{payment.paid_at ?? '-'}</td>
                <td className="border-t px-4 py-4 align-top">{payment.period_label ?? '-'}</td>
                <td className="border-t px-4 py-4 align-top">
                  {payment.order_id ? (
                    <Link className="text-primary underline" href={route('report.commissions.edit-order', payment.order_id)}>
                      {payment.order_name ?? `Order #${payment.order_id}`}
                    </Link>
                  ) : '-'}
                  <div className="text-xs text-slate-500">
                    {payment.invoice_number || payment.order_number ? `#${payment.invoice_number ?? payment.order_number}` : payment.order_status ?? ''}
                  </div>
                </td>
                <td className="border-t px-4 py-4 align-top">
                  <div>{payment.beneficiary_name ?? '-'}</div>
                  <div className="text-xs text-slate-500">{payment.beneficiary_relation ?? '-'}</div>
                </td>
                <td className="border-t px-4 py-4 align-top">
                  <div>Total: {formatCurrency(payment.commission_total)}</div>
                  <div className="text-xs text-slate-500">Fee: {formatCurrency(payment.commission_fee)} | Financing: {formatCurrency(payment.financing_fee_amount)}</div>
                </td>
                <td className="border-t px-4 py-4 align-top">{paymentKindLabel(payment.payment_kind)}</td>
                <td className="border-t px-4 py-4 align-top">
                  <div>#{payment.payment_sequence}</div>
                  <div className="text-xs text-slate-500">{formatCurrency(payment.payment_base_amount)}</div>
                </td>
                <td className="border-t px-4 py-4 align-top">{formatCurrency(payment.payment_other_cost_amount)}</td>
                <td className="border-t px-4 py-4 align-top">{formatCurrency(payment.payment_total)}</td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Pagination links={payments.links} />
    </AuthenticatedLayout>
  )
}
