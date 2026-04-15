import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link } from '@inertiajs/react'
import { type PageProps } from '@/types'

type PeriodShowProps = PageProps & {
  period: {
    id: number
    label: string
    status: string
    start_date: string
    end_date: string
    closed_at?: string | null
    snapshot?: {
      summary?: {
        payments_count: number
        orders_count: number
        commissions_count: number
        beneficiaries_count: number
        total_paid: number
        beneficiary_totals: Array<{
          beneficiary_name: string
          beneficiary_relation: string
          total_paid: number
          payments_count: number
        }>
      }
      payments?: Array<{
        payment_id: number
        sequence: number
        paid_at?: string | null
        payment_total_to_pay: number
        order: {
          name: string
          status: string
          owners: string[]
        }
        commission: {
          beneficiary_name: string
          beneficiary_relation: string
        }
      }>
    } | null
  }
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

export default function CommissionPeriodShow ({ auth, period }: PeriodShowProps) {
  const summary = period.snapshot?.summary
  const payments = period.snapshot?.payments ?? []

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={period.label}
      actions={
        <Link className="btn btn-outline-primary" href={route('commission-periods.index')}>
          Back To Periods
        </Link>
      }
    >
      <Head title={period.label} />

      <div className="mb-6 rounded border p-4">
        <h1 className="text-xl font-semibold">{period.label}</h1>
        <div className="mt-2 grid gap-3 md:grid-cols-4 text-sm">
          <div><span className="block text-slate-500">Status</span><span className="font-semibold">{period.status}</span></div>
          <div><span className="block text-slate-500">Start</span><span className="font-semibold">{period.start_date}</span></div>
          <div><span className="block text-slate-500">End</span><span className="font-semibold">{period.end_date}</span></div>
          <div><span className="block text-slate-500">Closed At</span><span className="font-semibold">{period.closed_at ?? '-'}</span></div>
        </div>
      </div>

      <div className="mb-6 grid gap-3 md:grid-cols-5">
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Payments</span><span className="text-xl font-semibold">{summary?.payments_count ?? 0}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Orders</span><span className="text-xl font-semibold">{summary?.orders_count ?? 0}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Commissions</span><span className="text-xl font-semibold">{summary?.commissions_count ?? 0}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Beneficiaries</span><span className="text-xl font-semibold">{summary?.beneficiaries_count ?? 0}</span></div>
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">Total Paid</span><span className="text-xl font-semibold">{formatCurrency(summary?.total_paid ?? 0)}</span></div>
      </div>

      <div className="mb-6 rounded border p-4">
        <h2 className="mb-3 text-lg font-semibold">Beneficiary Totals Snapshot</h2>
        <div className="table-responsive">
          <table className="table-auto w-full">
            <thead className="bg-gray-100">
              <tr className="text-left">
                <th className="px-4 py-3">Beneficiary</th>
                <th className="px-4 py-3">Relation</th>
                <th className="px-4 py-3">Payments</th>
                <th className="px-4 py-3">Total Paid</th>
              </tr>
            </thead>
            <tbody>
              {(summary?.beneficiary_totals ?? []).map((item) => (
                <tr key={`${item.beneficiary_name}-${item.beneficiary_relation}`}>
                  <td className="border-t px-4 py-4">{item.beneficiary_name}</td>
                  <td className="border-t px-4 py-4">{item.beneficiary_relation}</td>
                  <td className="border-t px-4 py-4">{item.payments_count}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(item.total_paid)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="rounded border p-4">
        <h2 className="mb-3 text-lg font-semibold">Payment Snapshot</h2>
        <div className="table-responsive">
          <table className="table-auto w-full">
            <thead className="bg-gray-100">
              <tr className="text-left">
                <th className="px-4 py-3">Order</th>
                <th className="px-4 py-3">Owners</th>
                <th className="px-4 py-3">Beneficiary</th>
                <th className="px-4 py-3">Relation</th>
                <th className="px-4 py-3">Payment #</th>
                <th className="px-4 py-3">Paid At</th>
                <th className="px-4 py-3">Total</th>
              </tr>
            </thead>
            <tbody>
              {payments.length === 0 && (
                <tr>
                  <td className="border-t px-4 py-4 text-center" colSpan={7}>No payments snapshot available for this period.</td>
                </tr>
              )}
              {payments.map((payment) => (
                <tr key={payment.payment_id}>
                  <td className="border-t px-4 py-4">
                    <div>{payment.order.name}</div>
                    <div className="text-xs text-slate-500">{payment.order.status}</div>
                  </td>
                  <td className="border-t px-4 py-4">{payment.order.owners.join(', ') || '-'}</td>
                  <td className="border-t px-4 py-4">{payment.commission.beneficiary_name}</td>
                  <td className="border-t px-4 py-4">{payment.commission.beneficiary_relation}</td>
                  <td className="border-t px-4 py-4">{payment.sequence}</td>
                  <td className="border-t px-4 py-4">{payment.paid_at ?? '-'}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.payment_total_to_pay)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
