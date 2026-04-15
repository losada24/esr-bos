import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import Pagination from '@/Components/Pagination'
import { Head, Link, useForm } from '@inertiajs/react'
import { type PageProps, type PaginatorLink } from '@/types'

type PeriodIndexProps = PageProps & {
  periods: {
    data: Array<{
      id: number
      label: string
      status: string
      start_date: string
      end_date: string
      closed_at?: string | null
      snapshot_summary?: {
        payments_count: number
        beneficiaries_count: number
        total_paid: number
      } | null
    }>
    links: PaginatorLink[]
  }
}

function formatCurrency(value: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(Number(value ?? 0))
}

export default function CommissionPeriodIndex ({ auth, periods }: PeriodIndexProps) {
  const form = useForm({
    start_date: '',
    end_date: '',
    label: ''
  })

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Commission Periods">
      <Head title="Commission Periods" />

      <form
        className="mb-6 grid gap-4 rounded border p-4 md:grid-cols-4"
        onSubmit={(event) => {
          event.preventDefault()
          form.post(route('commission-periods.store'))
        }}
      >
        <div>
          <label className="mb-1 block font-semibold">Start Date</label>
          <input className="form-input" type="date" value={form.data.start_date} onChange={(event) => { form.setData('start_date', event.target.value) }} />
        </div>
        <div>
          <label className="mb-1 block font-semibold">End Date</label>
          <input className="form-input" type="date" value={form.data.end_date} onChange={(event) => { form.setData('end_date', event.target.value) }} />
        </div>
        <div>
          <label className="mb-1 block font-semibold">Label</label>
          <input className="form-input" value={form.data.label} onChange={(event) => { form.setData('label', event.target.value) }} />
        </div>
        <div className="flex items-end">
          <button type="submit" className="btn btn-primary">Create Period</button>
        </div>
      </form>

      <div className="table-responsive">
        <table className="table-auto w-full">
          <thead className="bg-gray-100">
            <tr className="text-left">
              <th className="px-4 py-3">Period</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Payments</th>
              <th className="px-4 py-3">Beneficiaries</th>
              <th className="px-4 py-3">Total Paid</th>
              <th className="px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            {periods.data.length === 0 && (
              <tr>
                <td className="border-t px-4 py-4 text-center" colSpan={6}>No commission periods found.</td>
              </tr>
            )}
            {periods.data.map((period) => (
              <tr key={period.id}>
                <td className="border-t px-4 py-4">
                  <div className="font-semibold">{period.label}</div>
                  <div className="text-xs text-slate-500">{period.start_date} to {period.end_date}</div>
                </td>
                <td className="border-t px-4 py-4">{period.status}</td>
                <td className="border-t px-4 py-4">{period.snapshot_summary?.payments_count ?? 0}</td>
                <td className="border-t px-4 py-4">{period.snapshot_summary?.beneficiaries_count ?? 0}</td>
                <td className="border-t px-4 py-4">{formatCurrency(period.snapshot_summary?.total_paid ?? 0)}</td>
                <td className="border-t px-4 py-4 flex gap-2">
                  <Link className="btn btn-sm btn-primary" href={route('commission-periods.show', period.id)}>View</Link>
                  {period.status !== 'CLOSED' && (
                    <Link
                      as="button"
                      method="post"
                      href={route('commission-periods.close', period.id)}
                      className="btn btn-sm btn-outline-primary"
                    >
                      Close
                    </Link>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      <Pagination links={periods.links} />
    </AuthenticatedLayout>
  )
}
