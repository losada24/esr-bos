import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import type { RequestPayload } from '@inertiajs/core'
import { ReactSortable } from 'react-sortablejs'
import { type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { tagClasses, type TagColor } from '@/Utils/tags'
import EyeIcon from '@/Components/Icons/EyeIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import InfoTooltip from '@/Components/InfoTooltip'
import OrderBoardFilter, { type BoardFilters, type FilterFieldConfig } from '@/Components/OrderBoardFilter'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'

export interface OwnerOption { id: number, name: string }
type IdOption = { id: number, name: string }
type TagOption = { name: string | null }

const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as const

const MONTH_INDEX: Record<string, number> = MONTH_LABELS.reduce((acc, label, index) => {
  const lower = label.toLowerCase()
  acc[lower] = index
  if (lower === 'sep') {
    acc.sept = index
  }
  return acc
}, {} as Record<string, number>)

const parseDisplayDateToTimestamp = (value?: string | null): number => {
  if (!value) return 0
  const trimmedValue = value.trim()
  if (!trimmedValue) return 0

  const match = trimmedValue.match(/^([A-Za-z]+)\s+(\d{1,2}),\s*(\d{4})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i)
  if (match) {
    const [, monthLabel, dayStr, yearStr, hourStr, minuteStr, periodRaw] = match
    const monthIndex = MONTH_INDEX[monthLabel.toLowerCase()]
    const day = Number(dayStr)
    const year = Number(yearStr)
    let hours = Number(hourStr)
    const minutes = Number(minuteStr)
    const period = periodRaw.toUpperCase()

    if (
      monthIndex != null &&
      !Number.isNaN(day) &&
      !Number.isNaN(year) &&
      !Number.isNaN(hours) &&
      !Number.isNaN(minutes)
    ) {
      hours = hours % 12
      if (period === 'PM') {
        hours += 12
      }

      const timestamp = Date.UTC(year, monthIndex, day, hours, minutes)
      return Number.isNaN(timestamp) ? 0 : timestamp
    }
  }

  const fallback = Date.parse(trimmedValue)
  return Number.isNaN(fallback) ? 0 : fallback
}

const sortTasksByRecentActivity = (tasks: Tasks[] = []): Tasks[] => {
  return [...tasks].sort((taskA, taskB) => {
    const timestampA = parseDisplayDateToTimestamp(taskA?.date_edited ?? taskA?.date)
    const timestampB = parseDisplayDateToTimestamp(taskB?.date_edited ?? taskB?.date)
    return timestampB - timestampA
  })
}

const sortPipelinesByRecentActivity = (pipelines: Pipelines[] = []): Pipelines[] => {
  return pipelines.map(pipeline => ({
    ...pipeline,
    tasks: sortTasksByRecentActivity(pipeline.tasks ?? [])
  }))
}

const clonePipelines = (pipelines: Pipelines[] = []): Pipelines[] => {
  return pipelines.map(pipeline => ({
    ...pipeline,
    tasks: (pipeline.tasks ?? []).map(task => ({ ...task }))
  }))
}

const formatDateForDisplay = (date: Date): string => {
  const monthLabel = MONTH_LABELS[date.getMonth()] ?? MONTH_LABELS[0]
  const day = date.getDate().toString().padStart(2, '0')
  const year = date.getFullYear()
  let hours = date.getHours()
  const minutes = date.getMinutes().toString().padStart(2, '0')
  const period = hours >= 12 ? 'PM' : 'AM'
  hours = hours % 12
  if (hours === 0) hours = 12
  const hourDisplay = hours.toString().padStart(2, '0')
  return `${monthLabel} ${day}, ${year} ${hourDisplay}:${minutes} ${period}`
}

const formatBidDueDate = (value?: string | null): string | null => {
  if (!value) return null
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString()
}

const parseBidDueDate = (value?: string | null): Date | null => {
  if (!value) return null
  const normalized = /^\d{4}-\d{2}-\d{2}$/.test(value) ? `${value}T00:00:00` : value
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? null : date
}

const isBidDuePast = (value?: string | null): boolean => {
  const date = parseBidDueDate(value)
  if (!date) return false
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  return date.getTime() < today.getTime()
}

const stampTaskAsUpdated = (task: Tasks): Tasks => ({
  ...task,
  date_edited: formatDateForDisplay(new Date())
})

const INFINITE_SCROLL_STATUSES = new Set(['COMPLETE'])
const TASKS_PAGE_SIZE = 20
const SCROLL_THRESHOLD_PX = 120

type StatusPaginationState = { nextPage: number, loading: boolean }

const toNumericAmount = (value?: number | string | null): number => {
  if (typeof value === 'number') return Number.isFinite(value) ? value : 0
  if (typeof value === 'string') {
    const parsed = Number(value.replace(/,/g, ''))
    return Number.isFinite(parsed) ? parsed : 0
  }
  return 0
}

const getPipelineTotalProjectAmount = (pipeline: Pipelines): number => {
  if (pipeline.total_project_amount != null && `${pipeline.total_project_amount}`.trim() !== '') {
    return toNumericAmount(pipeline.total_project_amount)
  }
  return (pipeline.tasks ?? []).reduce((sum, task) => sum + toNumericAmount(task.project_amount), 0)
}

const buildFilterQuery = (filters?: BoardFilters): Record<string, unknown> => {
  if (!filters) return {}
  if (Array.isArray(filters.filters) && filters.filters.length) {
    return {
      filter_match: filters.filter_match ?? 'and',
      filters: JSON.stringify(filters.filters)
    }
  }

  const params: Record<string, string> = {}
  if (filters.filter_field) params.filter_field = filters.filter_field
  if (filters.filter_value != null && `${filters.filter_value}`.trim() !== '') params.filter_value = `${filters.filter_value}`
  if (filters.filter_value_secondary != null && `${filters.filter_value_secondary}`.trim() !== '') params.filter_value_secondary = `${filters.filter_value_secondary}`
  if (filters.filter_op) params.filter_op = filters.filter_op
  if (filters.filter_value_min != null && `${filters.filter_value_min}`.trim() !== '') params.filter_value_min = `${filters.filter_value_min}`
  if (filters.filter_value_max != null && `${filters.filter_value_max}`.trim() !== '') params.filter_value_max = `${filters.filter_value_max}`
  return params
}

const buildPaginationState = (pipelines: Pipelines[] = []): Record<string, StatusPaginationState> => {
  return pipelines.reduce<Record<string, StatusPaginationState>>((acc, pipeline) => {
    if (!INFINITE_SCROLL_STATUSES.has(pipeline.title)) return acc
    const key = pipeline.id == null ? (pipeline.title ?? '') : pipeline.id.toString()
    if (!key) return acc
    const loadedPages = pipeline.tasks.length ? Math.ceil(pipeline.tasks.length / TASKS_PAGE_SIZE) : 0
    acc[key] = {
      nextPage: loadedPages + 1,
      loading: false
    }
    return acc
  }, {})
}

const OrderStorage = ({ auth, data, statuses, owners, supervisors, created_by_users, tags, sources, order_types, filters }: PageProps & { data: Pipelines[], statuses: string[], owners: OwnerOption[], supervisors: IdOption[], created_by_users: IdOption[], tags: TagOption[], sources: string[], order_types: string[], filters: BoardFilters }) => {
  const [pipelines, setPipelinesState] = useState<Pipelines[]>(() => sortPipelinesByRecentActivity(data))
  const [statusPagination, setStatusPagination] = useState<Record<string, StatusPaginationState>>(() => buildPaginationState(data))
  const [isFilterOpen, setIsFilterOpen] = useState(false)
  const dragSnapshotRef = useRef<Pipelines[] | null>(null)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const appliedFilters = filters ?? {}
  const filterQueryParams = buildFilterQuery(appliedFilters)

  const tagFilterOptions = useMemo(() => {
    const seen = new Set<string>()
    return tags
      .map((tag) => (typeof tag.name === 'string' ? tag.name.trim() : ''))
      .filter((name) => {
        if (!name) return false
        const normalized = name.toLowerCase()
        if (seen.has(normalized)) return false
        seen.add(normalized)
        return true
      })
      .map((name) => ({ label: name, value: name }))
  }, [tags])

  const filterFields = useMemo<FilterFieldConfig[]>(() => ([
    { value: 'name', label: 'Order Name', type: 'text', placeholder: 'Order name or job address' },
    {
      value: 'name_and_job_address',
      label: 'Order Name + Job Address',
      type: 'dual_text',
      primaryLabel: 'Order Name',
      secondaryLabel: 'Job Address',
      placeholder: 'Order name',
      secondaryPlaceholder: 'Job address'
    },
    { value: 'job_address', label: 'Job Address', type: 'text' },
    { value: 'job_city', label: 'Job City', type: 'text' },
    { value: 'status', label: 'Status', type: 'select', options: statuses.map((status) => ({ label: status, value: status })) },
    { value: 'city', label: 'City', type: 'text' },
    { value: 'job_state', label: 'Job State', type: 'text' },
    { value: 'job_zip', label: 'Job Zip', type: 'text' },
    { value: 'order_type', label: 'Order Type', type: 'select', options: order_types.map((type) => ({ label: type, value: type })) },
    { value: 'is_supply', label: 'Is Supply', type: 'select', options: [{ label: 'Yes', value: '1' }, { label: 'No', value: '0' }] },
    { value: 'owner', label: 'Owner', type: 'select', options: owners.map((owner) => ({ label: owner.name, value: owner.id.toString() })) },
    { value: 'source', label: 'Source', type: 'select', options: sources.map((source) => ({ label: source, value: source })) },
    { value: 'company_name', label: 'Company Name', type: 'text' },
    { value: 'client_name', label: 'Client Name', type: 'text' },
    { value: 'phone', label: 'Phone', type: 'text' },
    { value: 'tag', label: 'Tag', type: 'select', options: tagFilterOptions },
    { value: 'supervisor', label: 'Supervisor', type: 'select', options: supervisors.map((supervisor) => ({ label: supervisor.name, value: supervisor.id.toString() })) },
    { value: 'created_by', label: 'Created By', type: 'select', options: created_by_users.map((user) => ({ label: user.name, value: user.id.toString() })) },
    { value: 'created_time', label: 'Created Time', type: 'date' },
    { value: 'project_amount', label: 'Project Amount', type: 'amount' }
  ]), [statuses, order_types, owners, sources, tagFilterOptions, supervisors, created_by_users])

  useEffect(() => {
    const sorted = sortPipelinesByRecentActivity(data)
    setPipelinesState(sorted)
    setStatusPagination(buildPaginationState(sorted))
  }, [data])

  const setPipelines = useCallback<Dispatch<SetStateAction<Pipelines[]>>>((value) => {
    setPipelinesState(prevState => {
      const nextState = typeof value === 'function'
        ? (value as (prev: Pipelines[]) => Pipelines[])(prevState)
        : value

      return sortPipelinesByRecentActivity(nextState)
    })
  }, [setPipelinesState])

  const updatePipelineTasks = (pipelineId: Pipelines['id'], tasks: Tasks[]) => {
    setPipelines((prev) =>
      prev.map((pipeline) => {
        if (pipeline.id !== pipelineId) {
          return pipeline
        }
        const previousTasks = pipeline.tasks ?? []
        const nextTasks = tasks.map((task) => {
          const existed = previousTasks.some(prevTask => prevTask.id === task.id)
          return existed ? task : stampTaskAsUpdated(task)
        })
        const baseTotal = pipeline.total_tasks ?? previousTasks.length
        const delta = nextTasks.length - previousTasks.length
        const nextTotal = Math.max(0, baseTotal + delta)
        const previousById = new Map(previousTasks.map(task => [task.id, task]))
        const nextById = new Map(nextTasks.map(task => [task.id, task]))
        const removedAmount = previousTasks.reduce((sum, task) => {
          return nextById.has(task.id) ? sum : sum + toNumericAmount(task.project_amount)
        }, 0)
        const addedAmount = nextTasks.reduce((sum, task) => {
          return previousById.has(task.id) ? sum : sum + toNumericAmount(task.project_amount)
        }, 0)
        const baseProjectAmount = getPipelineTotalProjectAmount(pipeline)
        const nextProjectAmount = Math.max(0, baseProjectAmount - removedAmount + addedAmount)
        return { ...pipeline, tasks: nextTasks, total_tasks: nextTotal, total_project_amount: nextProjectAmount }
      })
    )
  }

  const updateOrderStatus = async (orderId: number, status: string) => {
    const response = await fetch(route('frontdesk.updateStatus', { order: orderId }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ status })
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok || !payload?.order) {
      throw new Error(payload?.message ?? 'Unable to update status.')
    }
  }

  const loadMoreTasks = useCallback(async (statusKey: string, nextPage: number) => {
    setStatusPagination(prev => ({
      ...prev,
      [statusKey]: { nextPage, loading: true }
    }))

    try {
      const response = await fetch(route('order-storage.tasks', { status: statusKey, page: nextPage, per_page: TASKS_PAGE_SIZE, ...filterQueryParams }), {
        headers: {
          Accept: 'application/json'
        }
      })
      if (!response.ok) {
        throw new Error('Error loading tasks')
      }
      const payload = await response.json().catch(() => null)
      const incomingTasks = Array.isArray(payload?.tasks) ? payload.tasks as Tasks[] : []
      const totalTasks = typeof payload?.total === 'number' ? payload.total : null

      if (incomingTasks.length > 0) {
        setPipelines(prev =>
          prev.map(p => {
            const pipelineKey = p.id == null ? (p.title ?? '') : p.id.toString()
            if (pipelineKey !== statusKey) return p
            const existingIds = new Set(p.tasks.map(task => task.id))
            const mergedTasks = [...p.tasks]
            incomingTasks.forEach(task => {
              if (!existingIds.has(task.id)) {
                mergedTasks.push(task)
              }
            })
            return {
              ...p,
              tasks: mergedTasks,
              ...(totalTasks != null ? { total_tasks: totalTasks } : {})
            }
          })
        )
      } else if (totalTasks != null) {
        setPipelines(prev =>
          prev.map(p => {
            const pipelineKey = p.id == null ? (p.title ?? '') : p.id.toString()
            return pipelineKey === statusKey ? { ...p, total_tasks: totalTasks } : p
          })
        )
      }

      setStatusPagination(prev => ({
        ...prev,
        [statusKey]: {
          nextPage: nextPage + 1,
          loading: false
        }
      }))
    } catch (error) {
      console.error('❌ Error al cargar mas tareas:', error)
      setStatusPagination(prev => ({
        ...prev,
        [statusKey]: {
          nextPage,
          loading: false
        }
      }))
    }
  }, [setPipelines, setStatusPagination, filterQueryParams])

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      leftActions={<OrderGlobalSearch origin="order_storage" className="w-full max-w-[420px]" />}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => { setIsFilterOpen(true) }}
          >
            Filter
          </button>
        </div>
      }
    >
      <Head title="Order Storage" />
      {isFilterOpen && (
        <div
          className="fixed inset-0 z-40 bg-black/40"
          onClick={() => { setIsFilterOpen(false) }}
        />
      )}
      <div
        className={`fixed right-0 top-0 z-50 h-full w-[380px] max-w-[90vw] transform bg-white shadow-2xl transition-transform duration-200 dark:bg-[#0b1220] ${isFilterOpen ? 'translate-x-0' : 'translate-x-full'}`}
      >
        <div className="flex items-center justify-between border-b border-slate-200 px-4 py-3 dark:border-white-dark/10">
          <div className="text-sm font-semibold text-slate-700 dark:text-white">Filters</div>
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => { setIsFilterOpen(false) }}
          >
            Close
          </button>
        </div>
        <div className="h-[calc(100%-60px)] overflow-y-auto p-4">
          <OrderBoardFilter
            fields={filterFields}
            initialFilters={appliedFilters}
            onApply={(params) => {
              const payload: RequestPayload = {
                ...params,
                ...(Array.isArray(params.filters) ? { filters: JSON.stringify(params.filters) } : {})
              }
              router.get(route('order-storage.index'), payload, { replace: true, preserveState: true })
              setIsFilterOpen(false)
            }}
            onReset={() => {
              router.get(route('order-storage.index'), {}, { replace: true, preserveState: false })
              setIsFilterOpen(false)
            }}
          />
        </div>
      </div>
      <div className="w-full h-[calc(100vh-140px)]">
        <div className="h-full overflow-x-auto overflow-y-hidden">
          <div className="flex h-full min-w-max gap-4">
            {pipelines.map((pipeline) => {
              const statusKey = pipeline.id?.toString() ?? pipeline.title ?? ''
              const totalTasks = pipeline.total_tasks ?? pipeline.tasks.length
              const isInfiniteStatus = INFINITE_SCROLL_STATUSES.has(pipeline.title)
              const hasMoreTasks = isInfiniteStatus && pipeline.tasks.length < totalTasks
              const pagination = statusPagination[statusKey]
              const totalProjectAmount = getPipelineTotalProjectAmount(pipeline)

              return (
                <div
                  key={pipeline.id}
                  className="panel w-96 min-w-[24rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden"
                  data-group={pipeline.id}
                >
                  <div className="sticky top-0 z-10 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                      <h4 className="flex-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-white mb-0">
                        {pipeline.title}
                      </h4>
                      <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white">
                        <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                          <span>{totalTasks}</span>
                          <span>{totalTasks === 1 ? 'Order' : 'Orders'}</span>
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-gradient-to-r from-sky-500/10 to-sky-600/10 px-2.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-200">
                          <span>Total</span>
                          <span>{formatCurrency(totalProjectAmount)}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div
                    className="flex-1 overflow-y-auto pr-2 pt-2"
                    onScroll={(event) => {
                      if (!isInfiniteStatus || !statusKey) return
                      if (!hasMoreTasks) return
                      if (pagination?.loading) return
                      const target = event.currentTarget
                      if (target.scrollHeight - target.scrollTop - target.clientHeight > SCROLL_THRESHOLD_PX) return
                      const nextPage = pagination?.nextPage ?? Math.floor(pipeline.tasks.length / TASKS_PAGE_SIZE) + 1
                      loadMoreTasks(statusKey, nextPage)
                    }}
                  >
                    <ReactSortable<Tasks>
                      list={pipeline.tasks}
                      setList={(newState) => updatePipelineTasks(pipeline.id, newState as Tasks[])}
                      group="order-storage"
                      animation={200}
                      onStart={() => {
                        dragSnapshotRef.current = clonePipelines(pipelines)
                      }}
                      onEnd={(evt) => {
                        const run = async () => {
                          const { item, from, to } = evt
                          const movedTaskId = item.getAttribute('data-id')
                          const oldStatus = from.closest('[data-group]')?.getAttribute('data-group')?.trim() ?? ''
                          const newStatus = to.closest('[data-group]')?.getAttribute('data-group')?.trim() ?? ''

                          if (!movedTaskId || !oldStatus || !newStatus || oldStatus === newStatus) {
                            dragSnapshotRef.current = null
                            return
                          }

                          const orderId = Number(movedTaskId)
                          if (!Number.isFinite(orderId)) {
                            dragSnapshotRef.current = null
                            return
                          }

                          try {
                            await updateOrderStatus(orderId, newStatus)
                          } catch (error) {
                            console.error('❌ Error al actualizar estado:', error)
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                          } finally {
                            dragSnapshotRef.current = null
                          }
                        }

                        void run()
                      }}
                      ghostClass="sortable-ghost"
                      dragClass="sortable-drag"
                      className="min-h-[1px] space-y-4 pt-2"
                    >
                      {pipeline.tasks.map((task) => {
                        const createdByName = typeof task.created_by === 'string' ? task.created_by.trim() : ''
                        const createdByDisplay = createdByName || 'Unknown'
                        const ownerNames = (task.owners ?? [])
                          .map((owner: any) => typeof owner?.name === 'string' ? owner.name.trim() : '')
                          .filter((name: string) => Boolean(name))
                        const ownersDisplay = ownerNames.length ? ownerNames.join(', ') : 'No owners assigned'
                        const ownersLabel = ownerNames.length === 1 ? 'Owner' : 'Owners'
                        const isCommercialOrder = task.order_type?.toLowerCase() === 'commercial'
                        const bidDueDateLabel = formatBidDueDate(task.bid_due_date)
                        const bidDuePast = isBidDuePast(task.bid_due_date)
                        const showBidDueDate = Boolean(isCommercialOrder && bidDueDateLabel)
                        const bidDueBadgeClass = bidDuePast
                          ? 'bg-rose-100 text-rose-800 ring-rose-200'
                          : 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                        const bidDueLabelClass = bidDuePast ? 'text-rose-600' : 'text-emerald-600'
                        const isVipClient = Boolean(task.vip_clients)

                        return (
                          <div className="sortable-list" key={task.id} data-id={task.id}>
                            <div className="shadow bg-[#f4f4f4] dark:bg-white-dark/20 p-3 pb-4 rounded-md mb-5 space-y-2 cursor-move text-xs text-slate-600">
                              <div className="flex items-center justify-between w-full">
                                <p className="flex items-center gap-2 break-all text-sm font-semibold text-slate-700 dark:text-white">
                                  {task.title}
                                  {isVipClient && (
                                    <span className="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:ring-rose-400/40">
                                      VIP
                                    </span>
                                  )}
                                </p>
                                <div className="flex items-center gap-2 text-[11px]">
                                  <Link
                                    href={route('frontdesk.order_view', task.id)}
                                    title="Order View"
                                    className="flex items-center gap-1 hover:text-success"
                                  >
                                    <EyeIcon />
                                  </Link>
                                  <button
                                    onClick={() => {}}
                                    type="button"
                                    className="flex items-center gap-1 hover:text-info"
                                  >
                                    <EditIcon />
                                  </button>
                                  <InfoTooltip
                                    side="left"
                                    width={220}
                                    content={
                                      <div>
                                        <div style={{ fontWeight: 700, marginBottom: 6, fontSize: '15px', color: '#0f172a' }}>Information</div>
                                        <ul style={{ margin: 0, paddingLeft: 16, fontSize: '13px', color: '#1e293b', lineHeight: '1.6' }}>
                                          <li style={{ marginBottom: 6 }}>Phone: {task.phone ?? '—'}</li>
                                          <li style={{ marginBottom: 6 }}>Appt Date: {task.schedule_appointment ?? 'No Appt Scheduled'}</li>
                                          <li style={{ marginBottom: 6 }}>Owners: {ownersDisplay}</li>
                                        </ul>
                                      </div>
                                    }
                                  />
                                </div>
                              </div>
                              <div className="flex gap-2 items-center flex-wrap">
                                {task.tags?.length
                                  ? (
                                      task.tags.map((tag, index) => (
                                        <span
                                          key={`${task.id}-tag-${index}`}
                                          className={tagClasses((tag.color as TagColor) || 'gray')}
                                          title={tag.name}
                                        >
                                          <span className="truncate">{tag.name}</span>
                                        </span>
                                      ))
                                    )
                                  : (
                                    <span className="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium ring-1 bg-slate-100 text-slate-600 ring-slate-200">
                                      No Tags
                                    </span>
                                    )}
                              </div>
                              <p className="break-all">{task.date}</p>
                              {task.date_edited !== task.date && (
                                <p className="break-all">{task.date_edited}</p>
                              )}
                              <div className="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                {ownerNames.length > 0 ? (
                                  <>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-amber-900 ring-1 ring-amber-300/80 shadow-sm">
                                      <span className="text-[10px] uppercase tracking-wide text-amber-700">{ownersLabel}</span>
                                      <span className="font-semibold">{ownersDisplay}</span>
                                    </span>
                                    {showBidDueDate && (
                                      <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 ring-1 shadow-sm ${bidDueBadgeClass}`}>
                                        <span className={`text-[10px] uppercase tracking-wide ${bidDueLabelClass}`}>Bid Due</span>
                                        <span className="font-semibold">{bidDueDateLabel}</span>
                                      </span>
                                    )}
                                  </>
                                ) : (
                                  <>
                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-0.5 text-sky-900 ring-1 ring-sky-300/80 shadow-sm">
                                      <span className="text-[10px] uppercase tracking-wide text-sky-700">Created by</span>
                                      <span className="font-semibold">{createdByDisplay}</span>
                                    </span>
                                    {showBidDueDate && (
                                      <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 ring-1 shadow-sm ${bidDueBadgeClass}`}>
                                        <span className={`text-[10px] uppercase tracking-wide ${bidDueLabelClass}`}>Bid Due</span>
                                        <span className="font-semibold">{bidDueDateLabel}</span>
                                      </span>
                                    )}
                                  </>
                                )}
                              </div>
                              {(task.project_amount !== undefined && task.project_amount !== null)
                                ? (
                                  <div className="mt-1 flex items-center justify-between gap-2">
                                    <p className="break-all font-semibold text-slate-700">
                                      {formatCurrency(Number(task.project_amount))}
                                    </p>
                                    {task.is_supply && (
                                      <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                        SUPPLY
                                      </span>
                                    )}
                                  </div>
                                  )
                                : (
                                  task.is_supply && (
                                    <div className="mt-1 flex justify-end">
                                      <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                        SUPPLY
                                      </span>
                                    </div>
                                  )
                                  )}
                            </div>
                          </div>
                        )
                      })}
                    </ReactSortable>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>
    </AuthenticatedCalendarLayout>
  )
}

export default OrderStorage
