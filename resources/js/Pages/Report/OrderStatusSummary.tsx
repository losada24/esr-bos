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
  current_count: number
  previous_count: number
  delta: number
  percentage_change: number | null
  representation_percentage: number | null
}

type OrderStatusSummaryProps = PageProps & {
  statusSummary: StatusItem[]
  confirmedCount: number
  completedConfirmedCount: number
  completedFromConfirmedPercentage: number
  previousConfirmedCount: number
  previousCompletedConfirmedCount: number
  previousCompletedFromConfirmedPercentage: number
  startDate: string
  endDate: string
  previousStartDate: string
  previousEndDate: string
}

export default function OrderStatusSummary({
  statusSummary,
  confirmedCount,
  completedConfirmedCount,
  completedFromConfirmedPercentage,
  previousConfirmedCount,
  previousCompletedConfirmedCount,
  previousCompletedFromConfirmedPercentage,
  startDate,
  endDate,
  previousStartDate,
  previousEndDate,
  auth
}: OrderStatusSummaryProps) {
  const currentTotalCount = statusSummary.reduce((sum, item) => sum + item.current_count, 0)
  const previousTotalCount = statusSummary.reduce((sum, item) => sum + item.previous_count, 0)
  const totalDelta = currentTotalCount - previousTotalCount

  const formatPercentage = (value: number | null): string => {
    if (value === null) {
      return 'N/A'
    }

    return `${value.toFixed(2)}%`
  }

  const formatSignedPercentage = (value: number | null): string => {
    if (value === null) {
      return 'N/A'
    }

    const sign = value > 0 ? '+' : ''
    return `${sign}${value.toFixed(2)}%`
  }

  const formatSignedNumber = (value: number): string => {
    const sign = value > 0 ? '+' : ''
    return `${sign}${value}`
  }

  const getDeltaTextClass = (value: number | null): string => {
    if (value === null || value === 0) {
      return 'text-gray-700'
    }

    return value > 0 ? 'text-emerald-600' : 'text-rose-600'
  }

  const completedFromConfirmedDelta = completedFromConfirmedPercentage - previousCompletedFromConfirmedPercentage

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle="Order Status Summary"
    >
      <Head title="Order Status Summary" />
      <div className="space-y-6">
        <Formik
          initialValues={{
            start_date: startDate || '',
            end_date: endDate || '',
            previous_start_date: previousStartDate || '',
            previous_end_date: previousEndDate || ''
          }}
          onSubmit={(values) => {
            router.get(route('report.order-status-summary'), values, {
              preserveState: true
            })
          }}
        >
          {({ setFieldValue, values }) => (
            <Form className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
              <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                  <label htmlFor="start_date" className="block mb-1 font-semibold text-gray-700">Start Date</label>
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
                  <label htmlFor="end_date" className="block mb-1 font-semibold text-gray-700">End Date</label>
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

                <div>
                  <label htmlFor="previous_start_date" className="block mb-1 font-semibold text-gray-700">Previous Start Date</label>
                  <Flatpickr
                    id="previous_start_date"
                    value={values.previous_start_date}
                    options={{ dateFormat: 'Y-m-d' }}
                    onChange={([date]) => {
                      const formatted = date.toISOString().split('T')[0]
                      setFieldValue('previous_start_date', formatted)
                    }}
                    className="form-input border p-2 rounded w-full"
                  />
                  <ErrorMessage name="previous_start_date" component="div" className="text-red-500 text-sm mt-1" />
                </div>

                <div>
                  <label htmlFor="previous_end_date" className="block mb-1 font-semibold text-gray-700">Previous End Date</label>
                  <Flatpickr
                    id="previous_end_date"
                    value={values.previous_end_date}
                    options={{ dateFormat: 'Y-m-d' }}
                    onChange={([date]) => {
                      const formatted = date.toISOString().split('T')[0]
                      setFieldValue('previous_end_date', formatted)
                    }}
                    className="form-input border p-2 rounded w-full"
                  />
                  <ErrorMessage name="previous_end_date" component="div" className="text-red-500 text-sm mt-1" />
                </div>
              </div>

              <div className="mt-4 flex justify-end">
                <button
                  type="submit"
                  className="bg-blue-600 text-white px-5 py-2 rounded hover:bg-blue-700"
                >
                  Filter
                </button>
              </div>
            </Form>
          )}
        </Formik>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Current Period</p>
            <p className="mt-1 text-sm font-semibold text-gray-800">{startDate} to {endDate}</p>
            <p className="mt-3 text-xs uppercase tracking-wide text-gray-500">Total Orders</p>
            <p className="text-2xl font-semibold text-gray-900">{currentTotalCount}</p>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Previous Period</p>
            <p className="mt-1 text-sm font-semibold text-gray-800">{previousStartDate} to {previousEndDate}</p>
            <p className="mt-3 text-xs uppercase tracking-wide text-gray-500">Total Orders</p>
            <p className="text-2xl font-semibold text-gray-900">{previousTotalCount}</p>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Total Delta</p>
            <p className={`mt-4 text-2xl font-semibold ${getDeltaTextClass(totalDelta)}`}>
              {formatSignedNumber(totalDelta)}
            </p>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Completed From Confirmed (pp)</p>
            <p className={`mt-4 text-2xl font-semibold ${getDeltaTextClass(completedFromConfirmedDelta)}`}>
              {formatSignedPercentage(Number(completedFromConfirmedDelta.toFixed(2)))}
            </p>
          </div>
        </div>

        <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
          <div className="grid grid-cols-1 gap-3 md:grid-cols-2">
            <p className="text-sm font-semibold text-gray-700">
              Completed from Confirmed (Current): {completedFromConfirmedPercentage.toFixed(2)}% ({completedConfirmedCount}/{confirmedCount})
            </p>
            <p className="text-sm font-semibold text-gray-700">
              Completed from Confirmed (Previous): {previousCompletedFromConfirmedPercentage.toFixed(2)}% ({previousCompletedConfirmedCount}/{previousConfirmedCount})
            </p>
          </div>
        </div>

        <div className="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
          <div className="overflow-x-auto">
            <table className="min-w-full">
              <thead className="bg-gray-100">
                <tr>
                  <th className="p-3 border-b text-left">Status</th>
                  <th className="p-3 border-b text-right">Previous</th>
                  <th className="p-3 border-b text-right">Current</th>
                  <th className="p-3 border-b text-right">Delta</th>
                  <th className="p-3 border-b text-right">% Change</th>
                  <th className="p-3 border-b text-right">% Representation</th>
                </tr>
              </thead>
              <tbody>
                {statusSummary.length === 0 ? (
                  <tr>
                    <td className="p-3 text-center text-gray-500" colSpan={6}>No data for selected periods.</td>
                  </tr>
                ) : (
                  statusSummary.map((item) => (
                    <tr key={item.status} className="odd:bg-white even:bg-gray-50">
                      <td className="p-3 border-b font-medium text-gray-800">{item.status}</td>
                      <td className="p-3 border-b text-right text-gray-700">{item.previous_count}</td>
                      <td className="p-3 border-b text-right text-gray-900 font-semibold">{item.current_count}</td>
                      <td className={`p-3 border-b text-right font-semibold ${getDeltaTextClass(item.delta)}`}>{formatSignedNumber(item.delta)}</td>
                      <td className={`p-3 border-b text-right font-semibold ${getDeltaTextClass(item.percentage_change)}`}>{formatSignedPercentage(item.percentage_change)}</td>
                      <td className="p-3 border-b text-right text-gray-700">{formatPercentage(item.representation_percentage)}</td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
