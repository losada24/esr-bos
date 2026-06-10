import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface ReplannedOrderRow {
  id: number
  order_id: number
  order_number: string | null
  order_name: string | null
  replanned_at: string | null
  replanned_reasons: string[]
  replanned_reasons_label: string
  planned_pickup_date: string | null
  planned_start_date: string | null
  planned_end_date: string | null
  replanned_pickup_date: string | null
  replanned_start_date: string | null
  replanned_end_date: string | null
}

type ReplannedOrdersSummaryProps = PageProps & {
  rows: ReplannedOrderRow[]
  totals: {
    total: number
    reason_counts: Record<string, number>
  }
  startDate: string
  endDate: string
}

export default function ReplannedOrdersSummary ({ rows, totals, startDate, endDate, auth }: ReplannedOrdersSummaryProps) {
  const replannedReasonOrder = ['CLIENT', 'PERMIT', 'MATERIALS']
  const exportParams = new URLSearchParams()
  if (startDate) exportParams.set('start_date', startDate)
  if (endDate) exportParams.set('end_date', endDate)
  const exportQuery = `?${exportParams.toString()}`

  const formatLocalDate = (date: Date): string => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Replanned Orders Summary"
    >
      <Head title="Replanned Orders Summary" />

      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.replanned-orders-summary'), values, {
            preserveState: true
          })
        }}
      >
        {({ setFieldValue, values }) => (
          <Form className="mb-6 flex items-end gap-4">
            <div>
              <label htmlFor="start_date" className="mb-1 block font-semibold">Start Date</label>
              <Flatpickr
                id="start_date"
                value={values.start_date}
                options={{ dateFormat: 'Y-m-d' }}
                onChange={([date]) => {
                  if (!date) {
                    setFieldValue('start_date', '')
                    return
                  }
                  setFieldValue('start_date', formatLocalDate(date))
                }}
                className="form-input w-full rounded border p-2"
              />
              <ErrorMessage name="start_date" component="div" className="mt-1 text-sm text-red-500" />
            </div>

            <div>
              <label htmlFor="end_date" className="mb-1 block font-semibold">End Date</label>
              <Flatpickr
                id="end_date"
                value={values.end_date}
                options={{ dateFormat: 'Y-m-d' }}
                onChange={([date]) => {
                  if (!date) {
                    setFieldValue('end_date', '')
                    return
                  }
                  setFieldValue('end_date', formatLocalDate(date))
                }}
                className="form-input w-full rounded border p-2"
              />
              <ErrorMessage name="end_date" component="div" className="mt-1 text-sm text-red-500" />
            </div>

            <button
              type="submit"
              className="rounded bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
            >
              Filter
            </button>
          </Form>
        )}
      </Formik>

      <div className="mb-4 flex gap-2">
        <a
          className="btn btn-primary"
          href={route('report.replanned-orders-summary-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.replanned-orders-summary-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Replanned: {totals.total}
      </div>
      <div className="mt-1 text-left font-semibold text-gray-700">
        {replannedReasonOrder.map((reason) => (
          <div key={reason} className="mt-1">
            Cantidad de Replanned por {reason}: {totals.reason_counts?.[reason] ?? 0}
          </div>
        ))}
      </div>

      <table className="mt-4 w-full border border-gray-300">
        <thead className="bg-gray-100">
          <tr>
            <th className="border p-2">Order #</th>
            <th className="border p-2">Order Name</th>
            <th className="border p-2">Replanned At</th>
            <th className="border p-2">Replanned Reasons</th>
            <th className="border p-2">Planned Pickup</th>
            <th className="border p-2">Planned Start</th>
            <th className="border p-2">Planned End</th>
            <th className="border p-2">Replanned Pickup</th>
            <th className="border p-2">Replanned Start</th>
            <th className="border p-2">Replanned End</th>
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 ? (
            <tr>
              <td className="border p-2 text-center" colSpan={10}>No replanned orders found for the selected dates.</td>
            </tr>
          ) : (
            rows.map((row) => (
              <tr key={row.id}>
                <td className="border p-2">{row.order_number || `#${row.order_id}`}</td>
                <td className="border p-2">{row.order_name || '-'}</td>
                <td className="border p-2">{row.replanned_at || '-'}</td>
                <td className="border p-2">{row.replanned_reasons_label || '-'}</td>
                <td className="border p-2">{row.planned_pickup_date || '-'}</td>
                <td className="border p-2">{row.planned_start_date || '-'}</td>
                <td className="border p-2">{row.planned_end_date || '-'}</td>
                <td className="border p-2">{row.replanned_pickup_date || '-'}</td>
                <td className="border p-2">{row.replanned_start_date || '-'}</td>
                <td className="border p-2">{row.replanned_end_date || '-'}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
