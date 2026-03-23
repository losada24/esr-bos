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
  lost_request: number
}

interface OrderListItem {
  id: number
  name: string
  created_date: string | null
  current_status: string
  label: string
  status_date?: string | null
  loss_reason_frontdesk?: string | null
}

type DailyOrderStatusSummaryProps = PageProps & {
  dailySummary: DailySummaryRow[]
  totals: {
    total: number
    qualified: number
    estimate_appt_schedule: number
    lost_request: number
    total_orders: number
  }
  orderLists: {
    total: OrderListItem[]
    qualified: OrderListItem[]
    estimate_appt_schedule: OrderListItem[]
    lost_request: OrderListItem[]
  }
  startDate: string
  endDate: string
}

export default function DailyOrderStatusSummary({ dailySummary, totals, orderLists, startDate, endDate, auth }: DailyOrderStatusSummaryProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`
  const formatLocalDate = (date: Date): string => {
    const year = date.getFullYear()
    const month = String(date.getMonth() + 1).padStart(2, '0')
    const day = String(date.getDate()).padStart(2, '0')

    return `${year}-${month}-${day}`
  }

  const renderOrderListTable = (
    title: string,
    rows: OrderListItem[],
    emptyMessage: string,
    options?: {
      statusDateLabel?: string
      showStatusDate?: boolean
      showLossReason?: boolean
    }
  ) => (
    <div className="rounded border border-gray-300 p-3">
      <h3 className="font-semibold text-gray-800">{title} ({rows.length})</h3>
      {rows.length === 0 ? (
        <p className="mt-2 text-sm text-gray-500">{emptyMessage}</p>
      ) : (
        <table className="mt-2 w-full border border-gray-300 text-sm">
          <thead className="bg-gray-100">
            <tr>
              <th className="border p-2 text-left">Order</th>
              <th className="border p-2 text-left">Created Date</th>
              {options?.showStatusDate ? (
                <th className="border p-2 text-left">{options.statusDateLabel || 'Status Date'}</th>
              ) : null}
              <th className="border p-2 text-left">Current Status</th>
              {options?.showLossReason ? (
                <th className="border p-2 text-left">Loss Reason</th>
              ) : null}
            </tr>
          </thead>
          <tbody>
            {rows.map((order) => (
              <tr key={order.id}>
                <td className="border p-2">{order.name ? `#${order.id} - ${order.name}` : `#${order.id}`}</td>
                <td className="border p-2">{order.created_date || '-'}</td>
                {options?.showStatusDate ? (
                  <td className="border p-2">{order.status_date || '-'}</td>
                ) : null}
                <td className="border p-2">{order.current_status || '-'}</td>
                {options?.showLossReason ? (
                  <td className="border p-2">{order.loss_reason_frontdesk || '-'}</td>
                ) : null}
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  )

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
                  const formatted = formatLocalDate(date)
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
                  const formatted = formatLocalDate(date)
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
      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Lost Request: {totals.lost_request}
      </div>

      <div className="mt-6 grid grid-cols-1 gap-4">
        {renderOrderListTable('Total Orders List', orderLists.total, 'No orders for the selected dates.')}
        {renderOrderListTable('Qualified Orders List', orderLists.qualified, 'No qualified orders for the selected dates.')}
        {renderOrderListTable('Estimate & Appt Schedule Orders List', orderLists.estimate_appt_schedule, 'No estimate & appt schedule orders for the selected dates.')}
        {renderOrderListTable(
          'Lost Request Orders List',
          orderLists.lost_request,
          'No lost request orders for the selected dates.',
          {
            showStatusDate: true,
            statusDateLabel: 'Lost Request Date',
            showLossReason: true,
          }
        )}
      </div>

      <table className="w-full border border-gray-300 mt-4">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Date</th>
            <th className="p-2 border">Total</th>
            <th className="p-2 border">Qualified</th>
            <th className="p-2 border">Estimate &amp; Appt Schedule</th>
            <th className="p-2 border">Lost Request</th>
          </tr>
        </thead>
        <tbody>
          {dailySummary.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={5}>No data for the selected dates.</td>
            </tr>
          ) : (
            dailySummary.map((row) => (
              <tr key={row.date}>
                <td className="p-2 border">{row.date}</td>
                <td className="p-2 border">{row.new_request_qualified}</td>
                <td className="p-2 border">{row.qualified}</td>
                <td className="p-2 border">{row.estimate_appt_schedule}</td>
                <td className="p-2 border">{row.lost_request}</td>
              </tr>
            ))
          )}
        </tbody>
      </table>
    </AuthenticatedLayout>
  )
}
