import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { type FormikErrors, type FormikHelpers } from 'formik'
import { Head, Link, router } from '@inertiajs/react'
import type { RequestPayload } from '@inertiajs/core'
import { ReactSortable } from 'react-sortablejs'
import { type CompanyContact, type PageProps, type Pipelines, type Tasks, type User } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { tagClasses, type TagColor } from '@/Utils/tags'
import EyeIcon from '@/Components/Icons/EyeIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import InfoTooltip from '@/Components/InfoTooltip'
import OrderBoardFilter, { type BoardFilters, type FilterFieldConfig } from '@/Components/OrderBoardFilter'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import OrderPipelineSort from '@/Components/OrderPipelineSort'
import ProductLineBadge from '@/Components/ProductLineBadge'
import { formatDateOnlyDisplay, isDateOnlyPast } from '@/Utils/dateOnly'
import OrderEditModal from '@/Pages/Frontdesk/OrderEditModal'
import { getValueIdNotNull, loadOrderFormObj, type Order, type OrderFormValues } from '@/Pages/Frontdesk/OrderCommon'
import { type Client } from '@/Pages/Client/ClientCommon'
import { type Attachment, type Source } from '@/types/interfaces/order'
import { PAYMENT_METHODS } from '@/Utils/constants'
import {
  type PipelineSortBy,
  type PipelineSortDir,
  hasPipelineSortInUrl,
  normalizePipelineSort,
  readStoredPipelineSort,
  storePipelineSort
} from '@/Utils/orderPipelineSort'

export interface OwnerOption { id: number, name: string }
type IdOption = { id: number, name: string }
type TagOption = { name: string | null }
interface BoardConfigProps {
  board_title?: string
  index_route?: string
  tasks_route?: string
  sortable_group?: string
  search_origin?: string
  show_create_order?: boolean
  show_new_service?: boolean
  show_esr_task_actions?: boolean
  order_view_route?: string
  can_reorder_orders?: boolean
}

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
  return formatDateOnlyDisplay(value)
}

const isBidDuePast = (value?: string | null): boolean => {
  return isDateOnlyPast(value)
}

const stampTaskAsUpdated = (task: Tasks): Tasks => ({
  ...task,
  date_edited: formatDateForDisplay(new Date())
})

const normalizeStatusValue = (value: string | number): string => String(value).replace(/\s+/g, ' ').trim().toUpperCase()

const matchesStatus = (value: string | number, target: string | number): boolean =>
  normalizeStatusValue(value) === normalizeStatusValue(target)

const normalizeRoleName = (role: unknown): string => {
  const value = typeof role === 'string'
    ? role
    : typeof role === 'object' && role !== null && 'name' in role
      ? String((role as { name?: unknown }).name ?? '')
      : ''

  return value.trim().toLowerCase().replace(/[\s-]+/g, '_')
}

const ESR_SALES_STATUSES = new Set([
  'DEALER REQUEST',
  'FOLLOW UP PROJECTS',
  'REVIEW'
])

const ESR_SERVICES_STATUSES = new Set([
  'SERVICE IN REVIEW',
  'PRODUCTION SERVICES'
])

const ESR_ACCOUNTING_STATUSES = new Set([
  'ACCOUNT RECEIPT',
  'PRE-COORDINATION ACCOUNTING',
  'MATERIALS PICK UP OR DELIVERED',
  'PENDING PAYMENT MATCH'
])

const ESR_PRODUCTION_STATUSES = new Set([
  'PRODUCTION',
  'PENDING GLASS INVOICE'
])

const ESR_COORDINATION_LOGISTICS_STATUSES = new Set([
  'PENDING MAT REYLOS',
  'PENDING MATERIALS DEALER ESR',
  'PENDING MATERIALS ESW',
  'MATERIAL ORDER COMPLETED',
  'MATERIAL ORDER COMPLETED FINANCED',
  'STORAGE MATERIAL',
  'MATERIALS PICK UP OR DELIVERED FINANCED',
  'MATERIALS PICK UP OR DELIVERED BACKORDER'
])

const formatPipelineStatusTitle = (status: string | number, isEsrBoard: boolean): string => {
  const statusLabel = String(status)
  const normalizedStatus = normalizeStatusValue(statusLabel)

  if (!isEsrBoard) return statusLabel
  if (ESR_SALES_STATUSES.has(normalizedStatus)) return `${statusLabel} (Sales)`
  if (ESR_SERVICES_STATUSES.has(normalizedStatus)) return `${statusLabel} (Services)`
  if (ESR_ACCOUNTING_STATUSES.has(normalizedStatus)) return `${statusLabel} (Accounting)`
  if (ESR_PRODUCTION_STATUSES.has(normalizedStatus)) return `${statusLabel} (Production)`
  if (ESR_COORDINATION_LOGISTICS_STATUSES.has(normalizedStatus)) return `${statusLabel} (Coordination and Logistics)`

  return statusLabel
}

const getStageOverdueBadge = (pipeline: Pick<Pipelines, 'id' | 'title'>, task: Tasks): string | null => {
  if (!task.stage_overdue) return null
  if (task.current_status && !matchesStatus(task.current_status, pipeline.title)) return null

  if (task.stage_overdue_extension_active) {
    const extensionDays = task.stage_overdue_extension?.business_days
    return typeof extensionDays === 'number'
      ? `EXTENDED OVERDUE +${extensionDays} BUSINESS DAYS`
      : 'EXTENDED OVERDUE'
  }

  const elapsed = task.stage_business_days_elapsed
  const limit = task.stage_limit_business_days

  if (typeof elapsed === 'number' && typeof limit === 'number') {
    return `OVERDUE: ${elapsed}/${limit} business days`
  }

  return 'OVERDUE'
}

const getStageOverdueBadgeClass = (task: Tasks): string => (
  task.stage_overdue_extension_active
    ? 'bg-amber-400 text-amber-950 ring-amber-300'
    : 'bg-red-600 text-white ring-red-300'
)

const formatOverdueExtensionDate = (value?: string | null): string => {
  if (!value) return '-'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleDateString()
}

const getStageOverdueTitle = (task: Tasks): string | undefined => {
  const lines: string[] = []

  if (task.stage_started_at) {
    lines.push(`Entered status: ${task.stage_started_at}`)
  }

  const extension = task.stage_overdue_extension
  if (extension) {
    lines.push(`Extended: ${extension.business_days} business days`)
    lines.push(`Until: ${formatOverdueExtensionDate(extension.extended_until)}`)
    if (extension.user?.name) lines.push(`By: ${extension.user.name}`)
    if (extension.note) lines.push(`Note: ${extension.note}`)
  }

  return lines.length ? lines.join('\n') : undefined
}

const canExtendStageOverdue = (roleNames: string[]): boolean => (
  roleNames.includes('admin') ||
  roleNames.includes('owner_admin') ||
  roleNames.includes('account_manager') ||
  roleNames.includes('accounting') ||
  roleNames.includes('production') ||
  roleNames.includes('producction') ||
  roleNames.includes('productio')
)

const isPostSaleServiceTask = (task: Tasks): boolean => (
  Boolean(task.is_post_sale_service) && String(task.service_origin ?? '').toUpperCase() === 'SERVICE'
)

const canEditServiceControl = (roleNames: string[]): boolean => (
  roleNames.includes('admin') ||
  roleNames.includes('account_manager') ||
  roleNames.includes('service_manager')
)

const isRestrictedOwnerRoleSet = (roleNames: string[]): boolean => (
  roleNames.includes('owner') &&
  !roleNames.some(roleName => ['admin', 'account_manager', 'owner_admin', 'frontdesk_admin'].includes(roleName))
)

const isRestrictedOwnerAdminRoleSet = (roleNames: string[]): boolean => (
  roleNames.includes('owner_admin') &&
  !roleNames.some(roleName => ['admin', 'account_manager', 'frontdesk_admin'].includes(roleName))
)

const OWNER_ALLOWED_ESR_STATUSES = new Set([
  'DEALER REQUEST',
  'FOLLOW UP PROJECTS',
  'REVIEW'
])

const OWNER_ADMIN_ALLOWED_ESR_STATUSES = new Set([
  ...OWNER_ALLOWED_ESR_STATUSES,
  'ACCOUNT RECEIPT'
])

const OWNER_ADMIN_LOST_SOURCE_STATUSES = new Set([
  'DEALER REQUEST',
  'FOLLOW UP PROJECTS',
  'REVIEW'
])

const INFINITE_SCROLL_STATUSES = new Set(['COMPLETE', 'LOST'])
const DEFAULT_TASKS_PAGE_SIZE = 20
const ESR_TASKS_PAGE_SIZE = 10
const SCROLL_THRESHOLD_PX = 120
const DUPLICATE_ORDER_ERROR_KEY = 'duplicate_order_confirmation'

type StatusPaginationState = { nextPage: number, loading: boolean }
type ActivityMenuState = { orderId: number, x: number, y: number } | null
type ExtendOverdueTarget = {
  id: number
  title: string
  maximumDays: number
  usedDays: number
  remainingDays: number
} | null
type PaymentScheduleTemplates = Record<string, { label: string, percentage: number }[]>
type PendingEsrStatusMove = {
  orderId: number
  oldStatus: string
  newStatus: string
  task: Tasks
} | null
type EsrEditData = {
  order: Order
  clients: Client[]
  owners: User[]
  companies: CompanyContact[]
  sources: Source[]
  sources_clients: string[]
  statuses: string[]
  order_types: string[]
  services: string[]
  methods_of_payment: string[]
  type_of_financing: string[]
  payment_schedule_templates: PaymentScheduleTemplates
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
}

const activityMenuPosition = (element: HTMLElement) => {
  const rect = element.getBoundingClientRect()

  return {
    x: Math.min(rect.left + rect.width - 8, window.innerWidth - 190),
    y: Math.min(rect.top + rect.height + 4, window.innerHeight - 140)
  }
}

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

const isInfiniteScrollStatus = (status: string | number | undefined, isEsrBoard: boolean): boolean => (
  isEsrBoard || INFINITE_SCROLL_STATUSES.has(String(status ?? ''))
)

const buildPaginationState = (pipelines: Pipelines[] = [], isEsrBoard = false, pageSize = DEFAULT_TASKS_PAGE_SIZE): Record<string, StatusPaginationState> => {
  return pipelines.reduce<Record<string, StatusPaginationState>>((acc, pipeline) => {
    if (!isInfiniteScrollStatus(pipeline.title, isEsrBoard)) return acc
    const key = pipeline.id == null ? (pipeline.title ?? '') : pipeline.id.toString()
    if (!key) return acc
    const loadedPages = pipeline.tasks.length ? Math.ceil(pipeline.tasks.length / pageSize) : 0
    acc[key] = {
      nextPage: loadedPages + 1,
      loading: false
    }
    return acc
  }, {})
}

const OrderStorage = ({ auth, data, statuses, owners, supervisors, created_by_users, tags, sources, order_types, product_lines, filters, sort, board_title: boardTitle = 'Order Storage', index_route: indexRoute = 'order-storage.index', tasks_route: tasksRoute = 'order-storage.tasks', sortable_group: sortableGroup = 'order-storage', search_origin: searchOrigin = 'order_storage', show_create_order: showCreateOrder = false, show_new_service: showNewService = false, show_esr_task_actions: showEsrTaskActions = false, order_view_route: orderViewRoute = 'frontdesk.order_view', can_reorder_orders: canReorderOrders = true }: PageProps & BoardConfigProps & { data: Pipelines[], statuses: string[], owners: OwnerOption[], supervisors: IdOption[], created_by_users: IdOption[], tags: TagOption[], sources: string[], order_types: string[], product_lines: string[], filters: BoardFilters, sort: { sort_by?: string, sort_dir?: string } }) => {
  const isEsrBoard = searchOrigin === 'esr_process'
  const tasksPageSize = isEsrBoard ? ESR_TASKS_PAGE_SIZE : DEFAULT_TASKS_PAGE_SIZE
  const [pipelines, setPipelinesState] = useState<Pipelines[]>(() => data)
  const [statusPagination, setStatusPagination] = useState<Record<string, StatusPaginationState>>(() => buildPaginationState(data, isEsrBoard, tasksPageSize))
  const [isFilterOpen, setIsFilterOpen] = useState(false)
  const [activityMenu, setActivityMenu] = useState<ActivityMenuState>(null)
  const [deletingTaskId, setDeletingTaskId] = useState<number | null>(null)
  const [esrEditModalOpen, setEsrEditModalOpen] = useState(false)
  const [esrEditData, setEsrEditData] = useState<EsrEditData | null>(null)
  const [esrEditInitialValues, setEsrEditInitialValues] = useState<OrderFormValues | null>(null)
  const [esrEditError, setEsrEditError] = useState<string | null>(null)
  const [pendingEsrStatusMove, setPendingEsrStatusMove] = useState<PendingEsrStatusMove>(null)
  const [pendingEsrBackwardMove, setPendingEsrBackwardMove] = useState<PendingEsrStatusMove>(null)
  const [esrBackwardModalOpen, setEsrBackwardModalOpen] = useState(false)
  const [esrBackwardNote, setEsrBackwardNote] = useState('')
  const [esrBackwardError, setEsrBackwardError] = useState<string | null>(null)
  const [esrBackwardSaving, setEsrBackwardSaving] = useState(false)
  const [extendOverdueTarget, setExtendOverdueTarget] = useState<ExtendOverdueTarget>(null)
  const [extendOverdueDays, setExtendOverdueDays] = useState('')
  const [extendOverdueNote, setExtendOverdueNote] = useState('')
  const [extendOverdueError, setExtendOverdueError] = useState<string | null>(null)
  const [extendOverdueSaving, setExtendOverdueSaving] = useState(false)
  const dragSnapshotRef = useRef<Pipelines[] | null>(null)
  const sortHydratedRef = useRef(false)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const roleNames = (auth.user.roles ?? []).map(normalizeRoleName).filter(Boolean)
  const canEditPostSaleService = canEditServiceControl(roleNames)
  const canExtendOverdue = canExtendStageOverdue(roleNames)
  const isRestrictedOwner = isRestrictedOwnerRoleSet(roleNames)
  const isRestrictedOwnerAdmin = isRestrictedOwnerAdminRoleSet(roleNames)
  const appliedFilters = filters ?? {}
  const filterQueryParams = useMemo(() => buildFilterQuery(appliedFilters), [appliedFilters])
  const sortState = useMemo(() => normalizePipelineSort(sort), [sort])
  const hasSortInUrl = useMemo(() => hasPipelineSortInUrl(), [])
  const sortQueryParams = useMemo(() => ({
    sort_by: sortState.sort_by,
    sort_dir: sortState.sort_dir
  }), [sortState.sort_by, sortState.sort_dir])
  const statusIndex = useCallback((value: string): number => (
    statuses.findIndex(status => matchesStatus(status, value))
  ), [statuses])
  const isBackwardStatusMove = useCallback((oldStatus: string, newStatus: string): boolean => {
    const oldIndex = statusIndex(oldStatus)
    const newIndex = statusIndex(newStatus)

    return oldIndex >= 0 && newIndex >= 0 && newIndex < oldIndex
  }, [statusIndex])

  const openExtendOverdueModal = (task: Tasks) => {
    setExtendOverdueTarget({
      id: task.id,
      title: task.title,
      maximumDays: task.stage_overdue_extension_maximum_days ?? 30,
      usedDays: task.stage_overdue_extension_days_used ?? 0,
      remainingDays: task.stage_overdue_extension_days_remaining ?? 30
    })
    setExtendOverdueDays('')
    setExtendOverdueNote('')
    setExtendOverdueError(null)
  }

  const closeExtendOverdueModal = () => {
    if (extendOverdueSaving) return
    setExtendOverdueTarget(null)
    setExtendOverdueDays('')
    setExtendOverdueNote('')
    setExtendOverdueError(null)
  }

  const submitExtendOverdue = () => {
    if (!extendOverdueTarget) return

    const businessDays = Number(extendOverdueDays)
    if (!Number.isInteger(businessDays) || businessDays <= 0) {
      setExtendOverdueError('Business days must be greater than 0.')
      return
    }

    if (businessDays > extendOverdueTarget.remainingDays) {
      setExtendOverdueError(`Only ${extendOverdueTarget.remainingDays} business days remain for this status overdue.`)
      return
    }

    if (!extendOverdueNote.trim()) {
      setExtendOverdueError('Note is required.')
      return
    }

    setExtendOverdueSaving(true)
    setExtendOverdueError(null)

    router.post(route('order.stage-overdue-extensions.store', extendOverdueTarget.id), {
      business_days: businessDays,
      note: extendOverdueNote.trim()
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setExtendOverdueTarget(null)
        setExtendOverdueDays('')
        setExtendOverdueNote('')
        router.reload({ only: ['data'], preserveScroll: true })
      },
      onError: (errors) => {
        setExtendOverdueError(String(errors.business_days ?? errors.note ?? 'Unable to save the overdue extension.'))
      },
      onFinish: () => {
        setExtendOverdueSaving(false)
      }
    })
  }

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
    { value: 'product_line', label: 'Product Line', type: 'select', options: product_lines.map((line) => ({ label: line, value: line })) },
    { value: 'is_supply', label: 'Is Supply', type: 'select', options: [{ label: 'Yes', value: '1' }, { label: 'No', value: '0' }] },
    ...(isEsrBoard
      ? [{ value: 'stage_overdue', label: 'Overdue', type: 'select' as const, options: [{ label: 'Yes', value: '1' }, { label: 'No', value: '0' }] }]
      : []),
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
  ]), [statuses, order_types, product_lines, isEsrBoard, owners, sources, tagFilterOptions, supervisors, created_by_users])

  useEffect(() => {
    setPipelinesState(data)
    setStatusPagination(buildPaginationState(data, isEsrBoard, tasksPageSize))
  }, [data, isEsrBoard, tasksPageSize])

  const setPipelines = useCallback<Dispatch<SetStateAction<Pipelines[]>>>((value) => {
    setPipelinesState(prevState => {
      const nextState = typeof value === 'function'
        ? (value as (prev: Pipelines[]) => Pipelines[])(prevState)
        : value

      return nextState
    })
  }, [setPipelinesState])

  useEffect(() => {
    if (sortHydratedRef.current) return
    sortHydratedRef.current = true
    if (hasSortInUrl) return
    const storedSort = readStoredPipelineSort()
    if (!storedSort) return
    if (storedSort.sort_by === sortState.sort_by && storedSort.sort_dir === sortState.sort_dir) return

    router.get(route(indexRoute), { ...filterQueryParams, ...storedSort }, { replace: true, preserveState: true, preserveScroll: true })
  }, [filterQueryParams, hasSortInUrl, indexRoute, sortState.sort_by, sortState.sort_dir])

  useEffect(() => {
    if (!sortHydratedRef.current) return
    storePipelineSort(sortState)
  }, [sortState.sort_by, sortState.sort_dir])

  const applySort = useCallback((nextSortBy: PipelineSortBy, nextSortDir: PipelineSortDir) => {
    router.get(route(indexRoute), { ...filterQueryParams, sort_by: nextSortBy, sort_dir: nextSortDir }, {
      replace: true,
      preserveState: true,
      preserveScroll: true
    })
  }, [filterQueryParams, indexRoute])

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

  const toNull = (value: unknown) => {
    if (value === '' || value === undefined) return null
    return value
  }

  const openEsrEditModalForStatusMove = async (move: NonNullable<PendingEsrStatusMove>) => {
    setEsrEditError(null)
    setPendingEsrStatusMove(move)

    try {
      const response = await fetch(route('esr-process.orders.edit-data', { order: move.orderId }), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      const payload = await response.json().catch(() => null)

      if (!response.ok || !payload?.order) {
        throw new Error(payload?.message ?? 'Unable to load order edit form.')
      }

      const editData = payload as EsrEditData
      setEsrEditData(editData)
      setEsrEditInitialValues({
        ...loadOrderFormObj(editData.order),
        status: move.newStatus
      })
      setEsrEditModalOpen(true)
    } catch (error) {
      setPendingEsrStatusMove(null)
      window.alert(error instanceof Error ? error.message : 'Unable to load order edit form.')
    }
  }

  const applyEsrStatusMove = (move: NonNullable<PendingEsrStatusMove>, updatedOrder: Order) => {
    const { orderId, oldStatus, newStatus, task } = move
    const nextTask: Tasks = stampTaskAsUpdated({
      ...task,
      title: updatedOrder.name ?? task.title,
      project_amount: updatedOrder.project_amount ?? task.project_amount,
      order_type: updatedOrder.order_type ?? task.order_type,
      product_line: updatedOrder.product_line ?? task.product_line,
      esr_design: updatedOrder.esr_design ?? task.esr_design,
      esr_express: updatedOrder.esr_express ?? task.esr_express,
      esr_reylos_glass: updatedOrder.esr_reylos_glass ?? task.esr_reylos_glass,
      esr_service: updatedOrder.esr_service ?? task.esr_service,
      service_origin: updatedOrder.service_origin ?? task.service_origin,
      service_source: updatedOrder.service_source ?? task.service_source,
      is_post_sale_service: updatedOrder.is_post_sale_service ?? task.is_post_sale_service
    })

    setPipelines(prev => prev.map(pipeline => {
      const pipelineKey = pipeline.id?.toString() ?? pipeline.title ?? ''
      const currentTasks = pipeline.tasks ?? []

      if (pipelineKey === oldStatus) {
        const removedTask = currentTasks.find(item => item.id === orderId)
        return {
          ...pipeline,
          tasks: currentTasks.filter(item => item.id !== orderId),
          total_tasks: Math.max(0, (pipeline.total_tasks ?? currentTasks.length) - (removedTask ? 1 : 0)),
          total_project_amount: Math.max(0, getPipelineTotalProjectAmount(pipeline) - (removedTask ? toNumericAmount(removedTask.project_amount) : 0))
        }
      }

      if (pipelineKey === newStatus) {
        const alreadyExists = currentTasks.some(item => item.id === orderId)
        const nextTasks = alreadyExists
          ? currentTasks.map(item => item.id === orderId ? nextTask : item)
          : [nextTask, ...currentTasks]
        return {
          ...pipeline,
          tasks: nextTasks,
          total_tasks: Math.max(0, (pipeline.total_tasks ?? currentTasks.length) + (alreadyExists ? 0 : 1)),
          total_project_amount: getPipelineTotalProjectAmount(pipeline) + (alreadyExists ? 0 : toNumericAmount(nextTask.project_amount))
        }
      }

      return pipeline
    }))
  }

  const applyPendingEsrStatusMove = (updatedOrder: Order) => {
    if (!pendingEsrStatusMove) return

    applyEsrStatusMove(pendingEsrStatusMove, updatedOrder)
  }

  const deleteEsrOrder = async (task: Tasks) => {
    if (!showEsrTaskActions || deletingTaskId !== null) return
    if (!window.confirm(`Are you sure you want to delete order "${task.title}"?`)) return

    setDeletingTaskId(task.id)

    try {
      const response = await fetch(route('esr-process.destroy-order', { order: task.id }), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        }
      })
      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        throw new Error(payload?.message ?? 'Unable to delete order.')
      }

      setPipelines(prev => prev.map(pipeline => {
        const removedTask = pipeline.tasks.find(item => item.id === task.id)
        if (!removedTask) return pipeline

        return {
          ...pipeline,
          tasks: pipeline.tasks.filter(item => item.id !== task.id),
          total_tasks: Math.max(0, (pipeline.total_tasks ?? pipeline.tasks.length) - 1),
          total_project_amount: Math.max(0, getPipelineTotalProjectAmount(pipeline) - toNumericAmount(removedTask.project_amount))
        }
      }))
    } catch (error) {
      window.alert(error instanceof Error ? error.message : 'Unable to delete order.')
    } finally {
      setDeletingTaskId(null)
    }
  }

  const updateOrderStatus = async (orderId: number, status: string, note?: string, confirmCustomerRole = false): Promise<Order | null> => {
    const trimmedNote = note?.trim() ?? ''
    const response = await fetch(route('frontdesk.updateStatus', { order: orderId }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({
        status,
        ...(trimmedNote !== '' ? { note: trimmedNote } : {}),
        ...(confirmCustomerRole ? { confirm_customer_role: true } : {})
      })
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok || !payload?.order) {
      if (response.status === 409 && payload?.requires_confirmation) {
        const confirmed = window.confirm(payload?.message ?? 'This email already belongs to another user. Convert it to customer?')
        if (!confirmed) {
          return null
        }

        return await updateOrderStatus(orderId, status, note, true)
      }

      throw new Error(payload?.message ?? 'Unable to update status.')
    }

    return payload.order as Order
  }

  const closeEsrBackwardModal = () => {
    setEsrBackwardModalOpen(false)
    setPendingEsrBackwardMove(null)
    setEsrBackwardNote('')
    setEsrBackwardError(null)
    setEsrBackwardSaving(false)
  }

  const handleEsrBackwardSubmit = async () => {
    if (!pendingEsrBackwardMove) return

    const note = esrBackwardNote.trim()
    if (note === '') {
      setEsrBackwardError('A note is required to move this ESR order backward.')
      return
    }

    setEsrBackwardSaving(true)
    setEsrBackwardError(null)

    try {
      const updatedOrder = await updateOrderStatus(pendingEsrBackwardMove.orderId, pendingEsrBackwardMove.newStatus, note)
      if (updatedOrder) {
        applyEsrStatusMove(pendingEsrBackwardMove, updatedOrder)
        closeEsrBackwardModal()
      }
    } catch (error) {
      setEsrBackwardError(error instanceof Error ? error.message : 'Unable to update status.')
    } finally {
      setEsrBackwardSaving(false)
    }
  }

  const handleEsrEditSubmit = async (values: OrderFormValues, helpers: FormikHelpers<OrderFormValues>) => {
    if (!esrEditData) return

    setEsrEditError(null)

    try {
      const payload: Record<string, any> = {
        ...values,
        company_contact_id: toNull(values.company_contact_id),
        associate_company_contact_id_1: toNull(values.associate_company_contact_id_1),
        associate_company_contact_id_2: toNull(values.associate_company_contact_id_2),
        associate_client_id_1: toNull(values.associate_client_id_1),
        associate_client_id_2: toNull(values.associate_client_id_2),
        company_source_id: toNull(values.company_source_id),
        associate_source_id_1: toNull(values.associate_source_id_1),
        associate_source_id_2: toNull(values.associate_source_id_2),
        source: typeof values.source === 'string' ? values.source : getValueIdNotNull(values.source)
      }
      const attachmentFiles = Array.isArray(values.attachments)
        ? values.attachments.filter((attachment): attachment is File => attachment instanceof File)
        : []
      const note = String(values.notes ?? '').trim()
      delete payload.attachments
      if (note === '') {
        delete payload.notes
      } else {
        payload.notes = note
      }

      const isCash = values.method_of_payment === PAYMENT_METHODS.CASH
      const isCashAndFinanced = values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE
      const requiresSchedule = isCash || isCashAndFinanced
      const resolvedPaymentScheduleType = requiresSchedule
        ? (isCashAndFinanced ? 'CUSTOMIZED' : (values.payment_schedule_type || null))
        : null

      payload.method_of_payment = values.method_of_payment || null
      payload.type_of_financing = (values.method_of_payment === PAYMENT_METHODS.FINANCED || isCashAndFinanced)
        ? (values.type_of_financing || null)
        : null
      payload.down_payment = isCashAndFinanced
        ? (values.down_payment ?? null)
        : null
      payload.payment_schedule_type = resolvedPaymentScheduleType
      payload.custom_schedule = requiresSchedule && resolvedPaymentScheduleType === 'CUSTOMIZED'
        ? (values.custom_schedule ?? [])
          .map((item: { label?: string, amount?: string | number }) => ({
            label: String(item.label ?? '').trim(),
            amount: Number(String(item.amount ?? '').replace(/,/g, ''))
          }))
          .filter((item: { label: string, amount: number }) => item.label !== '' && Number.isFinite(item.amount))
        : []

      const submitOrderEdit = async (forceDuplicate = false): Promise<any | null> => {
        const response = await fetch(route('frontdesk.orders.update-qualified', { order: values.id }), {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({
            ...payload,
            force_duplicate: forceDuplicate
          })
        })

        const data = await response.json().catch(() => null)

        if (response.status === 422) {
          const duplicateMessageRaw = data?.errors?.[DUPLICATE_ORDER_ERROR_KEY]
          const duplicateMessage = Array.isArray(duplicateMessageRaw)
            ? duplicateMessageRaw[0]
            : duplicateMessageRaw

          if (!forceDuplicate && typeof duplicateMessage === 'string' && duplicateMessage.trim() !== '') {
            const shouldContinue = window.confirm(duplicateMessage)
            if (shouldContinue) {
              return await submitOrderEdit(true)
            }
            return null
          }

          const formattedErrors: FormikErrors<OrderFormValues> = {}
          Object.entries(data?.errors ?? {}).forEach(([field, messages]) => {
            if (Array.isArray(messages) && messages.length > 0) {
              formattedErrors[field as keyof OrderFormValues] = messages[0] as any
            } else if (typeof messages === 'string') {
              formattedErrors[field as keyof OrderFormValues] = messages as any
            }
          })
          helpers.setErrors(formattedErrors)
          setEsrEditError(data?.message ?? 'Please fix the highlighted fields.')
          return null
        }

        if (!response.ok) {
          throw new Error(data?.message ?? 'Failed to update order. Please try again later.')
        }

        return data
      }

      const data = await submitOrderEdit()
      if (!data) return

      let updatedOrder: Order | undefined = data?.order
      if (!updatedOrder) {
        throw new Error('Unexpected server response.')
      }

      if (attachmentFiles.length > 0) {
        const attachmentFormData = new FormData()
        attachmentFiles.forEach(file => attachmentFormData.append('attachments[]', file))
        const attachmentResponse = await fetch(route('order.attachments.store', values.id), {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: attachmentFormData
        })
        const attachmentData = await attachmentResponse.json().catch(() => null)

        if (!attachmentResponse.ok) {
          throw new Error(attachmentData?.message ?? 'Order was updated, but attachments could not be uploaded.')
        }

        const uploadedAttachments = Array.isArray(attachmentData?.attachments) ? attachmentData.attachments as Attachment[] : []
        updatedOrder = {
          ...updatedOrder,
          attachments: uploadedAttachments
        }
      }

      applyPendingEsrStatusMove(updatedOrder)
      setEsrEditModalOpen(false)
      setEsrEditData(null)
      setEsrEditInitialValues(null)
      setPendingEsrStatusMove(null)
    } catch (error) {
      setEsrEditError(error instanceof Error ? error.message : 'Unable to update order.')
    } finally {
      helpers.setSubmitting(false)
    }
  }

  const loadMoreTasks = useCallback(async (statusKey: string, nextPage: number) => {
    setStatusPagination(prev => ({
      ...prev,
      [statusKey]: { nextPage, loading: true }
    }))

    try {
      const response = await fetch(route(tasksRoute, { status: statusKey, page: nextPage, per_page: tasksPageSize, ...filterQueryParams, ...sortQueryParams }), {
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
  }, [setPipelines, setStatusPagination, filterQueryParams, sortQueryParams, tasksPageSize, tasksRoute])

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      leftActions={<OrderGlobalSearch origin={searchOrigin} className="w-full max-w-[420px]" />}
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
          {showCreateOrder && (
            <Link
              className="btn btn-primary"
              href={route('esr-process.create-order')}
            >
              <span>Create Order</span>
            </Link>
          )}
          {showNewService && (
            <Link
              className="btn btn-outline-primary"
              href={route('esr-process.create-service')}
            >
              <span>New Service</span>
            </Link>
          )}
        </div>
      }
    >
      <Head title={boardTitle} />
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
              router.get(route(indexRoute), payload, { replace: true, preserveState: true })
              setIsFilterOpen(false)
            }}
            onReset={() => {
              router.get(route(indexRoute), sortQueryParams, { replace: true, preserveState: false })
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
              const isInfiniteStatus = isInfiniteScrollStatus(pipeline.title, isEsrBoard)
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
                        {formatPipelineStatusTitle(pipeline.title, isEsrBoard)}
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
                      const nextPage = pagination?.nextPage ?? Math.floor(pipeline.tasks.length / tasksPageSize) + 1
                      loadMoreTasks(statusKey, nextPage)
                    }}
                  >
                    <ReactSortable<Tasks>
                      list={pipeline.tasks}
                      setList={(newState) => updatePipelineTasks(pipeline.id, newState as Tasks[])}
                      disabled={!canReorderOrders}
                      group={sortableGroup}
                      animation={200}
                      filter=".js-post-sale-service-card"
                      preventOnFilter={false}
                      onMove={(evt) => {
                        const draggedId = Number(evt.dragged.getAttribute('data-id'))
                        const draggedTask = pipelines
                          .flatMap(pipeline => pipeline.tasks ?? [])
                          .find(task => task.id === draggedId)

                        return !(isEsrBoard && draggedTask && isPostSaleServiceTask(draggedTask))
                      }}
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

                          const movedTaskForLock = pipelines
                            .flatMap(pipeline => pipeline.tasks ?? [])
                            .find(task => task.id === orderId)

                          if (
                            isEsrBoard &&
                            isRestrictedOwner &&
                            !OWNER_ALLOWED_ESR_STATUSES.has(normalizeStatusValue(newStatus))
                          ) {
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            window.alert('Owners can only move ESR orders to Dealer Request, Follow Up Projects, or Review.')
                            return
                          }

                          if (
                            isEsrBoard &&
                            isRestrictedOwnerAdmin &&
                            !(normalizeStatusValue(newStatus) === 'LOST' && OWNER_ADMIN_LOST_SOURCE_STATUSES.has(normalizeStatusValue(oldStatus))) &&
                            !OWNER_ADMIN_ALLOWED_ESR_STATUSES.has(normalizeStatusValue(newStatus))
                          ) {
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            window.alert('Owner admins can only move ESR orders to Dealer Request, Follow Up Projects, Review, Account Receipt, or Lost from Dealer Request, Follow Up Projects, and Review.')
                            return
                          }

                          if (isEsrBoard && movedTaskForLock && isPostSaleServiceTask(movedTaskForLock)) {
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            return
                          }

                          if (isEsrBoard && newStatus === 'PLANNED') {
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            return
                          }

                          if (isEsrBoard && isBackwardStatusMove(oldStatus, newStatus)) {
                            const movedTask = pipelines
                              .flatMap(pipeline => pipeline.tasks ?? [])
                              .find(task => task.id === orderId)
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            if (!movedTask) {
                              window.alert('Unable to load order from the pipeline.')
                              return
                            }
                            setPendingEsrBackwardMove({
                              orderId,
                              oldStatus,
                              newStatus,
                              task: movedTask
                            })
                            setEsrBackwardNote('')
                            setEsrBackwardError(null)
                            setEsrBackwardModalOpen(true)
                            return
                          }

                          if (
                            isEsrBoard &&
                            oldStatus === 'ACCOUNT RECEIPT' &&
                            ['PRODUCTION', 'PRODUCTION SERVICES'].includes(newStatus)
                          ) {
                            const movedTask = pipelines
                              .flatMap(pipeline => pipeline.tasks ?? [])
                              .find(task => task.id === orderId)
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                            dragSnapshotRef.current = null
                            if (!movedTask) {
                              window.alert('Unable to load order from the pipeline.')
                              return
                            }
                            if (movedTask.is_post_sale_service) {
                              await updateOrderStatus(orderId, newStatus)
                            } else {
                              await openEsrEditModalForStatusMove({
                                orderId,
                                oldStatus,
                                newStatus,
                                task: movedTask
                              })
                            }
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
                        const showBidDueDate = Boolean(isCommercialOrder && bidDueDateLabel && !isEsrBoard)
                        const bidDueBadgeClass = bidDuePast
                          ? 'bg-rose-100 text-rose-800 ring-rose-200'
                          : 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                        const bidDueLabelClass = bidDuePast ? 'text-rose-600' : 'text-emerald-600'
                        const isVipClient = Boolean(task.vip_clients)
                        const stageOverdueBadge = isEsrBoard ? getStageOverdueBadge(pipeline, task) : null
                        const esrOptionBadges = [
                          task.esr_design
                            ? { label: 'Design', className: 'bg-emerald-100 text-emerald-800 ring-emerald-200' }
                            : null,
                          task.esr_express
                            ? { label: 'EXPRESS', className: 'bg-yellow-100 text-yellow-800 ring-yellow-300' }
                            : null,
                          task.esr_reylos_glass
                            ? { label: 'Reylos Glass', className: 'bg-blue-100 text-blue-800 ring-blue-200' }
                            : null,
                          task.esr_service
                            ? { label: 'Service', className: 'bg-red-100 text-red-800 ring-red-200' }
                            : null
                        ].filter((badge): badge is { label: string, className: string } => Boolean(badge))
                        const serviceSource = String(task.service_source ?? '').trim().toUpperCase()
                        const isPostSaleServiceCard = isEsrBoard && isPostSaleServiceTask(task)
                        const showPostSaleServiceTabs = isPostSaleServiceCard
                        const postSaleServiceTabs = showPostSaleServiceTabs
                          ? [
                              serviceSource
                                ? {
                                    label: serviceSource,
                                    className: serviceSource === 'ESW'
                                      ? 'bg-indigo-100 text-indigo-800 ring-indigo-200'
                                      : 'bg-sky-100 text-sky-800 ring-sky-200'
                                  }
                                : null,
                              {
                                label: 'Post-Sale Service',
                                className: 'bg-amber-100 text-amber-900 ring-amber-300'
                              }
                            ].filter((tab): tab is { label: string, className: string } => Boolean(tab))
                          : []
                        const paymentBadge = (() => {
                          if (!isEsrBoard) return null
                          const scheduleType = String(task.payment_schedule_type ?? '').trim().toLowerCase()
                          const methodOfPayment = String(task.method_of_payment ?? '').trim().toUpperCase()
                          const isFullPayment = scheduleType === 'full payment'
                          if (isFullPayment) {
                            return { label: 'Full Payment', className: 'bg-emerald-100 text-emerald-800 ring-emerald-200' }
                          }
                          if (methodOfPayment === 'FINANCED') {
                            return { label: 'Financed', className: 'bg-slate-200 text-slate-900 ring-slate-400' }
                          }
                          if (methodOfPayment === 'CASH' && !isFullPayment && task.has_payment_made) {
                            return { label: 'Deposit', className: 'bg-rose-100 text-rose-800 ring-rose-200' }
                          }
                          return null
                        })()
                        const cardBackgroundClass = isPostSaleServiceCard
                          ? 'bg-purple-100 ring-1 ring-purple-300 dark:bg-purple-500/20 dark:ring-purple-400/40'
                          : (task.esr_service
                              ? 'bg-yellow-100 ring-1 ring-yellow-300 dark:bg-yellow-500/20 dark:ring-yellow-400/40'
                              : 'bg-[#f4f4f4] dark:bg-white-dark/20')
                        const orderNumber = String(task.order_number ?? '').trim()

                        return (
                          <div className={`sortable-list ${isPostSaleServiceCard ? 'js-post-sale-service-card' : ''}`} key={task.id} data-id={task.id}>
                            <div className={`shadow ${cardBackgroundClass} p-3 pb-4 rounded-md mb-5 space-y-2 ${isPostSaleServiceCard ? 'cursor-default' : 'cursor-move'} text-xs text-slate-600`}>
                              <div className="flex items-center justify-between w-full">
                                {isEsrBoard && orderNumber !== '' ? (
                                  <div className="inline-flex w-fit items-center rounded-full bg-slate-900 px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white ring-1 ring-slate-700">
                                    Order #{orderNumber}
                                  </div>
                                ) : <span />}
                                <div className="flex items-center gap-2 text-[11px]">
                                  {isPostSaleServiceCard && canEditPostSaleService && task.service_control_id && (
                                    <Link
                                      href={route('service-control.edit', task.service_control_id)}
                                      title="Edit Service Control"
                                      className="flex items-center gap-1 hover:text-info"
                                      onClick={(event) => {
                                        event.stopPropagation()
                                      }}
                                    >
                                      <EditIcon />
                                    </Link>
                                  )}
                                  {isPostSaleServiceCard && task.service_control_id && (
                                    <Link
                                      href={route('service-control.show', task.service_control_id)}
                                      title="View Service Control"
                                      className="flex items-center gap-1 hover:text-success"
                                      onClick={(event) => {
                                        event.stopPropagation()
                                      }}
                                    >
                                      <EyeIcon />
                                    </Link>
                                  )}
                                  {!isPostSaleServiceCard && (
                                    <Link
                                      href={route(orderViewRoute, orderViewRoute === 'esr-process.order-view' ? { id: task.id } : task.id)}
                                      title="Order View"
                                      className="flex items-center gap-1 hover:text-success"
                                    >
                                      <EyeIcon />
                                    </Link>
                                  )}
                                  {!isPostSaleServiceCard && (
                                    showEsrTaskActions
                                      ? (
                                        <>
                                          <button
                                            type="button"
                                            title="Add Activity"
                                            className="flex h-5 w-5 items-center justify-center rounded-full text-base font-bold leading-none text-sky-600 hover:bg-sky-50 hover:text-sky-700"
                                            onClick={(event) => {
                                              event.preventDefault()
                                              event.stopPropagation()
                                              const position = activityMenuPosition(event.currentTarget)
                                              setActivityMenu({ orderId: task.id, x: position.x, y: position.y })
                                            }}
                                          >
                                            +
                                          </button>
                                          <button
                                            type="button"
                                            title="Delete Order"
                                            disabled={deletingTaskId === task.id}
                                            className="flex items-center gap-1 hover:text-danger disabled:cursor-not-allowed disabled:opacity-50"
                                            onClick={(event) => {
                                              event.preventDefault()
                                              event.stopPropagation()
                                              void deleteEsrOrder(task)
                                            }}
                                          >
                                            <DeleteIcon className="h-4 w-4" />
                                          </button>
                                        </>
                                        )
                                      : (
                                        <button
                                          onClick={() => {}}
                                          type="button"
                                          className="flex items-center gap-1 hover:text-info"
                                        >
                                          <EditIcon />
                                        </button>
                                        )
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
                                          <li style={{ marginBottom: 6 }}>Owners: {ownersDisplay}</li>
                                        </ul>
                                      </div>
                                    }
                                  />
                                </div>
                              </div>
                              <p className="flex items-center gap-2 break-all text-sm font-semibold text-slate-700 dark:text-white">
                                {task.title}
                                {isVipClient && (
                                  <span className="inline-flex items-center rounded-full bg-rose-100 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:ring-rose-400/40">
                                    VIP
                                  </span>
                                )}
                              </p>
                              <div className="flex gap-2 items-center flex-wrap">
                                {task.is_parent_order && !isEsrBoard && (
                                  <span
                                    className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wide ring-1 bg-fuchsia-600 text-white ring-fuchsia-700 shadow-sm"
                                    title={task.child_orders_count ? `${task.child_orders_count} linked split order${task.child_orders_count === 1 ? '' : 's'}` : undefined}
                                  >
                                    Owner Commission Order
                                  </span>
                                )}
                                {postSaleServiceTabs.map((tab) => (
                                  <span
                                    key={`${task.id}-post-sale-${tab.label}`}
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ${tab.className}`}
                                  >
                                    {tab.label}
                                  </span>
                                ))}
                                {!isPostSaleServiceCard && <ProductLineBadge productLine={task.product_line} />}
                                {esrOptionBadges.map((badge) => (
                                  <span
                                    key={`${task.id}-esr-${badge.label}`}
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ${badge.className}`}
                                  >
                                    {badge.label}
                                  </span>
                                ))}
                                {paymentBadge && (
                                  <span
                                    className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide ring-1 ${paymentBadge.className}`}
                                  >
                                    {paymentBadge.label}
                                  </span>
                                )}
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
                              {(stageOverdueBadge || (canExtendOverdue && task.stage_overdue)) && (
                                <div className="mt-2 flex items-center justify-end gap-2">
                                  {stageOverdueBadge && (
                                    <span
                                      className={`inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide ring-2 shadow-sm ${getStageOverdueBadgeClass(task)}`}
                                      title={getStageOverdueTitle(task)}
                                    >
                                      {stageOverdueBadge}
                                    </span>
                                  )}
                                  {canExtendOverdue && task.stage_overdue && (task.stage_overdue_extension_days_remaining ?? 30) > 0 && (
                                    <button
                                      type="button"
                                      title="Extend Overdue"
                                      className="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide text-amber-700 ring-1 ring-amber-300 hover:bg-amber-50"
                                      onClick={(event) => {
                                        event.preventDefault()
                                        event.stopPropagation()
                                        openExtendOverdueModal(task)
                                      }}
                                    >
                                      Extend
                                    </button>
                                  )}
                                </div>
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
      {esrBackwardModalOpen && pendingEsrBackwardMove && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-slate-800">ESR Status Rollback</h3>
                <p className="text-xs text-slate-500">
                  {pendingEsrBackwardMove.oldStatus || 'Current status'} -&gt; {pendingEsrBackwardMove.newStatus}
                </p>
              </div>
              <button
                type="button"
                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                onClick={closeEsrBackwardModal}
                disabled={esrBackwardSaving}
              >
                <span className="sr-only">Close</span>
                x
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="esr-board-backward-note">
                  Note <span className="text-rose-500">*</span>
                </label>
                <textarea
                  id="esr-board-backward-note"
                  className="form-textarea w-full resize-none placeholder:text-slate-400"
                  rows={4}
                  value={esrBackwardNote}
                  onChange={(event) => {
                    setEsrBackwardNote(event.target.value)
                    if (esrBackwardError) setEsrBackwardError(null)
                  }}
                  placeholder="Explain why this ESR order is moving backward"
                  disabled={esrBackwardSaving}
                />
              </div>
              {esrBackwardError && (
                <p className="text-sm text-rose-600">{esrBackwardError}</p>
              )}
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeEsrBackwardModal}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={esrBackwardSaving}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleEsrBackwardSubmit}
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={esrBackwardSaving}
                >
                  {esrBackwardSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {extendOverdueTarget && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-slate-800">Extend Overdue</h3>
                <p className="text-xs text-slate-500">{extendOverdueTarget.title}</p>
                <p className="mt-1 text-xs font-medium text-amber-700">
                  {extendOverdueTarget.remainingDays} of {extendOverdueTarget.maximumDays} business days remaining
                  {extendOverdueTarget.usedDays > 0 ? ` (${extendOverdueTarget.usedDays} used)` : ''}
                </p>
              </div>
              <button
                type="button"
                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                onClick={closeExtendOverdueModal}
                disabled={extendOverdueSaving}
              >
                <span className="sr-only">Close</span>
                x
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="extend-overdue-business-days">
                  Business days <span className="text-rose-500">*</span>
                </label>
                <input
                  id="extend-overdue-business-days"
                  type="number"
                  min={1}
                  max={extendOverdueTarget.remainingDays}
                  step={1}
                  className="form-input w-full"
                  value={extendOverdueDays}
                  onChange={(event) => {
                    setExtendOverdueDays(event.target.value)
                    if (extendOverdueError) setExtendOverdueError(null)
                  }}
                  disabled={extendOverdueSaving}
                />
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="extend-overdue-note">
                  Note <span className="text-rose-500">*</span>
                </label>
                <textarea
                  id="extend-overdue-note"
                  className="form-textarea w-full resize-none placeholder:text-slate-400"
                  rows={4}
                  value={extendOverdueNote}
                  onChange={(event) => {
                    setExtendOverdueNote(event.target.value)
                    if (extendOverdueError) setExtendOverdueError(null)
                  }}
                  disabled={extendOverdueSaving}
                  placeholder="Explain why this overdue order is being extended"
                />
              </div>
              {extendOverdueError && (
                <p className="text-sm text-rose-600">{extendOverdueError}</p>
              )}
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeExtendOverdueModal}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={extendOverdueSaving}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={submitExtendOverdue}
                  className="rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-amber-950 shadow hover:bg-amber-400 disabled:cursor-not-allowed disabled:bg-amber-300"
                  disabled={extendOverdueSaving}
                >
                  {extendOverdueSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {esrEditData && esrEditInitialValues && (
        <OrderEditModal
          open={esrEditModalOpen}
          initialValues={esrEditInitialValues}
          onClose={() => {
            setEsrEditModalOpen(false)
            setEsrEditData(null)
            setEsrEditInitialValues(null)
            setEsrEditError(null)
            setPendingEsrStatusMove(null)
          }}
          onSubmit={handleEsrEditSubmit}
          clients={esrEditData.clients ?? []}
          owners={esrEditData.owners ?? []}
          status={esrEditData.statuses ?? []}
          sources={esrEditData.sources ?? []}
          order_types={esrEditData.order_types ?? []}
          companies={esrEditData.companies ?? []}
          sourcesClients={esrEditData.sources_clients ?? []}
          frame_colors={esrEditData.frame_colors ?? []}
          glass_colors={esrEditData.glass_colors ?? []}
          glass_types={esrEditData.glass_types ?? []}
          glass_coatings={esrEditData.glass_coatings ?? []}
          languages={esrEditData.languages ?? []}
          methodsOfPayment={esrEditData.methods_of_payment ?? []}
          financingOptions={esrEditData.type_of_financing ?? []}
          paymentScheduleTemplates={esrEditData.payment_schedule_templates ?? {}}
          services={esrEditData.services ?? []}
          showPaymentInformationSection
          canManageOwners={true}
          esrMode={isEsrBoard}
          attachments={Array.isArray(esrEditData.order.attachments) ? esrEditData.order.attachments.filter((attachment): attachment is Attachment => !(attachment instanceof File)) : []}
          errorMessage={esrEditError}
        />
      )}
    </AuthenticatedCalendarLayout>
  )
}

export default OrderStorage
