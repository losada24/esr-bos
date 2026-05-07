import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router } from '@inertiajs/react'
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
          beneficiary_source_type?: string | null
          beneficiary_source_id?: string | number | null
          beneficiary_name: string
          beneficiary_relation: string
          total_paid: number
          payments_count: number
        }>
      }
      payments?: Array<{
        payment_id: number
        sequence: number
        payment_kind?: string
        status?: string
        paid_at?: string | null
        accounting_status?: string | null
        accounting_status_date?: string | null
        payment_base_amount?: number
        payment_other_cost_amount?: number
        payment_total_to_pay: number
        can_unassign?: boolean
        order: {
          id?: number
          name: string
          status: string
          order_number?: string | number | null
          invoice_number?: string | null
          project_payment_method?: string | null
          type_of_financing?: string | null
          project_amount?: number
          owners: string[]
        }
        commission: {
          id?: number
          status?: string
          percentage_value?: number | null
          project_amount_snapshot?: number
          fee_amount_snapshot?: number
          financing_fee_amount?: number
          base_amount_snapshot?: number
          commission_total_amount?: number
          commission_paid_amount?: number
          commission_pending_amount?: number
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

function paymentKindLabel(paymentKind?: string): string {
  return paymentKind === 'EXTRA_ADJUSTMENT' ? 'Extra Adjustment' : 'Regular'
}

export default function CommissionPeriodShow ({ auth, period }: PeriodShowProps) {
  const summary = period.snapshot?.summary
  const payments = period.snapshot?.payments ?? []
  const showPaymentActions = period.status === 'OPEN'
  const totalLabel = period.status === 'OPEN' ? 'Total In Period' : 'Total Paid'
  const paymentsHeading = period.status === 'OPEN' ? 'Payments In Period' : 'Payment Snapshot'
  const beneficiaryHeading = period.status === 'OPEN' ? 'Beneficiary Totals In Period' : 'Beneficiary Totals Snapshot'

  function formatPercentage(value?: number | null): string {
    if (value === null || value === undefined) {
      return '-'
    }

    return `${Number(value)}%`
  }

  function buildBeneficiaryExportHref (
    format: 'pdf' | 'excel',
    beneficiarySourceType?: string | null,
    beneficiarySourceId?: string | number | null
  ): string {
    const baseRoute = format === 'pdf'
      ? route('commission-periods.pdf', period.id)
      : route('commission-periods.excel', period.id)

    if (!beneficiarySourceType || beneficiarySourceId === null || beneficiarySourceId === undefined) {
      return baseRoute
    }

    const query = new URLSearchParams({
      beneficiary_source_type: beneficiarySourceType,
      beneficiary_source_id: String(beneficiarySourceId)
    }).toString()

    return `${baseRoute}?${query}`
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={period.label}
      actions={
        <div className="flex flex-wrap gap-2">
          <a
            className="btn btn-outline-primary"
            href={route('commission-periods.pdf', period.id)}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a className="btn btn-outline-primary" href={route('commission-periods.excel', period.id)}>
            Export Excel
          </a>
          <Link className="btn btn-outline-primary" href={route('commission-periods.index')}>
            Back To Periods
          </Link>
        </div>
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
        <div className="rounded border p-3"><span className="block text-xs uppercase text-slate-500">{totalLabel}</span><span className="text-xl font-semibold">{formatCurrency(summary?.total_paid ?? 0)}</span></div>
      </div>

      <div className="mb-6 rounded border p-4">
        <h2 className="mb-3 text-lg font-semibold">{beneficiaryHeading}</h2>
        <div className="table-responsive">
          <table className="table-auto w-full">
            <thead className="bg-gray-100">
              <tr className="text-left">
                <th className="px-4 py-3">Beneficiary</th>
                <th className="px-4 py-3">Relation</th>
                <th className="px-4 py-3">Payments</th>
                <th className="px-4 py-3">{totalLabel}</th>
                <th className="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              {(summary?.beneficiary_totals ?? []).map((item) => (
                <tr key={`${item.beneficiary_name}-${item.beneficiary_relation}`}>
                  <td className="border-t px-4 py-4">{item.beneficiary_name}</td>
                  <td className="border-t px-4 py-4">{item.beneficiary_relation}</td>
                  <td className="border-t px-4 py-4">{item.payments_count}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(item.total_paid)}</td>
                  <td className="border-t px-4 py-4">
                    <div className="flex flex-wrap gap-2">
                      <a
                        className="btn btn-sm btn-outline-primary"
                        href={buildBeneficiaryExportHref('pdf', item.beneficiary_source_type, item.beneficiary_source_id)}
                        target="_blank"
                        rel="noopener noreferrer"
                      >
                        PDF
                      </a>
                      <a
                        className="btn btn-sm btn-outline-primary"
                        href={buildBeneficiaryExportHref('excel', item.beneficiary_source_type, item.beneficiary_source_id)}
                      >
                        Excel
                      </a>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <div className="rounded border p-4">
        <h2 className="mb-3 text-lg font-semibold">{paymentsHeading}</h2>
        <div className="table-responsive">
          <table className="table-auto w-full">
            <thead className="bg-gray-100">
              <tr className="text-left">
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
                <th className="px-4 py-3">Paid Accumulated</th>
                <th className="px-4 py-3">Payment To Pay</th>
                <th className="px-4 py-3">Other Cost</th>
                <th className="px-4 py-3">Total Payment</th>
                <th className="px-4 py-3">Payment Status</th>
                <th className="px-4 py-3">Paid At</th>
                {showPaymentActions && (
                  <th className="px-4 py-3">Actions</th>
                )}
              </tr>
            </thead>
            <tbody>
              {payments.length === 0 && (
                <tr>
                  <td className="border-t px-4 py-4 text-center" colSpan={showPaymentActions ? 21 : 20}>
                    No payments available for this period.
                  </td>
                </tr>
              )}
              {payments.map((payment) => (
                <tr key={payment.payment_id}>
                  <td className="border-t px-4 py-4">
                    <div>{payment.accounting_status ?? '-'}</div>
                    <div className="text-xs text-slate-500">{payment.accounting_status_date ?? '-'}</div>
                  </td>
                  <td className="border-t px-4 py-4">
                    <div className="font-semibold">{payment.order.name}</div>
                    <div className="text-xs text-slate-500">{payment.order.status} · {payment.order.owners.join(', ') || '-'}</div>
                  </td>
                  <td className="border-t px-4 py-4">
                    <div>#{payment.commission.id ?? '-'}</div>
                    <div className="text-xs text-slate-500">{payment.commission.status ?? '-'}</div>
                  </td>
                  <td className="border-t px-4 py-4">
                    {payment.order.invoice_number || (payment.order.order_number ? `#${payment.order.order_number}` : '-')}
                  </td>
                  <td className="border-t px-4 py-4">
                    <div>{payment.commission.beneficiary_name}</div>
                    <div className="text-xs text-slate-500">{payment.commission.beneficiary_relation}</div>
                  </td>
                  <td className="border-t px-4 py-4">{payment.order.project_payment_method ?? '-'}</td>
                  <td className="border-t px-4 py-4">{payment.order.type_of_financing ?? '-'}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.order.project_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.commission.fee_amount_snapshot ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.commission.base_amount_snapshot ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatPercentage(payment.commission.percentage_value)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.commission.commission_total_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.commission.commission_pending_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{paymentKindLabel(payment.payment_kind)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.commission.commission_paid_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.payment_base_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.payment_other_cost_amount ?? 0)}</td>
                  <td className="border-t px-4 py-4">{formatCurrency(payment.payment_total_to_pay)}</td>
                  <td className="border-t px-4 py-4">{payment.status ?? '-'}</td>
                  <td className="border-t px-4 py-4">{payment.paid_at ?? '-'}</td>
                  {showPaymentActions && (
                    <td className="border-t px-4 py-4">
                      {payment.can_unassign ? (
                        <button
                          type="button"
                          className="btn btn-sm btn-outline-danger"
                          onClick={() => {
                            if (!window.confirm(`Remove payment #${payment.sequence} from this commission period?`)) {
                              return
                            }

                            router.delete(route('commission-periods.payments.unassign', [period.id, payment.payment_id]), {
                              preserveScroll: true
                            })
                          }}
                        >
                          Remove
                        </button>
                      ) : (
                        '-'
                      )}
                    </td>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
