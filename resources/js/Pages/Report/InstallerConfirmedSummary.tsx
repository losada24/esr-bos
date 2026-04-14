import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'
import { formatPrice } from '@/Utils/price'

interface InstallerSummaryItem {
  id: number | null
  company_name: string | null
  installer_name: string | null
  confirmed_orders: number
  completed_orders: number
  assigned_amount: number
}

type InstallerConfirmedSummaryProps = PageProps & {
  summary: InstallerSummaryItem[]
  totalConfirmed: number
  totalCompleted: number
  totalAssigned: number
  startDate: string
  endDate: string
}

export default function InstallerConfirmedSummary({ summary, totalConfirmed, totalCompleted, totalAssigned, startDate, endDate, auth }: InstallerConfirmedSummaryProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Installer Confirmed Orders"
    >
      <Head title="Installer Confirmed Orders" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.installer-confirmed-summary'), values, {
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
          href={route('report.installer-confirmed-summary-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.installer-confirmed-summary-excel') + exportQuery}
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
        Total Completed Orders: {totalCompleted}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Project Payment: {formatPrice(Number(totalAssigned))}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Installer</th>
            <th className="p-2 border">Company</th>
            <th className="p-2 border">Confirmed Orders</th>
            <th className="p-2 border">Completed Orders</th>
            <th className="p-2 border">Total Project Payment</th>
          </tr>
        </thead>
        <tbody>
          {summary.map((item) => {
            const isUnassigned = !item.installer_name
            return (
              <tr key={item.id ?? 'unassigned'} className={isUnassigned ? 'bg-gray-100' : ''}>
                <td className="p-2 border">{item.installer_name || 'PICKUP OR DELIVERY ONLY'}</td>
                <td className="p-2 border">{item.company_name || '-'}</td>
                <td className="p-2 border">{item.confirmed_orders}</td>
                <td className="p-2 border">{item.completed_orders}</td>
                <td className="p-2 border">{formatPrice(Number(item.assigned_amount || 0))}</td>
              </tr>
            )
          })}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
