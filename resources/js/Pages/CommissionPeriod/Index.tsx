import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import Pagination from '@/Components/Pagination'
import { Head, Link, router, useForm } from '@inertiajs/react'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import { type PageProps, type PaginatorLink } from '@/types'
import { useState } from 'react'

type PeriodIndexProps = PageProps & {
  periods: {
    data: Array<{
      id: number
      label: string
      status: string
      start_date: string
      end_date: string
      closed_at?: string | null
      payments_count: number
      snapshot_summary?: {
        payments_count: number
        beneficiaries_count: number
        total_paid: number
      } | null
      can_edit: boolean
      can_edit_dates: boolean
      can_delete: boolean
      can_reopen: boolean
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

function formatDateForPicker(date: Date): string {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')

  return `${year}-${month}-${day}`
}

export default function CommissionPeriodIndex ({ auth, periods }: PeriodIndexProps) {
  const [editingPeriodId, setEditingPeriodId] = useState<number | null>(null)
  const [editingCanEditDates, setEditingCanEditDates] = useState<boolean>(true)

  const form = useForm({
    start_date: '',
    end_date: '',
    label: ''
  })

  function resetForm(): void {
    setEditingPeriodId(null)
    setEditingCanEditDates(true)
    form.setData({
      start_date: '',
      end_date: '',
      label: ''
    })
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Commission Periods">
      <Head title="Commission Periods" />

      <form
        className="mb-6 grid gap-4 rounded border p-4 md:grid-cols-4"
        onSubmit={(event) => {
          event.preventDefault()

          if (editingPeriodId !== null) {
            form.patch(route('commission-periods.update', editingPeriodId), {
              preserveScroll: true,
              onSuccess: () => { resetForm() }
            })
            return
          }

          form.post(route('commission-periods.store'), {
            preserveScroll: true,
            onSuccess: () => { resetForm() }
          })
        }}
      >
        <div>
          <label className="mb-1 block font-semibold">Start Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            value={form.data.start_date || undefined}
            className="form-input"
            disabled={editingPeriodId !== null && !editingCanEditDates}
            onChange={([date]) => {
              form.setData('start_date', date ? formatDateForPicker(date) : '')
            }}
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">End Date</label>
          <Flatpickr
            options={{
              mode: 'single',
              dateFormat: 'Y-m-d',
              position: 'auto right'
            }}
            value={form.data.end_date || undefined}
            className="form-input"
            disabled={editingPeriodId !== null && !editingCanEditDates}
            onChange={([date]) => {
              form.setData('end_date', date ? formatDateForPicker(date) : '')
            }}
          />
        </div>
        <div>
          <label className="mb-1 block font-semibold">Label</label>
          <input className="form-input" value={form.data.label} onChange={(event) => { form.setData('label', event.target.value) }} />
        </div>
        <div className="flex items-end gap-2">
          <button type="submit" className="btn btn-primary">
            {editingPeriodId !== null ? 'Save Period' : 'Create Period'}
          </button>
          {editingPeriodId !== null && (
            <button type="button" className="btn btn-outline-primary" onClick={() => { resetForm() }}>
              Cancel
            </button>
          )}
        </div>
      </form>

      {editingPeriodId !== null && !editingCanEditDates && (
        <div className="mb-4 text-sm text-slate-500">
          This period already has assigned payments. Only the label can be edited.
        </div>
      )}

      <div className="mb-4 text-sm text-slate-500">
        Open periods can be edited. Closed periods without payments can be reopened or deleted. Periods with real payments stay read-only.
      </div>

      <div className="table-responsive">
        <table className="table-auto w-full">
          <thead className="bg-gray-100">
            <tr className="text-left">
              <th className="px-4 py-3">Period</th>
              <th className="px-4 py-3">Status</th>
              <th className="px-4 py-3">Payments</th>
              <th className="px-4 py-3">Beneficiaries</th>
              <th className="px-4 py-3">Total</th>
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
                <td className="border-t px-4 py-4">{period.snapshot_summary?.payments_count ?? period.payments_count}</td>
                <td className="border-t px-4 py-4">{period.snapshot_summary?.beneficiaries_count ?? 0}</td>
                <td className="border-t px-4 py-4">{formatCurrency(period.snapshot_summary?.total_paid ?? 0)}</td>
                <td className="border-t px-4 py-4 flex gap-2">
                  <Link className="btn btn-sm btn-primary" href={route('commission-periods.show', period.id)}>View</Link>
                  {period.can_reopen && (
                    <Link
                      as="button"
                      method="post"
                      href={route('commission-periods.reopen', period.id)}
                      className="btn btn-sm btn-outline-primary"
                    >
                      Reopen
                    </Link>
                  )}
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
                  {period.can_edit && (
                    <button
                      type="button"
                      className="text-slate-500 hover:text-slate-700"
                      title="Edit period"
                      onClick={() => {
                        setEditingPeriodId(period.id)
                        setEditingCanEditDates(period.can_edit_dates)
                        form.setData({
                          start_date: period.start_date,
                          end_date: period.end_date,
                          label: period.label ?? ''
                        })
                      }}
                    >
                      <span className="sr-only">Edit period</span>
                      <EditIcon />
                    </button>
                  )}
                  {period.can_delete && (
                    <button
                      type="button"
                      className="text-slate-500 hover:text-red-600"
                      title="Delete period"
                      onClick={() => {
                        if (!window.confirm(`Delete commission period "${period.label}"?`)) {
                          return
                        }

                        if (editingPeriodId === period.id) {
                          resetForm()
                        }

                        router.delete(route('commission-periods.destroy', period.id), {
                          preserveScroll: true
                        })
                      }}
                    >
                      <span className="sr-only">Delete period</span>
                      <DeleteIcon />
                    </button>
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
