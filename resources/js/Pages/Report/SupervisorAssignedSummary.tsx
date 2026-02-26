import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface SupervisorSummaryItem {
  supervisor_id: number | null
  supervisor_name: string | null
  confirmed_orders: number
  confirmed_completed_orders: number
  execution_not_completed_orders: number
  inspection_not_completed_orders: number
}

type SupervisorAssignedSummaryProps = PageProps & {
  summary: SupervisorSummaryItem[]
  totalConfirmed: number
  totalConfirmedCompleted: number
  totalExecutionNotCompleted: number
  totalInspectionNotCompleted: number
  startDate: string
  endDate: string
}

export default function SupervisorAssignedSummary({ summary, totalConfirmed, totalConfirmedCompleted, totalExecutionNotCompleted, totalInspectionNotCompleted, startDate, endDate, auth }: SupervisorAssignedSummaryProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Supervisor Assigned Orders"
    >
      <Head title="Supervisor Assigned Orders" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.supervisor-assigned-summary'), values, {
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
          href={route('report.supervisor-assigned-summary-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.supervisor-assigned-summary-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-4 text-left font-semibold text-gray-700">
        Total Confirmed Orders: {totalConfirmed}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Confirmed & Completed: {totalConfirmedCompleted}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Current Open Orders: {totalExecutionNotCompleted}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Current Inspection Orders: {totalInspectionNotCompleted}
      </div>
      <div className="mt-1 text-left text-sm text-gray-500">
        Note: Current Open Orders and Current Inspection Orders use current order status and do not depend on the selected date range.
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Supervisor</th>
            <th className="p-2 border">Confirmed Orders</th>
            <th className="p-2 border">Confirmed & Completed</th>
            <th className="p-2 border">Current Open Orders</th>
            <th className="p-2 border">Current Inspection Orders</th>
          </tr>
        </thead>
        <tbody>
          {summary.map((item) => {
            const isPickupOrDeliveryOnly = !item.supervisor_name || item.supervisor_name === 'PICKUP OR DELIVERY ONLY'
            return (
              <tr key={item.supervisor_id ?? 'unassigned'} className={isPickupOrDeliveryOnly ? 'bg-gray-100' : ''}>
                <td className="p-2 border">{item.supervisor_name || 'PICKUP OR DELIVERY ONLY'}</td>
                <td className="p-2 border">{item.confirmed_orders}</td>
                <td className="p-2 border">{item.confirmed_completed_orders}</td>
                <td className="p-2 border">{isPickupOrDeliveryOnly ? 0 : item.execution_not_completed_orders}</td>
                <td className="p-2 border">{isPickupOrDeliveryOnly ? 0 : item.inspection_not_completed_orders}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
