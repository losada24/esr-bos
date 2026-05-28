import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import type { RequestPayload } from '@inertiajs/core'
import { ReactSortable } from 'react-sortablejs'
import { type Role, type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner, isFrontdeskEsr } from '@/Utils/user'

import DeleteIcon from '@/Components/Icons/DeleteIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import LostRequestModal from './LostRequestModal'
import QuantifiedModal from './QuantifiedModal'
import RequestStandByModal from './RequestStandByModal'
import EyeIcon from '@/Components/Icons/EyeIcon'
import { tagClasses, type TagColor } from '@/Utils/tags'
import InfoTooltip from '@/Components/InfoTooltip'
import OrderBoardFilter, { type BoardFilters, type FilterFieldConfig } from '@/Components/OrderBoardFilter'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import OrderPipelineSort from '@/Components/OrderPipelineSort'
import { formatDateOnlyDisplay, isDateOnlyPast } from '@/Utils/dateOnly'
import {
  type PipelineSortBy,
  type PipelineSortDir,
  hasPipelineSortInUrl,
  normalizePipelineSort,
  readStoredPipelineSort,
  storePipelineSort
} from '@/Utils/orderPipelineSort'

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

const formatBidDueDate = (value?: string | null): string | null => {
  return formatDateOnlyDisplay(value)
}

const isBidDuePast = (value?: string | null): boolean => {
  return isDateOnlyPast(value)
}

const INFINITE_SCROLL_STATUSES = new Set(['LOST REQUEST', 'QUALIFIED'])
const TASKS_PAGE_SIZE = 20
const SCROLL_THRESHOLD_PX = 120
type StatusPaginationState = { nextPage: number, loading: boolean }
type IdOption = { id: number, name: string }
type TagOption = { name: string | null }
type ActivityMenuState = {
  orderId: number
  x: number
  y: number
} | null

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

const TWENTY_FOUR_HOURS_IN_MS = 24 * 60 * 60 * 1000
const SEVENTY_TWO_HOURS_IN_MS = 72 * 60 * 60 * 1000
const FOURTEEN_DAYS_IN_MS = 14 * TWENTY_FOUR_HOURS_IN_MS

const normalizePipelineIdentifier = (pipeline: Pick<Pipelines, 'id' | 'title'>): string => {
  const normalizedTitle = pipeline.title?.toUpperCase() ?? ''
  const normalizedId = (pipeline.id == null ? '' : pipeline.id.toString()).toUpperCase()
  return normalizedTitle || normalizedId
}

const STALE_STATUS_RULES: Record<string, { threshold: number, className: string }> = {
  'NEW REQUEST': {
    threshold: TWENTY_FOUR_HOURS_IN_MS,
    className: 'bg-red-200 dark:bg-red-500/40'
  },
  'REQUEST FOLLOW UP': {
    threshold: SEVENTY_TWO_HOURS_IN_MS,
    className: 'bg-red-200 dark:bg-red-500/40'
  },
  'REQUEST STAND BY': {
    threshold: FOURTEEN_DAYS_IN_MS,
    className: 'bg-red-200 dark:bg-red-500/40'
  }
}

const getStatusTimestamp = (task: Tasks): number => {
  const isoTimestamp = task.status_created_at_iso != null ? Date.parse(task.status_created_at_iso) : Number.NaN
  if (!Number.isNaN(isoTimestamp)) return isoTimestamp
  return parseDisplayDateToTimestamp(task?.date)
}

const getStaleStatusClass = (pipeline: Pick<Pipelines, 'id' | 'title'>, task: Tasks): string | null => {
  const pipelineKey = normalizePipelineIdentifier(pipeline)
  if (!pipelineKey) return null
  const rule = STALE_STATUS_RULES[pipelineKey]
  if (!rule) return null
  const createdTimestamp = getStatusTimestamp(task)
  if (!createdTimestamp) return null
  return (Date.now() - createdTimestamp) >= rule.threshold ? rule.className : null
}

const activityMenuPosition = (element: HTMLElement) => {
  const rect = element.getBoundingClientRect()

  return {
    x: Math.min(rect.left + rect.width - 8, window.innerWidth - 190),
    y: Math.min(rect.top + rect.height + 4, window.innerHeight - 140)
  }
}

export default function Frontdesk ({
  auth,
  data,
  lossReasonFrontdesk,
  sources,
  order_types,
  statuses,
  owners,
  supervisors,
  created_by_users,
  tags,
  filters,
  sort,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages
}: PageProps & {
  data: Pipelines[]
  lossReasonFrontdesk: string[]
  sources: string[]
  order_types: string[]
  statuses: string[]
  owners: IdOption[]
  supervisors: IdOption[]
  created_by_users: IdOption[]
  tags: TagOption[]
  filters: BoardFilters
  sort: { sort_by?: string, sort_dir?: string }
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
}) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))
  const IS_FRONTDESK_ESR = isFrontdeskEsr(auth.user.roles.map((role: Role) => role.name))
  const canDeleteTasks = !IS_FRONTDESK_ESR

  const [projectList, setProjectListState] = useState<Pipelines[]>(() => data)
  const [showModal, setShowModal] = useState(false)
  const [lostTask, setLostTask] = useState<Tasks | null>(null)
  const [showQuantifiedModal, setShowQuantifiedModal] = useState(false)
  const [showRequestStandByModal, setShowRequestStandByModal] = useState(false)
  const [previousStatusId, setPreviousStatusId] = useState<string | null>(null)
  const [statusPagination, setStatusPagination] = useState<Record<string, StatusPaginationState>>(() => buildPaginationState(data))
  const [isFilterOpen, setIsFilterOpen] = useState(false)
  const [activityMenu, setActivityMenu] = useState<ActivityMenuState>(null)
  const sortHydratedRef = useRef(false)
  const appliedFilters = filters ?? {}
  const filterQueryParams = useMemo(() => buildFilterQuery(appliedFilters), [appliedFilters])
  const sortState = useMemo(() => normalizePipelineSort(sort), [sort])
  const hasSortInUrl = useMemo(() => hasPipelineSortInUrl(), [])
  const sortQueryParams = useMemo(() => ({
    sort_by: sortState.sort_by,
    sort_dir: sortState.sort_dir
  }), [sortState.sort_by, sortState.sort_dir])

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
    { value: 'loss_reason_frontdesk', label: 'Loss Reason', type: 'select', options: lossReasonFrontdesk.map((reason) => ({ label: reason, value: reason })) },
    { value: 'company_name', label: 'Company Name', type: 'text' },
    { value: 'client_name', label: 'Client Name', type: 'text' },
    { value: 'phone', label: 'Phone', type: 'text' },
    { value: 'tag', label: 'Tag', type: 'select', options: tagFilterOptions },
    { value: 'supervisor', label: 'Supervisor', type: 'select', options: supervisors.map((supervisor) => ({ label: supervisor.name, value: supervisor.id.toString() })) },
    { value: 'created_by', label: 'Created By', type: 'select', options: created_by_users.map((user) => ({ label: user.name, value: user.id.toString() })) },
    { value: 'created_time', label: 'Created Time', type: 'date' },
    { value: 'project_amount', label: 'Project Amount', type: 'amount' }
  ]), [statuses, order_types, owners, sources, lossReasonFrontdesk, tagFilterOptions, supervisors, created_by_users])

  useEffect(() => {
    setProjectListState(data)
    setStatusPagination(buildPaginationState(data))
  }, [data])

  const setProjectList = useCallback<Dispatch<SetStateAction<Pipelines[]>>>((value) => {
    setProjectListState(prevState => {
      const nextState = typeof value === 'function'
        ? (value as (prev: Pipelines[]) => Pipelines[])(prevState)
        : value

      return nextState
    })
  }, [setProjectListState])

  useEffect(() => {
    if (sortHydratedRef.current) return
    sortHydratedRef.current = true
    if (hasSortInUrl) return
    const storedSort = readStoredPipelineSort()
    if (!storedSort) return
    if (storedSort.sort_by === sortState.sort_by && storedSort.sort_dir === sortState.sort_dir) return

    router.get(route('frontdesk.index'), { ...filterQueryParams, ...storedSort }, { replace: true, preserveState: true, preserveScroll: true })
  }, [filterQueryParams, hasSortInUrl, sortState.sort_by, sortState.sort_dir])

  useEffect(() => {
    if (!sortHydratedRef.current) return
    storePipelineSort(sortState)
  }, [sortState.sort_by, sortState.sort_dir])

  const applySort = useCallback((nextSortBy: PipelineSortBy, nextSortDir: PipelineSortDir) => {
    router.get(route('frontdesk.index'), { ...filterQueryParams, sort_by: nextSortBy, sort_dir: nextSortDir }, {
      replace: true,
      preserveState: true,
      preserveScroll: true
    })
  }, [filterQueryParams])

  async function updateOrderStatus (orderId: number, newStatus: string, confirmCustomerRole = false): Promise<void> {
    const url = route('frontdesk.updateStatus', { order: orderId })

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        Accept: 'application/json'
      },
      body: JSON.stringify({
        status: newStatus,
        ...(confirmCustomerRole ? { confirm_customer_role: true } : {})
      })
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok) {
      if (response.status === 409 && payload?.requires_confirmation) {
        const confirmed = window.confirm(payload?.message ?? 'This email already belongs to another user. Convert it to customer?')
        if (!confirmed) {
          return
        }

        return await updateOrderStatus(orderId, newStatus, true)
      }

      throw new Error(payload?.message || 'Error updating status')
    }
  }

  const destroyOrder = (orderId: number) => {
    if (!confirm('Are you sure you want to delete this Order?')) return
    router.delete(route('order.destroy', orderId), { preserveScroll: true })
  }

  const loadMoreTasks = useCallback(async (statusKey: string, nextPage: number) => {
    setStatusPagination(prev => ({
      ...prev,
      [statusKey]: {
        nextPage,
        loading: true
      }
    }))

    try {
      const response = await fetch(route('frontdesk.tasks', { status: statusKey, page: nextPage, per_page: TASKS_PAGE_SIZE, ...filterQueryParams, ...sortQueryParams }), {
        headers: { Accept: 'application/json' }
      })

      if (!response.ok) {
        throw new Error('Error loading tasks')
      }

      const payload = await response.json()
      const incomingTasks = Array.isArray(payload?.tasks) ? payload.tasks as Tasks[] : []
      const totalTasks = typeof payload?.total === 'number' ? payload.total : null

      if (incomingTasks.length) {
        setProjectList(prev =>
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
        setProjectList(prev =>
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
  }, [setProjectList, setStatusPagination, filterQueryParams, sortQueryParams])

  /* const loadEvents = (date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('dashboard.get_events', { year, month, service: calendarFilter.service, status: calendarFilter.status, ...(calendarFilter.name !== 'all' && { name: calendarFilter.name }) })
    getJson(getEventsRoute, (events) => {
      setEvents(events)
    }, 'json')
  } */

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      leftActions={<OrderGlobalSearch origin="frontdesk" className="w-full max-w-[420px]" />}
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <OrderPipelineSort
            sortBy={sortState.sort_by}
            sortDir={sortState.sort_dir}
            onSortByChange={(value) => { applySort(value, sortState.sort_dir) }}
            onSortDirChange={(value) => { applySort(sortState.sort_by, value) }}
          />
          <button
            type="button"
            className="btn btn-outline-primary"
            onClick={() => { setIsFilterOpen(true) }}
          >
            Filter
          </button>
          {!IS_FRONTDESK_ESR && (
            <Link
              className="btn btn-primary"
              href={route('frontdesk.create')}
            >
              <span>Create Request</span>
            </Link>
          )}
          {!IS_FRONTDESK_ESR && (
            <Link
              className="btn btn-primary"
              href={route('frontdesk.create-qualified')}
            >
              <span>Create Order</span>
            </Link>
          )}
        </div>
      }
    >
      <Head title="Frontdesk" />
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
                ...sortQueryParams,
                ...(Array.isArray(params.filters) ? { filters: JSON.stringify(params.filters) } : {})
              }
              router.get(route('frontdesk.index'), payload, { replace: true, preserveState: true })
              setIsFilterOpen(false)
            }}
            onReset={() => {
              router.get(route('frontdesk.index'), sortQueryParams, { replace: true, preserveState: false })
              setIsFilterOpen(false)
            }}
          />
        </div>
      </div>
      <div className="w-full h-[calc(100vh-140px)]">
          <div className="overflow-x-auto  overflow-y-hidden h-full">
                <div className="flex gap-4 min-w-max h-full">
                  {projectList.map((project: any) => {
                    const statusKey = project.id?.toString() ?? project.title ?? ''
                    const totalTasks = project.total_tasks ?? project.tasks.length
                    const isInfiniteStatus = INFINITE_SCROLL_STATUSES.has(project.title)
                    const hasMoreTasks = isInfiniteStatus && project.tasks.length < totalTasks
                    const pagination = statusPagination[statusKey]

                    return (
                      <div key={project.id} className="panel w-80 min-w-[20rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden" data-group={project.id}>
                        <div className="sticky top-0 z-20 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                          <div className="flex items-start justify-between gap-3">
                            <h4 className="flex-1 text-xs font-semibold leading-tight text-slate-700 dark:text-white mb-0">
                              {project.title}
                            </h4>
                            <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white shrink-0">
                              <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                                <span className="text-[11px]">{totalTasks}</span>
                                <span>{totalTasks === 1 ? 'Order' : 'Orders'}</span>
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
                            const nextPage = pagination?.nextPage ?? Math.floor(project.tasks.length / TASKS_PAGE_SIZE) + 1
                            loadMoreTasks(statusKey, nextPage)
                          }}
                        >
                          <ReactSortable<Tasks>
                                list={project.tasks}
                                setList={() => {}} // Desactivado para manejarlo manualmente
                                group="shared"
                                animation={200}
                                onEnd={(evt) => {
                                  const { item, from, to } = evt
                                  // const movedTaskId = item.getAttribute('data-id')
                                  const movedTaskIdAttr = item.getAttribute('data-id')
                                  if (!movedTaskIdAttr) return// No hay ID, no seguimos
                                  const movedTaskId = Number(movedTaskIdAttr)
                                  const oldStatus = from.closest('[data-group]')?.getAttribute('data-group') ?? ''
                                  const newStatus = to.closest('[data-group]')?.getAttribute('data-group') ?? ''

                                  if (oldStatus === newStatus) return

                                  let movedTask!: Tasks

                                  /* setProjectList((prev) => {
                                    const updatedList = prev.map((pipeline) => {
                                      if (pipeline.id.toString() === oldStatus) {
                                        const newTasks = pipeline.tasks.filter((t) => {
                                          if (Number(t.id) === Number(movedTaskId)) {
                                            movedTask = t
                                            return false
                                          }
                                          return true
                                        })
                                        return { ...pipeline, tasks: newTasks }
                                      }
                                      return pipeline
                                    })
                                    return updatedList
                                  }) */
                                  setProjectList(prev =>
                                    prev.map(p => {
                                      if (p.id.toString() === oldStatus) {
                                        let removed = false
                                        const remaining = p.tasks.filter(t => {
                                          if (Number(t.id) === movedTaskId) {
                                            movedTask = t
                                            removed = true
                                            return false
                                          }
                                          return true
                                        })
                                        if (!removed) return p
                                        const nextTotal = Math.max(0, (p.total_tasks ?? p.tasks.length) - 1)
                                        return { ...p, tasks: remaining, total_tasks: nextTotal }
                                      }
                                      return p
                                    })
                                  )

                                  /* if (newStatus === 'LOST REQUEST' && movedTask) {
                                    setLostTask(movedTask)
                                    setShowModal(true)
                                    return // detenemos aquí, no movemos aún
                                  } */

                                  if (!movedTask) return
                                  if (newStatus === 'QUALIFIED') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowQuantifiedModal(true)
                                    return
                                  }
                                  if (newStatus === 'LOST REQUEST') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowModal(true)
                                    return
                                  }
                                  if (newStatus === 'REQUEST STAND BY') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowRequestStandByModal(true)
                                    return
                                  }
                                  const movedTaskWithUpdatedDate = {
                                    ...movedTask,
                                    date_edited: formatDateForDisplay(new Date())
                                  }
                                  setProjectList(prev =>
                                    prev.map(p =>
                                      p.id.toString() === newStatus
                                        ? (() => {
                                            const exists = p.tasks.some(t => Number(t.id) === movedTaskId)
                                            if (exists) return p
                                            const nextTotal = (p.total_tasks ?? p.tasks.length) + 1
                                            return { ...p, tasks: [...p.tasks, movedTaskWithUpdatedDate], total_tasks: nextTotal }
                                          })()
                                        : p
                                    )
                                  )

                                  // 4) Actualizar backend y revertir si falla
                                  updateOrderStatus(movedTaskId, newStatus)
                                    .then(() => { console.log('✅ Estado actualizado en backend') })
                                    .catch((err: unknown) => {
                                      console.error('❌ Error al actualizar el estado:', err)
                                      // revertir
                                      setProjectList(prev =>
                                        prev.map(p => {
                                          if (p.id.toString() === newStatus) {
                                            const remaining = p.tasks.filter(t => Number(t.id) !== movedTaskId)
                                            const nextTotal = Math.max(0, (p.total_tasks ?? p.tasks.length) - 1)
                                            return { ...p, tasks: remaining, total_tasks: nextTotal }
                                          }
                                          if (p.id.toString() === oldStatus) {
                                            const exists = p.tasks.some(t => Number(t.id) === movedTaskId)
                                            if (exists) return p
                                            const nextTotal = (p.total_tasks ?? p.tasks.length) + 1
                                            return { ...p, tasks: [...p.tasks, movedTask], total_tasks: nextTotal }
                                          }
                                          return p
                                        })
                                      )
                                    })
                                }}
                                ghostClass="sortable-ghost"
                                dragClass="sortable-drag"
                                className="min-h-[1px] space-y-4  pt-2"
                                >
                                {project.tasks.map((task: Tasks) => {
                                  console.log('tags →', task.tags)
                                  const staleStatusClass = getStaleStatusClass(project, task)
                                  const cardBackgroundClass = staleStatusClass ?? 'bg-[#f4f4f4] dark:bg-white-dark/20'
                                  const createdByName = typeof task.created_by === 'string' ? task.created_by.trim() : ''
                                  const createdByDisplay = createdByName || 'Unknown'
                                  const ownerNames = (task.owners ?? [])
                                    .map((owner: any) => typeof owner?.name === 'string' ? owner.name.trim() : '')
                                    .filter((name: string) => Boolean(name))
                                  const ownersLabel = ownerNames.length === 1 ? 'Owner' : 'Owners'
                                  const ownersDisplay = ownerNames.join(', ')
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
                                        <div className="sortable-list " key={task.id} data-id={task.id}>
                                            <div className={`shadow ${cardBackgroundClass} p-3 pb-4 rounded-md space-y-2 cursor-move text-xs text-slate-600`}>
                                                {task.image ? <img src="/assets/images/carousel1.jpeg" alt="images" className="h-32 w-full object-cover rounded-md" /> : ''}
                                                <div className="flex items-center justify-between w-full">
                                                  <p className="flex items-center gap-2 break-all text-sm font-semibold text-slate-700">
                                                    {task.title}
                                                    {isVipClient && (
                                                      <span className="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:ring-rose-400/40">
                                                        VIP
                                                      </span>
                                                    )}
                                                  </p>

                                                  {/* Botones a la derecha */}
                                                <div className="flex items-center gap-2 text-[11px]">
                                                <Link
                                                  href={route('frontdesk.order_view', task.id)}
                                                  title='Order View'
                                                  className='flex items-center gap-1 hover:text-success'
                                                >
                                                  <EyeIcon />
                                                </Link>
                                                    <button
                                                      type="button"
                                                      title="Add Activity"
                                                      className="flex h-5 w-5 items-center justify-center rounded-full text-base font-bold leading-none text-sky-600 hover:bg-sky-50 hover:text-sky-700"
                                                      onClick={(event) => {
                                                        event.preventDefault()
                                                        event.stopPropagation()
                                                        const position = activityMenuPosition(event.currentTarget)
                                                        setActivityMenu({
                                                          orderId: task.id,
                                                          x: position.x,
                                                          y: position.y
                                                        })
                                                      }}
                                                    >
                                                      +
                                                    </button>
                                                    {canDeleteTasks && (
                                                      <button
                                                        onClick={(event) => {
                                                          event.preventDefault()
                                                          event.stopPropagation()
                                                          destroyOrder(task.id)
                                                        }}
                                                        type="button"
                                                        title="Delete Order"
                                                        className="flex items-center gap-1 hover:text-danger"
                                                      >
                                                        <DeleteIcon />
                                                      </button>
                                                    )}
                                                    <InfoTooltip
                                                      side="left"
                                                      width={220}
                                                      content={
                                                        <div>
                                                          <div style={{ fontWeight: 700, marginBottom: 6, fontSize: '15px', color: '#0f172a' }}>Information</div>
                                                          <ul style={{ margin: 0, paddingLeft: 16, fontSize: '13px', color: '#1e293b', lineHeight: '1.6' }}>
                                                            <li style={{ marginBottom: 6 }}>Phone: {task.phone ?? '—'}</li>
                                                            <li style={{ marginBottom: 6 }}>Appt Date: {task.schedule_appointment ?? 'No Appt Scheduled'}</li>
                                                            <li style={{ marginBottom: 6 }}>Created By: {task.created_by ?? 'Unknown'}</li>

                                                          </ul>
                                                        </div>
                                                      }
                                                    />
                                                  </div>
                                                </div>
                                         <div className="flex gap-2 items-center flex-wrap">
                                        {(() => {
                                          const tagsArr = Array.isArray(task.tags)
                                            ? task.tags
                                            : Object.values(task.tags ?? {}) // ← si viene como objeto, conviértelo en array

                                          return tagsArr.length
                                            ? (
                                                tagsArr.map((tag: any, i: number) => (
                                              <span
                                                key={i}
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
                                              )
                                        })()}
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
                                                {task.is_supply && (
                                                  <div className="mt-1 flex justify-end">
                                                    <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                                      SUPPLY
                                                    </span>
                                                  </div>
                                                )}
                                               {/* <p className="break-all">{formatPrice(Number(task.precio))}</p> */}
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
      <LostRequestModal
        lostTask={lostTask}
        showModal={showModal}
        onClose={() => {
          setShowModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
        setProjectList={setProjectList}
        updateOrderStatus={updateOrderStatus}
        lostStatusId="LOST REQUEST"
        lossReasonFrontdesk={lossReasonFrontdesk}
         previousStatusId={previousStatusId}
      />
    <QuantifiedModal
        showModal={showQuantifiedModal}
        onClose={() => {
          setShowQuantifiedModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
        task={lostTask}
        setProjectList={setProjectList}
        updateOrderStatus={updateOrderStatus}
        lostStatusId="QUALIFIED"
        lossReasonFrontdesk={lossReasonFrontdesk}
        sources={sources ?? []}
        previousStatusId={previousStatusId}
        order_types={order_types ?? []}
        frame_colors={frame_colors ?? []}
        glass_colors={glass_colors ?? []}
        glass_types={glass_types ?? []}
        glass_coatings={glass_coatings ?? []}
        languages={languages ?? []}
        // errors={FormikErrors<OrderFormValues>}
      />
      <RequestStandByModal
        task={lostTask}
        showModal={showRequestStandByModal}
        previousStatusId={previousStatusId}
        setProjectList={setProjectList}
        onClose={() => {
          setShowRequestStandByModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
      />
      {activityMenu && (
        <>
          <button
            type="button"
            className="fixed inset-0 z-40 cursor-default bg-transparent"
            aria-label="Close activity actions"
            onClick={() => { setActivityMenu(null) }}
          />
          <div
            className="fixed z-50 w-[180px] overflow-hidden rounded-md border border-slate-200 bg-white py-1 shadow-xl"
            style={{ left: activityMenu.x, top: activityMenu.y }}
          >
            <Link
              href={route('activities.index', { mode: 'event', order_id: activityMenu.orderId })}
              className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-sky-50 hover:text-sky-700"
            >
              <CalendarIcon />
              Create Event
            </Link>
            <Link
              href={route('activities.index', { mode: 'call', order_id: activityMenu.orderId })}
              className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700"
            >
              <PhoneIcon />
              Log Call
            </Link>
          </div>
        </>
      )}
    </AuthenticatedCalendarLayout>
  )
}
