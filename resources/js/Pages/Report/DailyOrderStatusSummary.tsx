import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface DailySummaryRow {
  date: string
  new_request_qualified: number
  qualified: number
  estimate_appt_schedule: number
}

interface OrderListItem {
  id: number
  name: string
  label: string
}

type DailyOrderStatusSummaryProps = PageProps & {
  dailySummary: DailySummaryRow[]
  totals: {
    total: number
    qualified: number
    estimate_appt_schedule: number
    total_orders: number
  }
  orderLists: {
    total: OrderListItem[]
    qualified: OrderListItem[]
    estimate_appt_schedule: OrderListItem[]
  }
  startDate: string
  endDate: string
}

export default function DailyOrderStatusSummary({ dailySummary, totals, orderLists, startDate, endDate, auth }: DailyOrderStatusSummaryProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Daily Order Status"
    >
      <Head title="Daily Order Status" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.daily-order-status-summary'), values, {
            preserveState: true
          })
        }}
      >
        {({ setFieldValue, values }) => (
          <Form className="mb-6 flex gap-4 items-end">
            <div>
              <label htmlFor="start_date" className="block mb-1 font-semibold">Start Date</label>
              <Flatpickr
                id="start_date"
                value={values.start_date}
                options={{ dateFormat: 'Y-m-d' }}
                onChange={([date]) => {
                  const formatted = date.toISOString().split('T')[0]
                  setFieldValue('start_date', formatted)
                }}
                className="form-input border p-2 rounded w-full"
              />
              <ErrorMessage name="start_date" component="div" className="text-red-500 text-sm mt-1" />
            </div>

            <div>
              <label htmlFor="end_date" className="block mb-1 font-semibold">End Date</label>
              <Flatpickr
                id="end_date"
                value={values.end_date}
                options={{ dateFormat: 'Y-m-d' }}
                onChange={([date]) => {
                  const formatted = date.toISOString().split('T')[0]
                  setFieldValue('end_date', formatted)
                }}
                className="form-input border p-2 rounded w-full"
              />
              <ErrorMessage name="end_date" component="div" className="text-red-500 text-sm mt-1" />
            </div>

            <button
              type="submit"
              className="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
            >
              Filter
            </button>
          </Form>
        )}
      </Formik>

      <div className="mb-4 flex gap-2">
        <a
          className="btn btn-primary"
          href={route('report.daily-order-status-summary-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.daily-order-status-summary-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-4 text-left font-semibold text-gray-700">
        Total: {totals.total}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Orders: {totals.total_orders}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Qualified: {totals.qualified}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Estimate &amp; Appt Schedule: {totals.estimate_appt_schedule}
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4">
        <div className="rounded border border-gray-300 p-3">
          <h3 className="font-semibold text-gray-800">Total Orders List ({orderLists.total.length})</h3>
          {orderLists.total.length === 0 ? (
            <p className="mt-2 text-sm text-gray-500">No orders for the selected dates.</p>
          ) : (
            <p className="mt-2 text-sm text-gray-700 break-words">
              {orderLists.total.map((order) => order.label).join(', ')}
            </p>
          )}
        </div>

        <div className="rounded border border-gray-300 p-3">
          <h3 className="font-semibold text-gray-800">Qualified Orders List ({orderLists.qualified.length})</h3>
          {orderLists.qualified.length === 0 ? (
            <p className="mt-2 text-sm text-gray-500">No qualified orders for the selected dates.</p>
          ) : (
            <p className="mt-2 text-sm text-gray-700 break-words">
              {orderLists.qualified.map((order) => order.label).join(', ')}
            </p>
          )}
        </div>

        <div className="rounded border border-gray-300 p-3">
          <h3 className="font-semibold text-gray-800">Estimate &amp; Appt Schedule Orders List ({orderLists.estimate_appt_schedule.length})</h3>
          {orderLists.estimate_appt_schedule.length === 0 ? (
            <p className="mt-2 text-sm text-gray-500">No estimate &amp; appt schedule orders for the selected dates.</p>
          ) : (
            <p className="mt-2 text-sm text-gray-700 break-words">
              {orderLists.estimate_appt_schedule.map((order) => order.label).join(', ')}
            </p>
          )}
        </div>
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Date</th>
            <th className="p-2 border">Total</th>
            <th className="p-2 border">Qualified</th>
            <th className="p-2 border">Estimate &amp; Appt Schedule</th>
          </tr>
        </thead>
        <tbody>
          {dailySummary.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={4}>No data for the selected dates.</td>
            </tr>
          ) : (
            dailySummary.map((row) => (
              <tr key={row.date}>
                <td className="p-2 border">{row.date}</td>
                <td className="p-2 border">{row.new_request_qualified}</td>
                <td className="p-2 border">{row.qualified}</td>
                <td className="p-2 border">{row.estimate_appt_schedule}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
