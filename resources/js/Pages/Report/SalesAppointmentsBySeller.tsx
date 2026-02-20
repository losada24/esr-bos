import React from 'react'
import { Head, router } from '@inertiajs/react'
import { ErrorMessage, Form, Formik } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface SalesAppointmentsSummaryItem {
  seller_id: number
  seller_name: string
  appointments_count: number
}

type SalesAppointmentsBySellerProps = PageProps & {
  summary: SalesAppointmentsSummaryItem[]
  totals: {
    appointments: number
  }
  startDate: string
  endDate: string
}

export default function SalesAppointmentsBySeller ({
  summary,
  totals,
  startDate,
  endDate,
  auth
}: SalesAppointmentsBySellerProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Sales Appointments Report">
      <Head title="Sales Appointments Report" />

      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.sales-appointments-by-seller'), values, {
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
          href={route('report.sales-appointments-by-seller-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.sales-appointments-by-seller-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Appointments: {totals.appointments}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Salesperson</th>
            <th className="p-2 border">Appointments</th>
          </tr>
        </thead>
        <tbody>
          {summary.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={2}>
                No appointments found for the selected dates.
              </td>
            </tr>
          ) : (
            summary.map((item) => (
              <tr key={item.seller_id}>
                <td className="p-2 border">{item.seller_name}</td>
                <td className="p-2 border">{item.appointments_count}</td>
              </tr>
            ))
          )}
        </tbody>
        <tfoot>
          <tr className="bg-gray-100 font-semibold">
            <td className="p-2 border">Total</td>
            <td className="p-2 border">{totals.appointments}</td>
          </tr>
        </tfoot>
      </table>
    </AuthenticatedLayout>
  )
}
