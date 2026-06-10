import React from 'react'
import { Head, router } from '@inertiajs/react'
import { Formik, Form, ErrorMessage } from 'formik'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface MarketingClient {
  id: number
  name: string
  source: string
  created_at: string | null
  appointment_date?: string | null
  qualified_orders_count?: number
  first_qualified_at?: string | null
  last_qualified_at?: string | null
  lost_orders_count?: number
  loss_reasons?: string | null
  first_lost_order_at?: string | null
  last_lost_order_at?: string | null
}

type MarketingReportProps = PageProps & {
  qualifiedClients: MarketingClient[]
  qualifiedClientsWithAppointment: MarketingClient[]
  lostClients: MarketingClient[]
  totals: {
    total_clients: number
    qualified_clients: number
    qualified_clients_with_appointment: number
    lost_clients: number
    qualified_orders: number
    lost_orders: number
    grand_total_clients: number
    qualified_clients_by_source: Record<string, number>
    lost_clients_by_reason: Record<string, number>
  }
  filters: {
    sources: string[]
    qualified_status: string
    lost_status: string
    loss_reasons: string[]
  }
  startDate: string
  endDate: string
}

export default function MarketingReport ({
  qualifiedClients,
  qualifiedClientsWithAppointment,
  lostClients,
  totals,
  filters,
  startDate,
  endDate,
  auth
}: MarketingReportProps) {
  const exportQuery = `?start_date=${startDate || ''}&end_date=${endDate || ''}`

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Marketing Report">
      <Head title="Marketing Report" />

      <Formik
        initialValues={{
          start_date: startDate || '',
          end_date: endDate || ''
        }}
        onSubmit={(values) => {
          router.get(route('report.marketing'), values, { preserveState: true })
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
          href={route('report.marketing-pdf') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          View PDF
        </a>
        <a
          className="btn btn-secondary"
          href={route('report.marketing-excel') + exportQuery}
          target="_blank"
          rel="noopener noreferrer"
        >
          Export Excel
        </a>
      </div>

      <div className="mt-2 text-left font-semibold text-gray-700">
        Total Clients from Sources (Instagram/Facebook + Google Ads): {totals.total_clients}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Qualified Clients: {totals.qualified_clients}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Qualified Clients with Appointment: {totals.qualified_clients_with_appointment}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Lost Request Clients: {totals.lost_clients}
      </div>
      <div className="mt-2 text-left font-semibold text-gray-700">
        Grand Total Clients (Qualified + Lost): {totals.grand_total_clients}
      </div>
      <div className="mt-4 grid gap-4 md:grid-cols-2">
        <div>
          <h4 className="mb-2 text-sm font-semibold text-slate-700">Qualified Clients by Source</h4>
          <table className="w-full border border-gray-300 text-sm">
            <thead className="bg-gray-100">
              <tr>
                <th className="p-2 border">Source</th>
                <th className="p-2 border">Total</th>
              </tr>
            </thead>
            <tbody>
              {['INSTAGRAM/FACEBOOK', 'GOOGLE ADS'].map((source) => (
                <tr key={`qualified-source-${source}`}>
                  <td className="p-2 border">{source}</td>
                  <td className="p-2 border">{totals.qualified_clients_by_source?.[source] ?? 0}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-gray-100 font-semibold">
                <td className="p-2 border">Total</td>
                <td className="p-2 border">
                  {['INSTAGRAM/FACEBOOK', 'GOOGLE ADS']
                    .reduce((sum, source) => sum + (totals.qualified_clients_by_source?.[source] ?? 0), 0)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
        <div>
          <h4 className="mb-2 text-sm font-semibold text-slate-700">Lost Clients by Reason</h4>
          <table className="w-full border border-gray-300 text-sm">
            <thead className="bg-gray-100">
              <tr>
                <th className="p-2 border">Reason</th>
                <th className="p-2 border">Total</th>
              </tr>
            </thead>
            <tbody>
              {filters.loss_reasons.map((reason) => (
                <tr key={`lost-reason-${reason}`}>
                  <td className="p-2 border">{reason}</td>
                  <td className="p-2 border">{totals.lost_clients_by_reason?.[reason] ?? 0}</td>
                </tr>
              ))}
            </tbody>
            <tfoot>
              <tr className="bg-gray-100 font-semibold">
                <td className="p-2 border">Total</td>
                <td className="p-2 border">
                  {filters.loss_reasons.reduce((sum, reason) => sum + (totals.lost_clients_by_reason?.[reason] ?? 0), 0)}
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
      </div>

      <h3 className="mt-6 mb-2 text-lg font-semibold">Qualified Clients With Appointment</h3>
      <table className="w-full border border-gray-300">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Client</th>
            <th className="p-2 border">Source</th>
            <th className="p-2 border">Appointment Date</th>
          </tr>
        </thead>
        <tbody>
          {qualifiedClientsWithAppointment.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={3}>No qualified clients with appointment for the selected dates.</td>
            </tr>
          ) : (
            qualifiedClientsWithAppointment.map((row) => (
              <tr key={`qualified-appt-${row.id}`}>
                <td className="p-2 border">{row.name}</td>
                <td className="p-2 border">{row.source}</td>
                <td className="p-2 border">{row.appointment_date || '-'}</td>
              </tr>
            ))
          )}
        </tbody>
        <tfoot>
          <tr className="bg-gray-100 font-semibold">
            <td className="p-2 border" colSpan={2}>Total</td>
            <td className="p-2 border">{totals.qualified_clients_with_appointment}</td>
          </tr>
        </tfoot>
      </table>

      <h3 className="mt-6 mb-2 text-lg font-semibold">Qualified Orders by Client</h3>
      <table className="w-full border border-gray-300">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Client</th>
            <th className="p-2 border">Source</th>
            <th className="p-2 border">Client Created</th>
            <th className="p-2 border">Qualified Orders</th>
          </tr>
        </thead>
        <tbody>
          {qualifiedClients.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={4}>No qualified clients for the selected dates.</td>
            </tr>
          ) : (
            qualifiedClients.map((row) => (
              <tr key={`qualified-${row.id}`}>
                <td className="p-2 border">{row.name}</td>
                <td className="p-2 border">{row.source}</td>
                <td className="p-2 border">{row.created_at || '-'}</td>
                <td className="p-2 border">{row.qualified_orders_count ?? 0}</td>
              </tr>
            ))
          )}
        </tbody>
        <tfoot>
          <tr className="bg-gray-100 font-semibold">
            <td className="p-2 border" colSpan={3}>Total Qualified Orders</td>
            <td className="p-2 border">
              {qualifiedClients.reduce((sum, row) => sum + (row.qualified_orders_count ?? 0), 0)}
            </td>
          </tr>
        </tfoot>
      </table>

      <h3 className="mt-8 mb-2 text-lg font-semibold">Lost Request Clients</h3>
      <table className="w-full border border-gray-300">
        <thead className="bg-gray-100">
          <tr>
            <th className="p-2 border">Client</th>
            <th className="p-2 border">Source</th>
            <th className="p-2 border">Client Created</th>
            <th className="p-2 border">Lost Orders</th>
            <th className="p-2 border">Loss Reasons</th>
          </tr>
        </thead>
        <tbody>
          {lostClients.length === 0 ? (
            <tr>
              <td className="p-2 border text-center" colSpan={5}>No lost request clients for the selected dates.</td>
            </tr>
          ) : (
            lostClients.map((row) => (
              <tr key={`lost-${row.id}`}>
                <td className="p-2 border">{row.name}</td>
                <td className="p-2 border">{row.source}</td>
                <td className="p-2 border">{row.created_at || '-'}</td>
                <td className="p-2 border">{row.lost_orders_count ?? 0}</td>
                <td className="p-2 border">{row.loss_reasons || '-'}</td>
              </tr>
            ))
          )}
        </tbody>
        <tfoot>
          <tr className="bg-gray-100 font-semibold">
            <td className="p-2 border" colSpan={3}>Total</td>
            <td className="p-2 border">
              {lostClients.reduce((sum, row) => sum + (row.lost_orders_count ?? 0), 0)}
            </td>
            <td className="p-2 border"></td>
          </tr>
        </tfoot>
      </table>
    </AuthenticatedLayout>
  )
}
