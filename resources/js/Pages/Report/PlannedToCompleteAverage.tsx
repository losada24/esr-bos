import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

type ReportRow = {
  id: number
  order_number: string | null
  name: string
  service: string | null
  order_type: string | null
  type_of_housing: string | null
  start_at: string
  completed_at: string
  duration_days: number
  duration_label: string
}

type ServiceOption = {
  label: string
  value: string
}

type PlannedToCompleteAverageProps = PageProps & {
  rows: ReportRow[]
  totalOrders: number
  averageDurationDays: number
  averageDurationLabel: string
  transitionType: 'planned_completed' | 'confirmed_completed'
  transitionLabel: string
  businessType: 'all' | 'residential' | 'commercial'
  serviceType: string
  serviceOptions: ServiceOption[]
  startDate: string
  endDate: string
}

export default function PlannedToCompleteAverage ({
  rows,
  totalOrders,
  averageDurationDays,
  averageDurationLabel,
  transitionType,
  transitionLabel,
  businessType,
  serviceType,
  serviceOptions,
  startDate,
  endDate,
  auth
}: PlannedToCompleteAverageProps) {
  const startStatusLabel = transitionType === 'confirmed_completed' ? 'Confirmed At' : 'Planned At'
  const exportQuery = `?${new URLSearchParams({
    transition_type: transitionType || 'planned_completed',
    business_type: businessType || 'all',
    service: serviceType || 'all',
    start_date: startDate || '',
    end_date: endDate || ''
  }).toString()}`

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Status Transition Average">
      <Head title="Status Transition Average" />

      <div className="space-y-6">
        <Formik
          initialValues={{
            transition_type: transitionType || 'planned_completed',
            business_type: businessType || 'all',
            service: serviceType || 'all',
            start_date: startDate || '',
            end_date: endDate || ''
          }}
          onSubmit={(values) => {
            router.get(route('report.planned-to-complete-average'), values, {
              preserveState: true
            })
          }}
        >
          {({ values, setFieldValue }) => (
            <Form className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
              <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
                <div>
                  <label htmlFor="transition_type" className="mb-1 block font-semibold text-gray-700">Transition</label>
                  <select
                    id="transition_type"
                    name="transition_type"
                    value={values.transition_type}
                    onChange={(e) => setFieldValue('transition_type', e.target.value)}
                    className="form-select w-full rounded border p-2"
                  >
                    <option value="planned_completed">PLANNED / COMPLETED</option>
                    <option value="confirmed_completed">CONFIRMED / COMPLETED</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="business_type" className="mb-1 block font-semibold text-gray-700">Type</label>
                  <select
                    id="business_type"
                    name="business_type"
                    value={values.business_type}
                    onChange={(e) => setFieldValue('business_type', e.target.value)}
                    className="form-select w-full rounded border p-2"
                  >
                    <option value="all">ALL</option>
                    <option value="residential">Residential</option>
                    <option value="commercial">Commercial</option>
                  </select>
                </div>

                <div>
                  <label htmlFor="service" className="mb-1 block font-semibold text-gray-700">Service</label>
                  <select
                    id="service"
                    name="service"
                    value={values.service}
                    onChange={(e) => setFieldValue('service', e.target.value)}
                    className="form-select w-full rounded border p-2"
                  >
                    {serviceOptions.map((option) => (
                      <option key={option.value} value={option.value}>{option.label}</option>
                    ))}
                  </select>
                </div>

                <div>
                  <label htmlFor="start_date" className="mb-1 block font-semibold text-gray-700">Start Date</label>
                  <Flatpickr
                    id="start_date"
                    value={values.start_date}
                    options={{ dateFormat: 'Y-m-d' }}
                    onChange={([date]) => {
                      setFieldValue('start_date', date ? date.toISOString().split('T')[0] : '')
                    }}
                    className="form-input w-full rounded border p-2"
                  />
                  <ErrorMessage name="start_date" component="div" className="mt-1 text-sm text-red-500" />
                </div>

                <div>
                  <label htmlFor="end_date" className="mb-1 block font-semibold text-gray-700">End Date</label>
                  <Flatpickr
                    id="end_date"
                    value={values.end_date}
                    options={{ dateFormat: 'Y-m-d' }}
                    onChange={([date]) => {
                      setFieldValue('end_date', date ? date.toISOString().split('T')[0] : '')
                    }}
                    className="form-input w-full rounded border p-2"
                  />
                  <ErrorMessage name="end_date" component="div" className="mt-1 text-sm text-red-500" />
                </div>
              </div>

              <div className="mt-4 flex justify-end">
                <button
                  type="submit"
                  className="rounded bg-blue-600 px-5 py-2 text-white hover:bg-blue-700"
                >
                  Filter
                </button>
              </div>
            </Form>
          )}
        </Formik>

        <div className="flex gap-2">
          <a
            className="btn btn-primary"
            href={route('report.planned-to-complete-average-pdf') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a
            className="btn btn-secondary"
            href={route('report.planned-to-complete-average-excel') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            Export Excel
          </a>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Filters</p>
            <p className="mt-1 text-sm font-semibold text-gray-900">{transitionLabel}</p>
            <p className="mt-1 text-xl font-semibold text-gray-900">
              {businessType === 'all' ? 'ALL TYPES' : (businessType === 'commercial' ? 'Commercial' : 'Residential')}
            </p>
            <p className="mt-1 text-sm text-gray-700">{serviceType === 'all' ? 'ALL SERVICES' : serviceType}</p>
            <p className="mt-3 text-xs text-gray-500">{startDate} to {endDate}</p>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Orders</p>
            <p className="mt-1 text-2xl font-semibold text-gray-900">{totalOrders}</p>
          </div>

          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Average {transitionLabel}</p>
            <p className="mt-1 text-2xl font-semibold text-gray-900">{averageDurationDays} days</p>
            <p className="mt-1 text-sm text-gray-600">{averageDurationLabel}</p>
          </div>
        </div>

        <div className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
          <div className="overflow-x-auto">
            <table className="min-w-full">
              <thead className="bg-gray-100">
                <tr>
                  <th className="p-3 text-left">Order #</th>
                  <th className="p-3 text-left">Order Name</th>
                  <th className="p-3 text-left">Service</th>
                  <th className="p-3 text-left">Order Type</th>
                  <th className="p-3 text-left">Type of Housing</th>
                  <th className="p-3 text-left">{startStatusLabel}</th>
                  <th className="p-3 text-left">Completed At</th>
                  <th className="p-3 text-right">Duration (Days)</th>
                  <th className="p-3 text-left">Duration</th>
                </tr>
              </thead>
              <tbody>
                {rows.length === 0 ? (
                  <tr>
                    <td className="p-3 text-center text-gray-500" colSpan={9}>
                      No orders found for selected filters.
                    </td>
                  </tr>
                ) : (
                  rows.map((row) => (
                    <tr key={row.id} className="odd:bg-white even:bg-gray-50">
                      <td className="border-b p-3">{row.order_number || '-'}</td>
                      <td className="border-b p-3">{row.name}</td>
                      <td className="border-b p-3">{row.service || '-'}</td>
                      <td className="border-b p-3">{row.order_type || '-'}</td>
                      <td className="border-b p-3">{row.type_of_housing || '-'}</td>
                      <td className="border-b p-3">{row.start_at}</td>
                      <td className="border-b p-3">{row.completed_at}</td>
                      <td className="border-b p-3 text-right font-semibold">{row.duration_days}</td>
                      <td className="border-b p-3">{row.duration_label}</td>
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
