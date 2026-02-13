import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface AccountingStatusRow {
  id: number
  status: string
  order_name: string | null
  owner: string
  amount: number | string | null
  status_date: string | null
}

type AccountingStatusSummaryProps = PageProps & {
  rows: AccountingStatusRow[]
  totals: {
    total: number
    account_receipt: number
    complete: number
  }
  startDate: string
  endDate: string
}

function formatCurrency(value: number | string | null): string {
  const numericValue = Number(value ?? 0)

  if (Number.isNaN(numericValue)) {
    return '$0.00'
  }

  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD'
  }).format(numericValue)
}

export default function AccountingStatusSummary({ rows, totals, startDate, endDate, auth }: AccountingStatusSummaryProps) {
  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Accounting Status Summary"
    >
      <Head title="Accounting Status Summary" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.accounting-status-summary'), values, {
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

      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Rows: {totals.total}
      </div>
      <div className="mt-1 text-left font-semibold text-gray-700">
        ACCOUNT RECEIPT: {totals.account_receipt}
      </div>
      <div className="mt-1 text-left font-semibold text-gray-700">
        COMPLETE: {totals.complete}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Status</th>
            <th className="p-2 border">Order Name</th>
            <th className="p-2 border">Owner</th>
            <th className="p-2 border">Amount</th>
            <th className="p-2 border">Status Date</th>
          </tr>
        </thead>
        <tbody>
          {rows.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={5}>No data for the selected dates.</td>
            </tr>
          ) : (
            rows.map((row) => (
              <tr key={row.id}>
                <td className="p-2 border">{row.status}</td>
                <td className="p-2 border">{row.order_name || '-'}</td>
                <td className="p-2 border">{row.owner || '-'}</td>
                <td className="p-2 border">{formatCurrency(row.amount)}</td>
                <td className="p-2 border">{row.status_date || '-'}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
