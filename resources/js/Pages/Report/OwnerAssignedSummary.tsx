import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'
import { formatPrice } from '@/Utils/price'

interface OwnerSummaryItem {
  owner_id: number | null
  owner_name: string | null
  estimate_orders: number
  estimated_clients: number
  estimate_amount: number
  closed_won_orders: number
  closed_won_amount: number
  closed_won_orders_percentage: number
  closed_won_amount_percentage: number
}

type OwnerAssignedSummaryProps = PageProps & {
  summary: OwnerSummaryItem[]
  totalEstimateOrders: number
  totalEstimatedClients: number
  totalEstimateAmount: number
  totalClosedWonOrders: number
  totalClosedWonAmount: number
  totalClosedWonOrdersPercentage: number
  totalClosedWonAmountPercentage: number
  startDate: string
  endDate: string
}

function formatPercentage(value: number) {
  return `${Number(value || 0).toFixed(2)}%`
}

export default function OwnerAssignedSummary({
  summary,
  totalEstimateOrders,
  totalEstimatedClients,
  totalEstimateAmount,
  totalClosedWonOrders,
  totalClosedWonAmount,
  totalClosedWonOrdersPercentage,
  totalClosedWonAmountPercentage,
  startDate,
  endDate,
  auth
}: OwnerAssignedSummaryProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Owner Report">
      <Head title="Owner Report" />
      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.owner-assigned-summary'), values, {
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
          href={route('report.owner-assigned-summary-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.owner-assigned-summary-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-4 text-left font-semibold text-gray-700">
        Total Assigned Clients: {totalEstimateOrders}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Estimated Clients: {totalEstimatedClients}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Estimate Amount (Project Amount): {formatPrice(Number(totalEstimateAmount))}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Closed Won Orders: {totalClosedWonOrders}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Closed Won Amount: {formatPrice(Number(totalClosedWonAmount))}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Salesperson</th>
            <th className="p-2 border">Total Assigned Clients</th>
            <th className="p-2 border">Estimated Clients</th>
            <th className="p-2 border">Estimate Amount (Project Amount)</th>
            <th className="p-2 border">Closed Won Orders</th>
            <th className="p-2 border">% Closed Won Orders</th>
            <th className="p-2 border">Closed Won Amount</th>
            <th className="p-2 border">% Closed Won Amount</th>
          </tr>
        </thead>
        <tbody>
          {summary.map((item) => (
            <tr key={item.owner_id ?? 'unassigned'}>
              <td className="p-2 border">{item.owner_name || 'UNASSIGNED OWNER'}</td>
              <td className="p-2 border">{item.estimate_orders}</td>
              <td className="p-2 border">{item.estimated_clients}</td>
              <td className="p-2 border">{formatPrice(Number(item.estimate_amount || 0))}</td>
              <td className="p-2 border">{item.closed_won_orders}</td>
              <td className="p-2 border">{formatPercentage(item.closed_won_orders_percentage)}</td>
              <td className="p-2 border">{formatPrice(Number(item.closed_won_amount || 0))}</td>
              <td className="p-2 border">{formatPercentage(item.closed_won_amount_percentage)}</td>
            </tr>
          ))}
        </tbody>
        <tfoot>
          <tr className="bg-gray-100 font-semibold">
            <td className="p-2 border">Totals</td>
            <td className="p-2 border">{totalEstimateOrders}</td>
            <td className="p-2 border">{totalEstimatedClients}</td>
            <td className="p-2 border">{formatPrice(Number(totalEstimateAmount))}</td>
            <td className="p-2 border">{totalClosedWonOrders}</td>
            <td className="p-2 border">{formatPercentage(totalClosedWonOrdersPercentage)}</td>
            <td className="p-2 border">{formatPrice(Number(totalClosedWonAmount))}</td>
            <td className="p-2 border">{formatPercentage(totalClosedWonAmountPercentage)}</td>
          </tr>
        </tfoot>
      </table>
    </AuthenticatedLayout>
  )
}
