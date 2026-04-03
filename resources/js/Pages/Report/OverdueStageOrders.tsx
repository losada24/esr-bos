import React from 'react'
import { Head, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface OverdueStageOrderRow {
  id: number
  order_name: string | null
  order_label: string
  seller_name: string
  created_by_name: string
  days_in_stage: number
  created_at: string | null
  stage_entered_at: string | null
}

interface OverdueStageSellerGroup {
  label: string
  source: string
  count: number
  rows: OverdueStageOrderRow[]
}

interface OverdueStageGroup {
  status: string
  threshold_label: string
  note: string
  is_configured: boolean
  count: number
  seller_groups: OverdueStageSellerGroup[]
}

type OverdueStageOrdersProps = PageProps & {
  generatedAt: string
  totals: {
    statuses: number
    configured_statuses: number
    orders: number
  }
  groups: OverdueStageGroup[]
}

const formatDateTime = (value?: string | null): string => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString()
}

export default function OverdueStageOrders ({ generatedAt, totals, groups, auth }: OverdueStageOrdersProps) {
  return (
    <AuthenticatedLayout auth={auth} pageTitle="Overdue Stage Orders">
      <Head title="Overdue Stage Orders" />

      <div className="space-y-6">
        <div className="flex flex-col gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm md:flex-row md:items-end md:justify-between">
          <div>
            <h1 className="text-xl font-semibold text-gray-900">Overdue Stage Orders</h1>
            <p className="mt-1 text-sm text-gray-600">
              Orders grouped by status using the same overdue logic as the pipeline color alerts.
            </p>
            <p className="mt-2 text-xs font-medium uppercase tracking-wide text-gray-500">
              Generated at: {formatDateTime(generatedAt)}
            </p>
          </div>

          <button
            type="button"
            className="btn btn-primary"
            onClick={() => {
              router.get(route('report.overdue-stage-orders'))
            }}
          >
            Refresh
          </button>
        </div>

        <div className="flex gap-2">
          <a
            className="btn btn-primary"
            href={route('report.overdue-stage-orders-pdf')}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a
            className="btn btn-secondary"
            href={route('report.overdue-stage-orders-excel')}
            target="_blank"
            rel="noopener noreferrer"
          >
            Export Excel
          </a>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Tracked Statuses</p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">{totals.statuses}</p>
          </div>
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Configured Statuses</p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">{totals.configured_statuses}</p>
          </div>
          <div className="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">Overdue Orders</p>
            <p className="mt-2 text-2xl font-semibold text-gray-900">{totals.orders}</p>
          </div>
        </div>

        <div className="space-y-5">
          {groups.map((group) => (
            <section key={group.status} className="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
              <div className="border-b border-gray-200 bg-gray-50 px-4 py-4">
                <div className="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                  <div>
                    <h2 className="text-lg font-semibold text-gray-900">
                      {group.status} ({group.count})
                    </h2>
                    <p className="mt-1 text-sm text-gray-600">{group.note}</p>
                  </div>

                  <div className="text-sm font-medium text-gray-700">
                    Threshold: {group.threshold_label}
                  </div>
                </div>
              </div>

              {group.seller_groups.length === 0 ? (
                <div className="px-4 py-6 text-sm text-gray-500">
                  {group.is_configured
                    ? 'No overdue orders in this status.'
                    : 'No overdue evaluation is available for this status because it has no threshold configured.'}
                </div>
              ) : (
                <div className="space-y-4 px-4 py-4">
                  {group.seller_groups.map((sellerGroup) => (
                    <div key={`${group.status}-${sellerGroup.label}`} className="overflow-hidden rounded-lg border border-gray-200">
                      <div className="flex flex-col gap-1 border-b border-gray-200 bg-gray-50 px-4 py-3 md:flex-row md:items-center md:justify-between">
                        <div className="text-sm font-semibold text-gray-900">
                          {sellerGroup.source === 'seller' ? 'Seller' : 'Created By'}: {sellerGroup.label}
                        </div>
                        <div className="text-xs font-medium uppercase tracking-wide text-gray-500">
                          Orders: {sellerGroup.count}
                        </div>
                      </div>

                      <div className="overflow-x-auto">
                        <table className="min-w-full">
                          <thead className="bg-gray-100">
                            <tr>
                              <th className="border-b p-3 text-left text-sm font-semibold text-gray-700">Order</th>
                              <th className="border-b p-3 text-left text-sm font-semibold text-gray-700">
                                {sellerGroup.source === 'seller' ? 'Seller' : 'Created By'}
                              </th>
                              <th className="border-b p-3 text-left text-sm font-semibold text-gray-700">Days In Stage</th>
                              <th className="border-b p-3 text-left text-sm font-semibold text-gray-700">Created At</th>
                              <th className="border-b p-3 text-left text-sm font-semibold text-gray-700">Entered Stage At</th>
                            </tr>
                          </thead>
                          <tbody>
                            {sellerGroup.rows.map((row) => (
                              <tr key={`${group.status}-${sellerGroup.label}-${row.id}`} className="odd:bg-white even:bg-gray-50">
                                <td className="border-b p-3 text-sm text-gray-800">{row.order_label}</td>
                                <td className="border-b p-3 text-sm text-gray-700">
                                  {sellerGroup.source === 'seller' ? (row.seller_name || '-') : (row.created_by_name || '-')}
                                </td>
                                <td className="border-b p-3 text-sm font-semibold text-gray-900">{row.days_in_stage}</td>
                                <td className="border-b p-3 text-sm text-gray-700">{formatDateTime(row.created_at)}</td>
                                <td className="border-b p-3 text-sm text-gray-700">{formatDateTime(row.stage_entered_at)}</td>
                              </tr>
                            ))}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </section>
          ))}
        </div>
      </div>
    </AuthenticatedLayout>
  )
}
