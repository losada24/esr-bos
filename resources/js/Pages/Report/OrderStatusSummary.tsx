import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface StatusItem {
  status: string
  count: number
}

type OrderStatusSummaryProps = PageProps & {
  statusSummary: StatusItem[]
  startDate: string
  endDate: string
}

export default function OrderStatusSummary({ statusSummary, startDate, endDate, auth }: OrderStatusSummaryProps) {
  const totalCount = statusSummary.reduce((sum, item) => sum + item.count, 0)

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Order Status Summary"
    >
      <Head title="Order Status Summary" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.order-status-summary'), values, {
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

      <div className="mt-4 text-left font-semibold text-gray-700">
        Total Orders: {totalCount}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Status</th>
            <th className="p-2 border">Quantity</th>
          </tr>
        </thead>
        <tbody>
          {statusSummary.map((item) => (
            <tr key={item.status}>
              <td className="p-2 border">{item.status}</td>
              <td className="p-2 border">{item.count}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
