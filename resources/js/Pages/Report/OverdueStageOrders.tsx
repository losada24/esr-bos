import React, { useMemo, useState } from 'react'
import { Head, router } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface FilterOption {
  id?: number
  name?: string
}

interface OverdueStageOrderRow {
  id: number
  status: string
  order_name: string | null
  order_label: string
  amount: number
  order_type: string | null
  product_line: string | null
  seller_name: string
  created_by_name: string
  days_in_stage: number
  stage_limit_business_days: number | null
  created_at: string | null
  stage_entered_at: string | null
  is_overdue: boolean
  overdue_extension_active?: boolean
  overdue_extension?: {
    business_days: number
    extended_until?: string | null
    note?: string | null
    user?: {
      name: string
    } | null
  } | null
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
  overdue_count: number
  amount: number
  count: number
  seller_groups: OverdueStageSellerGroup[]
}

interface OverdueStageFilters {
  seller_id: number | null
  statuses: string[]
  order_types: string[]
  product_lines: string[]
  overdue_only: boolean
}

type OverdueStageOrdersProps = PageProps & {
  generatedAt: string
  selectedSellerName: string
  totals: {
    statuses: number
    configured_statuses: number
    orders: number
    overdue_orders: number
    amount: number
  }
  filters: OverdueStageFilters
  filterOptions: {
    sellers: FilterOption[]
    statuses: string[]
    order_types: string[]
    product_lines: string[]
  }
  groups: OverdueStageGroup[]
}

const formatDateTime = (value?: string | null): string => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value

  return date.toLocaleString()
}

const formatCurrency = (value?: number | string | null): string => {
  const amount = Number(value ?? 0)

  return amount.toLocaleString(undefined, {
    style: 'currency',
    currency: 'USD'
  })
}

const toggleValue = (values: string[], value: string): string[] => {
  return values.includes(value)
    ? values.filter((item) => item !== value)
    : [...values, value]
}

const explicitSelection = (values: string[], allValues: string[]): string[] => {
  return values.length === allValues.length ? [] : values
}

export default function OverdueStageOrders ({
  generatedAt,
  selectedSellerName,
  totals,
  filters,
  filterOptions,
  groups,
  auth
}: OverdueStageOrdersProps) {
  const [sellerId, setSellerId] = useState<string>(filters.seller_id ? String(filters.seller_id) : '')
  const [statuses, setStatuses] = useState<string[]>(() => explicitSelection(filters.statuses, filterOptions.statuses))
  const [orderTypes, setOrderTypes] = useState<string[]>(() => explicitSelection(filters.order_types, filterOptions.order_types))
  const [productLines, setProductLines] = useState<string[]>(() => explicitSelection(filters.product_lines, filterOptions.product_lines))
  const [overdueOnly, setOverdueOnly] = useState<boolean>(filters.overdue_only)
  const [expandedStatuses, setExpandedStatuses] = useState<string[]>([])

  const query = useMemo(() => ({
    seller_id: sellerId || undefined,
    statuses,
    order_types: orderTypes,
    product_lines: productLines,
    overdue_only: overdueOnly ? 1 : undefined
  }), [sellerId, statuses, orderTypes, productLines, overdueOnly])

  const exportHref = (routeName: string): string => {
    const params = new URLSearchParams()

    if (sellerId) params.append('seller_id', sellerId)
    statuses.forEach((status) => { params.append('statuses[]', status) })
    orderTypes.forEach((orderType) => { params.append('order_types[]', orderType) })
    productLines.forEach((productLine) => { params.append('product_lines[]', productLine) })
    if (overdueOnly) params.append('overdue_only', '1')

    const qs = params.toString()
    return `${route(routeName)}${qs ? `?${qs}` : ''}`
  }

  const applyFilters = () => {
    router.get(route('report.overdue-stage-orders'), query, {
      preserveState: true,
      preserveScroll: true
    })
  }

  const resetFilters = () => {
    router.get(route('report.overdue-stage-orders'))
  }

  const clearSelection = (setter: React.Dispatch<React.SetStateAction<string[]>>) => {
    setter([])
  }

  const toggleStatusGroup = (status: string) => {
    setExpandedStatuses((values) => toggleValue(values, status))
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Overdue Stage Orders">
      <Head title="Overdue Stage Orders" />

      <div className="space-y-6">
        <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
          <h1 className="text-xl font-semibold text-slate-800">Overdue Stage Orders</h1>

          <div className="flex flex-wrap items-center gap-2">
            <select
              value={sellerId}
              onChange={(event) => { setSellerId(event.target.value) }}
              className="form-select w-56"
            >
              <option value="">All sellers</option>
              {filterOptions.sellers.map((seller) => (
                <option key={seller.id} value={seller.id}>{seller.name}</option>
              ))}
            </select>

            <label className="inline-flex items-center gap-2 rounded-md border border-white-light bg-white px-4 py-2 text-sm font-semibold text-slate-600 dark:border-[#17263c] dark:bg-[#121e32] dark:text-white-dark">
              <input
                type="checkbox"
                checked={overdueOnly}
                onChange={(event) => { setOverdueOnly(event.target.checked) }}
                className="form-checkbox"
              />
              Overdue only
            </label>

            <button type="button" className="btn btn-primary" onClick={applyFilters}>Filter</button>
            <button type="button" className="btn btn-outline-primary" onClick={resetFilters}>Reset</button>
            <a className="btn btn-outline-primary" href={exportHref('report.overdue-stage-orders-pdf')} target="_blank" rel="noopener noreferrer">View PDF</a>
            <a className="btn btn-outline-primary" href={exportHref('report.overdue-stage-orders-excel')} target="_blank" rel="noopener noreferrer">Export Excel</a>
          </div>
        </div>

        <div className="rounded-lg border border-sky-200 bg-sky-50 px-5 py-4 text-sm font-medium text-sky-700">
          Orders by seller and ESR Process status. Overdue rows use the configured pipeline alert thresholds. Generated at: {formatDateTime(generatedAt)}
        </div>

        <div className="panel">
          <div className="flex flex-col gap-1 md:flex-row md:items-start md:justify-between">
            <div>
              <h2 className="text-base font-semibold text-slate-800">Report Filters</h2>
              <p className="text-sm text-slate-400">Select the statuses, order types, and product lines to include.</p>
            </div>
            <div className="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{sellerId ? selectedSellerName : 'All sellers'}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{statuses.length === 0 ? 'All statuses' : `${statuses.length} statuses`}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{orderTypes.length === 0 ? 'All order types' : `${orderTypes.length} order types`}</span>
              <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1">{productLines.length === 0 ? 'All product lines' : `${productLines.length} product lines`}</span>
            </div>
          </div>

          <div className="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div>
              <div className="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>Status</span>
                <button type="button" className="text-primary" onClick={() => { clearSelection(setStatuses) }}>All</button>
              </div>
              <div className="max-h-44 overflow-y-auto rounded-md border border-white-light bg-white p-2 dark:border-[#17263c] dark:bg-[#121e32]">
                <div className="flex flex-wrap gap-2">
                  {filterOptions.statuses.map((status) => (
                    <button
                      key={status}
                      type="button"
                      onClick={() => { setStatuses((values) => toggleValue(values, status)) }}
                      className={`rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${statuses.includes(status) ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-500'}`}
                    >
                      {status}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            <div>
              <div className="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>Order Type</span>
                <button type="button" className="text-primary" onClick={() => { clearSelection(setOrderTypes) }}>All</button>
              </div>
              <div className="rounded-md border border-white-light bg-white p-2 dark:border-[#17263c] dark:bg-[#121e32]">
                <div className="flex flex-wrap gap-2">
                  {filterOptions.order_types.map((orderType) => (
                    <button
                      key={orderType}
                      type="button"
                      onClick={() => { setOrderTypes((values) => toggleValue(values, orderType)) }}
                      className={`rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${orderTypes.includes(orderType) ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-500'}`}
                    >
                      {orderType}
                    </button>
                  ))}
                </div>
              </div>
            </div>

            <div>
              <div className="mb-2 flex items-center justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                <span>Product Line</span>
                <button type="button" className="text-primary" onClick={() => { clearSelection(setProductLines) }}>All</button>
              </div>
              <div className="rounded-md border border-white-light bg-white p-2 dark:border-[#17263c] dark:bg-[#121e32]">
                <div className="flex flex-wrap gap-2">
                  {filterOptions.product_lines.map((productLine) => (
                    <button
                      key={productLine}
                      type="button"
                      onClick={() => { setProductLines((values) => toggleValue(values, productLine)) }}
                      className={`rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${productLines.includes(productLine) ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-500'}`}
                    >
                      {productLine}
                    </button>
                  ))}
                </div>
              </div>
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-4">
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Seller</p>
            <p className="mt-2 text-lg font-semibold text-slate-800">{selectedSellerName}</p>
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
            <p className="mt-2 text-2xl font-semibold text-slate-800">{formatCurrency(totals.amount)}</p>
          </div>
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between">
            <div>
              <h2 className="text-base font-semibold text-slate-800">Orders By Status</h2>
              <p className="text-sm text-slate-400">Open or close each status group to review matching orders.</p>
            </div>
            <span className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500">{groups.length} statuses</span>
          </div>

          <div className="space-y-4">
            {groups.map((group) => {
              const isExpanded = expandedStatuses.includes(group.status)

              return (
              <section key={group.status} className="overflow-hidden rounded-md border border-slate-200">
                <button
                  type="button"
                  className="block w-full cursor-pointer bg-[#f6f8fa] px-4 py-4 text-left hover:bg-slate-100"
                  onClick={() => { toggleStatusGroup(group.status) }}
                >
                  <div className="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                      <h3 className="text-base font-semibold text-slate-800">
                        {isExpanded ? '-' : '+'} {group.status} ({group.count})
                      </h3>
                      <p className="mt-1 text-sm text-slate-400">{group.note}</p>
                    </div>
                    <div className="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-wide">
                      <span className="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-rose-700">Overdue: {group.overdue_count}</span>
                      <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-500">Total: {formatCurrency(group.amount)}</span>
                      <span className="rounded-full border border-slate-200 bg-white px-3 py-1 text-slate-500">Threshold: {group.threshold_label}</span>
                    </div>
                  </div>
                </button>

                {!isExpanded
                  ? null
                  : group.seller_groups.length === 0
                    ? (
                  <div className="px-4 py-8 text-center text-sm text-slate-400">No matching orders in this status.</div>
                      )
                    : (
                  <div className="table-responsive">
                    <table className="w-full whitespace-nowrap">
                      <thead>
                        <tr className="text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                          <th className="px-4 py-3">Order</th>
                          <th className="px-4 py-3">Amount</th>
                          <th className="px-4 py-3">Days In Status</th>
                          <th className="px-4 py-3">Order Type</th>
                          <th className="px-4 py-3">Product Line</th>
                          <th className="px-4 py-3">Seller</th>
                          <th className="px-4 py-3">Entered Status At</th>
                          <th className="px-4 py-3">Extension</th>
                        </tr>
                      </thead>
                      <tbody>
                        {group.seller_groups.flatMap((sellerGroup) => sellerGroup.rows).map((row) => (
                          <tr key={`${group.status}-${row.id}`} className={`border-t text-sm ${row.is_overdue ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-slate-200 text-slate-600'}`}>
                            <td className="px-4 py-4 align-top">
                              <div className={row.is_overdue ? 'font-semibold text-rose-900' : 'font-semibold text-slate-700'}>
                                {row.order_label}
                              </div>
                            </td>
                            <td className="px-4 py-4 align-top">{formatCurrency(row.amount)}</td>
                            <td className="px-4 py-4 align-top font-semibold">
                              {row.days_in_stage}
                              {row.is_overdue && row.stage_limit_business_days != null
                                ? (
                                <span className="ml-2 rounded-full border border-rose-200 bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-rose-700">overdue</span>
                                  )
                                : null}
                            </td>
                            <td className="px-4 py-4 align-top">{row.order_type ?? '-'}</td>
                            <td className="px-4 py-4 align-top">
                              <span className="rounded-full border border-sky-200 bg-sky-50 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide text-sky-700">
                                {row.product_line ?? '-'}
                              </span>
                            </td>
                            <td className="px-4 py-4 align-top">{[row.seller_name, row.created_by_name].find(Boolean) ?? '-'}</td>
                            <td className="px-4 py-4 align-top">{formatDateTime(row.stage_entered_at)}</td>
                            <td className="px-4 py-4 align-top">
                              {row.overdue_extension
                                ? (
                                  <div className={row.overdue_extension_active ? 'text-amber-800' : 'text-slate-600'}>
                                    <div className="font-semibold">
                                      {row.overdue_extension_active ? 'Active extension' : 'Last extension'}
                                    </div>
                                    <div>{row.overdue_extension.business_days} business days until {formatDateTime(row.overdue_extension.extended_until)}</div>
                                    {row.overdue_extension.user?.name && <div>By {row.overdue_extension.user.name}</div>}
                                    {row.overdue_extension.note && <div className="mt-1 max-w-md whitespace-normal">{row.overdue_extension.note}</div>}
                                  </div>
                                  )
                                : '-'}
                            </td>
                          </tr>
                        ))}
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
    </AuthenticatedLayout>
  )
}
