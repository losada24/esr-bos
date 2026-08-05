import React, { useEffect, useMemo, useState } from 'react'
import { Head, router } from '@inertiajs/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { type PageProps } from '@/types'
import { formatPrice } from '@/Utils/price'

interface SellerOption {
  id: number
  name: string
}

interface OverdueStageOrderRow {
  id: number
  order_name: string | null
  order_label: string
  project_amount: number
  seller_name: string
  created_by_name: string
  days_in_stage: number
  created_at: string | null
  stage_entered_at: string | null
  is_overdue: boolean
}

interface OverdueStageGroup {
  status: string
  threshold_label: string
  note: string
  is_configured: boolean
  count: number
  overdue_count: number
  amount_total: number
  rows: OverdueStageOrderRow[]
}

type OverdueStageOrdersProps = PageProps & {
  generatedAt: string
  sellers: SellerOption[]
  statusOptions: string[]
  orderTypeOptions: string[]
  productLineOptions: string[]
  filters: {
    seller_id: number | null
    overdue_only: boolean
    statuses: string[]
    order_types: string[]
    product_lines: string[]
  }
  totals: {
    statuses: number
    configured_statuses: number
    orders: number
    overdue_orders: number
    amount: number
  }
  groups: OverdueStageGroup[]
}

const formatDateTime = (value?: string | null): string => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString()
}

export default function OverdueStageOrders ({
  generatedAt,
  sellers,
  statusOptions,
  orderTypeOptions,
  productLineOptions,
  filters,
  totals,
  groups,
  auth
}: OverdueStageOrdersProps) {
  const [sellerId, setSellerId] = useState(filters.seller_id ? String(filters.seller_id) : '')
  const [overdueOnly, setOverdueOnly] = useState(Boolean(filters.overdue_only))
  const [selectedStatuses, setSelectedStatuses] = useState<string[]>(filters.statuses ?? [])
  const [selectedOrderTypes, setSelectedOrderTypes] = useState<string[]>(filters.order_types ?? [])
  const [selectedProductLines, setSelectedProductLines] = useState<string[]>(filters.product_lines ?? [])
  const [openStatuses, setOpenStatuses] = useState<Record<string, boolean>>(() => {
    return groups.reduce<Record<string, boolean>>((carry, group) => {
      carry[group.status] = group.count > 0
      return carry
    }, {})
  })

  useEffect(() => {
    setOpenStatuses(groups.reduce<Record<string, boolean>>((carry, group) => {
      carry[group.status] = group.count > 0
      return carry
    }, {}))
  }, [groups])

  const exportQuery = useMemo(() => {
    const params = new URLSearchParams()
    if (sellerId) params.set('seller_id', sellerId)
    if (overdueOnly) params.set('overdue_only', '1')
    selectedStatuses.forEach((status) => params.append('statuses[]', status))
    selectedOrderTypes.forEach((type) => params.append('order_types[]', type))
    selectedProductLines.forEach((line) => params.append('product_lines[]', line))
    const query = params.toString()
    return query ? `?${query}` : ''
  }, [sellerId, overdueOnly, selectedStatuses, selectedOrderTypes, selectedProductLines])

  const selectedSellerName = sellers.find((seller) => String(seller.id) === sellerId)?.name ?? 'All sellers'
  const selectedStatusLabel = selectedStatuses.length > 0 ? `${selectedStatuses.length} selected` : 'All statuses'
  const selectedOrderTypeLabel = selectedOrderTypes.length > 0 ? selectedOrderTypes.join(', ') : 'All order types'
  const selectedProductLineLabel = selectedProductLines.length > 0 ? selectedProductLines.join(', ') : 'All product lines'

  const applyFilters = () => {
    router.get(route('report.overdue-stage-orders'), {
      seller_id: sellerId || undefined,
      overdue_only: overdueOnly ? 1 : undefined,
      statuses: selectedStatuses.length ? selectedStatuses : undefined,
      order_types: selectedOrderTypes.length ? selectedOrderTypes : undefined,
      product_lines: selectedProductLines.length ? selectedProductLines : undefined
    }, {
      preserveState: true
    })
  }

  const resetFilters = () => {
    setSellerId('')
    setOverdueOnly(false)
    setSelectedStatuses([])
    setSelectedOrderTypes([])
    setSelectedProductLines([])
    router.get(route('report.overdue-stage-orders'), {}, {
      preserveState: true,
      replace: true
    })
  }

  const toggleFilterValue = (value: string, selectedValues: string[], setter: (values: string[]) => void) => {
    setter(
      selectedValues.includes(value)
        ? selectedValues.filter((selectedValue) => selectedValue !== value)
        : [...selectedValues, value]
    )
  }

  const toggleStatus = (status: string) => {
    setOpenStatuses((current) => ({
      ...current,
      [status]: !current[status]
    }))
  }

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      pageTitle="Overdue Stage Orders"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <select
            value={sellerId}
            className="form-select w-56"
            onChange={(event) => setSellerId(event.target.value)}
          >
            <option value="">All sellers</option>
            {sellers.map((seller) => (
              <option key={seller.id} value={seller.id}>{seller.name}</option>
            ))}
          </select>
          <label className="flex h-10 items-center gap-2 rounded border border-slate-200 bg-white px-3 text-sm font-medium text-slate-600">
            <input
              type="checkbox"
              checked={overdueOnly}
              onChange={(event) => setOverdueOnly(event.target.checked)}
            />
            Overdue only
          </label>
          <button type="button" className="btn btn-primary" onClick={applyFilters}>Filter</button>
          <button type="button" className="btn btn-outline-primary" onClick={resetFilters}>Reset</button>
          <a
            className="btn btn-outline-primary"
            href={route('report.overdue-stage-orders-pdf') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            View PDF
          </a>
          <a
            className="btn btn-outline-primary"
            href={route('report.overdue-stage-orders-excel') + exportQuery}
            target="_blank"
            rel="noopener noreferrer"
          >
            Export Excel
          </a>
        </div>
      }
    >
      <Head title="Overdue Stage Orders" />

      <div className="space-y-6">
        <div className="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-4 text-sm text-sky-900">
          Orders by seller and status. Overdue rows use the configured pipeline alert thresholds. Generated at: {formatDateTime(generatedAt)}
        </div>

        <div className="panel space-y-4">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div>
              <h2 className="text-base font-semibold text-slate-800">Report Filters</h2>
              <p className="text-sm text-slate-400">Select the statuses, order types, and product lines to include.</p>
            </div>
            <div className="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{selectedSellerName}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{selectedStatusLabel}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{selectedOrderTypeLabel}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{selectedProductLineLabel}</span>
            </div>
          </div>

          <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div>
              <div className="mb-2 flex items-center justify-between gap-3">
                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</span>
                <button type="button" className="text-xs font-semibold text-primary" onClick={() => setSelectedStatuses([])}>All</button>
              </div>
              <div className="max-h-44 overflow-y-auto rounded border border-slate-200 bg-white p-2">
                {statusOptions.map((status) => (
                  <button
                    key={status}
                    type="button"
                    className={`mb-2 mr-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                      selectedStatuses.includes(status)
                        ? 'border-primary bg-primary text-white'
                        : 'border-slate-200 bg-slate-50 text-slate-500'
                    }`}
                    onClick={() => toggleFilterValue(status, selectedStatuses, setSelectedStatuses)}
                  >
                    {status}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <div className="mb-2 flex items-center justify-between gap-3">
                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Order Type</span>
                <button type="button" className="text-xs font-semibold text-primary" onClick={() => setSelectedOrderTypes([])}>All</button>
              </div>
              <div className="rounded border border-slate-200 bg-white p-2">
                {orderTypeOptions.map((type) => (
                  <button
                    key={type}
                    type="button"
                    className={`mb-2 mr-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                      selectedOrderTypes.includes(type)
                        ? 'border-primary bg-primary text-white'
                        : 'border-slate-200 bg-slate-50 text-slate-500'
                    }`}
                    onClick={() => toggleFilterValue(type, selectedOrderTypes, setSelectedOrderTypes)}
                  >
                    {type}
                  </button>
                ))}
              </div>
            </div>

            <div>
              <div className="mb-2 flex items-center justify-between gap-3">
                <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Product Line</span>
                <button type="button" className="text-xs font-semibold text-primary" onClick={() => setSelectedProductLines([])}>All</button>
              </div>
              <div className="rounded border border-slate-200 bg-white p-2">
                {productLineOptions.map((line) => (
                  <button
                    key={line}
                    type="button"
                    className={`mb-2 mr-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${
                      selectedProductLines.includes(line)
                        ? 'border-primary bg-primary text-white'
                        : 'border-slate-200 bg-slate-50 text-slate-500'
                    }`}
                    onClick={() => toggleFilterValue(line, selectedProductLines, setSelectedProductLines)}
                  >
                    {line}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Seller</p>
            <p className="mt-2 truncate text-lg font-semibold text-slate-800">{selectedSellerName}</p>
          </div>
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Orders</p>
            <p className="mt-2 text-2xl font-semibold text-slate-800">{totals.orders}</p>
          </div>
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Overdue Orders</p>
            <p className="mt-2 text-2xl font-semibold text-rose-700">{totals.overdue_orders}</p>
          </div>
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Amount</p>
            <p className="mt-2 text-2xl font-semibold text-slate-800">{formatPrice(Number(totals.amount || 0))}</p>
          </div>
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between gap-3">
            <div>
              <h2 className="text-base font-semibold text-slate-800">Orders By Status</h2>
              <p className="text-sm text-slate-400">Open or close each status group to review matching orders.</p>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
              {totals.statuses} statuses
            </span>
          </div>

          <div className="space-y-3">
          {groups.length === 0 ? (
            <div className="rounded-lg border border-slate-200 bg-slate-50 p-10 text-center text-sm text-slate-400">
              No orders found for the selected filters.
            </div>
          ) : groups.map((group) => {
            const isOpen = openStatuses[group.status] ?? false

            return (
              <section key={group.status} className="overflow-hidden rounded-lg border border-slate-200 bg-white">
                <button
                  type="button"
                  className="flex w-full flex-col gap-2 border-b border-slate-200 bg-slate-50 px-4 py-4 text-left md:flex-row md:items-center md:justify-between"
                  onClick={() => toggleStatus(group.status)}
                >
                  <div>
                    <h3 className="text-base font-semibold text-slate-800">
                      {isOpen ? '-' : '+'} {group.status} ({group.count})
                    </h3>
                    <p className="mt-1 text-sm text-slate-400">{group.note}</p>
                  </div>

                  <div className="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <span className={group.overdue_count > 0 ? 'rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700' : 'rounded-full border border-slate-200 bg-white px-3 py-1'}>
                      Overdue: {group.overdue_count}
                    </span>
                    <span className="rounded-full border border-slate-200 bg-white px-3 py-1">Total: {formatPrice(Number(group.amount_total || 0))}</span>
                    <span className="rounded-full border border-slate-200 bg-white px-3 py-1">Threshold: {group.threshold_label}</span>
                  </div>
                </button>

                {isOpen && (
                  <div className="table-responsive">
                    <table className="w-full whitespace-nowrap">
                      <thead>
                        <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                          <th className="px-4 py-3">Order</th>
                          <th className="px-4 py-3">Amount</th>
                          <th className="px-4 py-3">Days In Status</th>
                          <th className="px-4 py-3">Seller</th>
                          <th className="px-4 py-3">Entered Status At</th>
                        </tr>
                      </thead>
                      <tbody>
                        {group.rows.length === 0 ? (
                          <tr>
                            <td className="px-4 py-10 text-center text-sm text-slate-400" colSpan={5}>
                              No orders found in this status.
                            </td>
                          </tr>
                        ) : (
                          group.rows.map((row) => (
                            <tr
                              key={`${group.status}-${row.id}`}
                              className={`border-t text-sm ${row.is_overdue ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-slate-200 text-slate-600'}`}
                            >
                              <td className="px-4 py-4 align-top">
                                <div className={row.is_overdue ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>{row.order_label}</div>
                                {row.is_overdue && <div className="mt-1 text-[11px] font-semibold text-rose-700">Overdue in current status</div>}
                              </td>
                              <td className="px-4 py-4 align-top">{formatPrice(Number(row.project_amount || 0))}</td>
                              <td className="px-4 py-4 align-top font-semibold">{row.days_in_stage}</td>
                              <td className="px-4 py-4 align-top">{row.seller_name || '-'}</td>
                              <td className="px-4 py-4 align-top">
                                <div className={row.is_overdue ? 'text-xs text-rose-600' : 'text-xs text-slate-400'}>{formatDateTime(row.stage_entered_at)}</div>
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                )}
              </section>
            )
          })}
          </div>
        </div>
      </div>
    </AuthenticatedCalendarLayout>
  )
}
