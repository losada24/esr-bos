import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link, router } from '@inertiajs/react'
import type { RequestPayload } from '@inertiajs/core'
import { ReactSortable } from 'react-sortablejs'
import { type Role, type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner, isOwnerAdmin, isFrontdeskAdmin } from '@/Utils/user'

import { tagClasses, type TagColor } from '@/Utils/tags'
import { PAYMENT_METHODS } from '@/Utils/constants'
import EyeIcon from '@/Components/Icons/EyeIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import InfoTooltip from '@/Components/InfoTooltip'
import OrderBoardFilter, { type BoardFilters, type FilterFieldConfig } from '@/Components/OrderBoardFilter'
import OrderGlobalSearch from '@/Components/OrderGlobalSearch'
import OrderPipelineSort from '@/Components/OrderPipelineSort'
import ProductLineBadge from '@/Components/ProductLineBadge'
import { formatDateOnlyDisplay, isDateOnlyPast } from '@/Utils/dateOnly'
import EstimateScheduleModal from './EstimateScheduleModal'
import FollowUpModal from './FollowUpModal'
import StandByNoteModal from './StandByNoteModal'
import RequestRescheduleModal from './RequestRescheduleModal'
import PreContractNoteModal from './PreContractNoteModal'
import ContractSignedModal, { PRIMARY_CLIENT_EMAIL_SELECTION } from './ContractSignedModal'
import LostContractModal from './LostContractModal'
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
type ActivityMenuState = { orderId: number, x: number, y: number } | null

type PaymentScheduleTemplateItem = { label: string, percentage: number }
type PaymentScheduleTemplates = Record<string, PaymentScheduleTemplateItem[]>

type ContractSignedSubmitValues = {
  projectName: string
  productLine: string
  esrCost: string
  projectAmount: string
  downPayment: string
  jobAddress: string
  city: string
  jobState: string
  jobZip: string
  methodOfPayment: string
  typeOfFinancing: string
  clientEmailSelection: string
  orderCompanyContactId?: number | null
  attachments: File[]
  nameCheck: boolean
  addressCheck: boolean
  amountCheck: boolean
  emailCheck: boolean
  cityPermits: boolean
  associationPermits: boolean
  pendingFinancingOrDeposit: boolean
  pendingHoaApproval: boolean
  paymentScheduleType: string
  customSchedule: Array<{ label: string, amount: number }>
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

const ESTIMATE_STATUS = 'ESTIMATE & APPT SCHEDULE'
const REQUEST_RESCHEDULE_STATUS = 'REQUEST RE-SCHEDULE'
const LOST_CONTRACT_STATUS = 'LOST CONTRACT'
const FOLLOW_UP_STATUSES: string[] = ['FOLLOW UP', 'FOLLOW UP PROJECTS']
const STAND_BY_STATUS = 'STAND BY'
const DAY_IN_MS = 24 * 60 * 60 * 1000
const FOLLOW_UP_STALE_THRESHOLD_MS = 45 * DAY_IN_MS
const STAND_BY_STALE_THRESHOLD_MS = 120 * DAY_IN_MS
const ESTIMATE_RESIDENTIAL_THRESHOLD_MS = 2 * DAY_IN_MS
const ESTIMATE_COMMERCIAL_THRESHOLD_MS = 7 * DAY_IN_MS
const INFINITE_SCROLL_STATUSES = new Set(['CONTRACT SIGNED BY CLIENT', 'LOST CONTRACT'])
const TASKS_PAGE_SIZE = 20
const SCROLL_THRESHOLD_PX = 120

const activityMenuPosition = (element: HTMLElement) => {
  const rect = element.getBoundingClientRect()

  return {
    x: Math.min(rect.left + rect.width - 8, window.innerWidth - 190),
    y: Math.min(rect.top + rect.height + 4, window.innerHeight - 140)
  }
}
const CUSTOM_SCHEDULE_TYPE = 'CUSTOMIZED'
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

const buildEmptyCustomSchedule = () =>
  Array.from({ length: 6 }, () => ({ label: '', amount: '' }))

const getFollowUpStaleClass = (pipeline: Pipelines, task: Tasks): string | null => {
  const pipelineId = pipeline?.id != null ? pipeline.id.toString() : ''
  if (!FOLLOW_UP_STATUSES.includes(pipelineId)) {
    return null
  }
  const iso = task.follow_up_started_at_iso
  if (!iso) return null
  const createdTimestamp = Date.parse(iso)
  if (Number.isNaN(createdTimestamp)) return null
  return (Date.now() - createdTimestamp) >= FOLLOW_UP_STALE_THRESHOLD_MS
    ? 'bg-red-200 dark:bg-red-500/40'
    : null
}

const getStandByStaleClass = (pipeline: Pipelines, task: Tasks): string | null => {
  const pipelineId = pipeline?.id != null ? pipeline.id.toString() : ''
  if (pipelineId !== STAND_BY_STATUS) {
    return null
  }
  const iso = task.status_created_at_iso
  if (!iso) return null
  const statusTimestamp = Date.parse(iso)
  if (Number.isNaN(statusTimestamp)) return null
  return (Date.now() - statusTimestamp) >= STAND_BY_STALE_THRESHOLD_MS
    ? 'bg-red-200 dark:bg-red-500/40'
    : null
}

const getEstimateStaleClass = (pipeline: Pipelines, task: Tasks): string | null => {
  const pipelineId = pipeline?.id != null ? pipeline.id.toString() : ''
  if (pipelineId !== ESTIMATE_STATUS) {
    return null
  }

  const parseIso = (value?: string | null): number | null => {
    if (!value) return null
    const timestamp = Date.parse(value)
    return Number.isNaN(timestamp) ? null : timestamp
  }

  const normalizedOrderType = (task.order_type ?? '').trim().toUpperCase()
  if (normalizedOrderType === 'RESIDENTIAL') {
    const appointmentTimestamp = parseIso(task.schedule_appointment_iso)
    if (appointmentTimestamp !== null) {
      return (Date.now() - appointmentTimestamp) >= ESTIMATE_RESIDENTIAL_THRESHOLD_MS
        ? 'bg-red-200 dark:bg-red-500/40'
        : null
    }

    const statusTimestamp = parseIso(task.status_created_at_iso)
    if (statusTimestamp === null) return null
    return (Date.now() - statusTimestamp) >= ESTIMATE_RESIDENTIAL_THRESHOLD_MS
      ? 'bg-red-200 dark:bg-red-500/40'
      : null
  }

  if (normalizedOrderType === 'COMMERCIAL') {
    const statusTimestamp = parseIso(task.status_created_at_iso)
    if (statusTimestamp === null) return null
    return (Date.now() - statusTimestamp) >= ESTIMATE_COMMERCIAL_THRESHOLD_MS
      ? 'bg-red-200 dark:bg-red-500/40'
      : null
  }

  return null
}

const normalizeStatusValue = (value: string): string => value.replace(/\s+/g, ' ').trim().toUpperCase()
const matchesStatus = (value: string, target: string): boolean => normalizeStatusValue(value) === normalizeStatusValue(target)

export default function Sales ({ auth, data, lossReasonFrontdesk, sources, order_types, product_lines, statuses, owners, supervisors, created_by_users, tags, filters, sort, methods_of_payment, type_of_financing, payment_schedule_templates }: PageProps & { data: Pipelines[], lossReasonFrontdesk: string [], sources: string[], order_types: string[], product_lines: string[], statuses: string[], owners: OwnerOption[], supervisors: IdOption[], created_by_users: IdOption[], tags: TagOption[], filters: BoardFilters, sort: { sort_by?: string, sort_dir?: string }, methods_of_payment: string[], type_of_financing: string[], payment_schedule_templates: PaymentScheduleTemplates }) {
  const roleNames = auth.user.roles.map((role: Role) => role.name)
  const IS_ADMIN = isAdmin(roleNames)
  const IS_OWNER_ADMIN = isOwnerAdmin(roleNames)
  const IS_ACCOUNT_MANAGER = isAccountManager(roleNames)
  const IS_SUPERVISOR = isSupervisor(roleNames)
  const IS_SERVICE_MANAGER = isServiceManager(roleNames)
  const IS_INSTALLER = isInstaller(roleNames)
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(roleNames)
  const IS_OWNER = isOwner(roleNames)
  const CAN_MOVE_TO_ESTIMATE = IS_ADMIN || IS_OWNER_ADMIN || isFrontdeskAdmin(roleNames)
  const CAN_DELETE_ORDER = IS_ADMIN || IS_OWNER_ADMIN

  const [projectList, setProjectListState] = useState<Pipelines[]>(() => data)
  const [showModal, setShowModal] = useState(false)
  const [lostTask, setLostTask] = useState<Tasks | null>(null)
  const [showQuantifiedModal, setShowQuantifiedModal] = useState(false)
  const [previousStatusId, setPreviousStatusId] = useState<string | null>(null)
  const [scheduleModalOpen, setScheduleModalOpen] = useState(false)
  const [pendingMove, setPendingMove] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [scheduleInitialValues, setScheduleInitialValues] = useState<{ scheduleDate: string, ownerIds: number[] }>({ scheduleDate: '', ownerIds: [] })
  const [scheduleSaving, setScheduleSaving] = useState(false)
  const [scheduleError, setScheduleError] = useState<string | null>(null)
  const [followUpModalOpen, setFollowUpModalOpen] = useState(false)
  const [followUpInitialValues, setFollowUpInitialValues] = useState<{ projectAmount: string, note: string }>({ projectAmount: '', note: '' })
  const [followUpSaving, setFollowUpSaving] = useState(false)
  const [followUpError, setFollowUpError] = useState<string | null>(null)
  const [pendingFollowUp, setPendingFollowUp] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [standByModalOpen, setStandByModalOpen] = useState(false)
  const [standByInitialNote, setStandByInitialNote] = useState('')
  const [standBySaving, setStandBySaving] = useState(false)
  const [standByError, setStandByError] = useState<string | null>(null)
  const [pendingStandBy, setPendingStandBy] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [requestRescheduleModalOpen, setRequestRescheduleModalOpen] = useState(false)
  const [requestRescheduleInitialNote, setRequestRescheduleInitialNote] = useState('')
  const [requestRescheduleSaving, setRequestRescheduleSaving] = useState(false)
  const [requestRescheduleError, setRequestRescheduleError] = useState<string | null>(null)
  const [pendingRequestReschedule, setPendingRequestReschedule] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [preContractModalOpen, setPreContractModalOpen] = useState(false)
  const [preContractInitialNote, setPreContractInitialNote] = useState('')
  const [preContractSaving, setPreContractSaving] = useState(false)
  const [preContractError, setPreContractError] = useState<string | null>(null)
  const [pendingPreContract, setPendingPreContract] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [contractSignedModalOpen, setContractSignedModalOpen] = useState(false)
  const [contractSignedInitialValues, setContractSignedInitialValues] = useState<{ projectName: string, projectAmount: string, downPayment: string, jobAddress: string, city: string, jobState: string, jobZip: string, methodOfPayment: string, typeOfFinancing: string, clientEmailSelection: string, orderCompanyContactId: number | null, nameCheck: boolean, addressCheck: boolean, amountCheck: boolean, emailCheck: boolean, cityPermits: boolean, associationPermits: boolean, pendingFinancingOrDeposit: boolean | null, pendingHoaApproval: boolean | null, paymentScheduleType: string, customSchedule: Array<{ label: string, amount: string }> }>({ projectName: '', projectAmount: '', downPayment: '', jobAddress: '', city: '', jobState: '', jobZip: '', methodOfPayment: '', typeOfFinancing: '', clientEmailSelection: PRIMARY_CLIENT_EMAIL_SELECTION, orderCompanyContactId: null, nameCheck: false, addressCheck: false, amountCheck: false, emailCheck: false, cityPermits: false, associationPermits: false, pendingFinancingOrDeposit: null, pendingHoaApproval: null, paymentScheduleType: '', customSchedule: buildEmptyCustomSchedule() })
  const [contractSignedSaving, setContractSignedSaving] = useState(false)
  const [contractSignedError, setContractSignedError] = useState<string | null>(null)
  const [contractSignedConfirmation, setContractSignedConfirmation] = useState<null | { message: string, userEmail?: string, userRoles?: string[] }>(null)
  const [contractSignedPendingValues, setContractSignedPendingValues] = useState<ContractSignedSubmitValues | null>(null)
  const [pendingContractSigned, setPendingContractSigned] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [lostContractModalOpen, setLostContractModalOpen] = useState(false)
  const [lostContractSaving, setLostContractSaving] = useState(false)
  const [lostContractError, setLostContractError] = useState<string | null>(null)
  const [pendingLostContract, setPendingLostContract] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [statusPagination, setStatusPagination] = useState<Record<string, StatusPaginationState>>(() => buildPaginationState(data))
  const [isFilterOpen, setIsFilterOpen] = useState(false)
  const [deletingTaskId, setDeletingTaskId] = useState<number | null>(null)
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
    { value: 'product_line', label: 'Product Line', type: 'select', options: product_lines.map((line) => ({ label: line, value: line })) },
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
  ]), [statuses, order_types, product_lines, owners, sources, lossReasonFrontdesk, tagFilterOptions, supervisors, created_by_users])

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

    router.get(route('sales.index'), { ...filterQueryParams, ...storedSort }, { replace: true, preserveState: true, preserveScroll: true })
  }, [filterQueryParams, hasSortInUrl, sortState.sort_by, sortState.sort_dir])

  useEffect(() => {
    if (!sortHydratedRef.current) return
    storePipelineSort(sortState)
  }, [sortState.sort_by, sortState.sort_dir])

  const applySort = useCallback((nextSortBy: PipelineSortBy, nextSortDir: PipelineSortDir) => {
    router.get(route('sales.index'), { ...filterQueryParams, sort_by: nextSortBy, sort_dir: nextSortDir }, {
      replace: true,
      preserveState: true,
      preserveScroll: true
    })
  }, [filterQueryParams])

  const ownerOptions = owners ?? []
  const paymentMethods = methods_of_payment ?? []
  const financingOptions = type_of_financing ?? []

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  const restoreTaskToStatus = (task: Tasks, status: string) => {
    const taskAmount = toNumericAmount(task.project_amount)
    setProjectList(prev => prev.map(p => {
      const pipelineId = p.id?.toString?.() ?? ''
      const isTarget = matchesStatus(pipelineId, status)
      const existingTask = p.tasks.find(t => Number(t.id) === Number(task.id))
      const hadTask = Boolean(existingTask)
      const existingTaskAmount = toNumericAmount(existingTask?.project_amount)
      const filtered = p.tasks.filter(t => Number(t.id) !== Number(task.id))
      const baseTotal = p.total_tasks ?? p.tasks.length
      const baseProjectAmount = getPipelineTotalProjectAmount(p)
      let nextTotal = baseTotal
      let nextProjectAmount = baseProjectAmount
      if (hadTask && !isTarget) {
        nextTotal = Math.max(0, nextTotal - 1)
        nextProjectAmount = Math.max(0, nextProjectAmount - existingTaskAmount)
      }
      if (isTarget && !hadTask) {
        nextTotal += 1
        nextProjectAmount += taskAmount
      }
      const totalChanged = nextTotal !== baseTotal
      const amountChanged = nextProjectAmount !== baseProjectAmount
      if (isTarget) {
        const tasks = [...filtered, task]
        if (!totalChanged && !amountChanged) return { ...p, tasks }
        return { ...p, tasks, ...(totalChanged ? { total_tasks: nextTotal } : {}), ...(amountChanged ? { total_project_amount: nextProjectAmount } : {}) }
      }
      if (!totalChanged && !amountChanged) return { ...p, tasks: filtered }
      return { ...p, tasks: filtered, ...(totalChanged ? { total_tasks: nextTotal } : {}), ...(amountChanged ? { total_project_amount: nextProjectAmount } : {}) }
    }))
  }

  const applyTaskMove = (task: Tasks, newStatus: string) => {
    const taskAmount = toNumericAmount(task.project_amount)
    setProjectList(prev => prev.map(pipeline => {
      const pipelineId = pipeline.id?.toString?.() ?? ''
      const isNew = matchesStatus(pipelineId, newStatus)
      const existingTask = pipeline.tasks.find(t => Number(t.id) === Number(task.id))
      const hadTask = Boolean(existingTask)
      const existingTaskAmount = toNumericAmount(existingTask?.project_amount)
      const filtered = pipeline.tasks.filter(t => Number(t.id) !== Number(task.id))
      const tasks = isNew ? [...filtered, task] : filtered
      const baseTotal = pipeline.total_tasks ?? pipeline.tasks.length
      const baseProjectAmount = getPipelineTotalProjectAmount(pipeline)
      let nextTotal = baseTotal
      let nextProjectAmount = baseProjectAmount
      if (hadTask && !isNew) {
        nextTotal = Math.max(0, nextTotal - 1)
        nextProjectAmount = Math.max(0, nextProjectAmount - existingTaskAmount)
      }
      if (!hadTask && isNew) {
        nextTotal += 1
        nextProjectAmount += taskAmount
      }
      const totalChanged = nextTotal !== baseTotal
      const amountChanged = nextProjectAmount !== baseProjectAmount
      if (!totalChanged && !amountChanged) return { ...pipeline, tasks }
      return {
        ...pipeline,
        tasks,
        ...(totalChanged ? { total_tasks: nextTotal } : {}),
        ...(amountChanged ? { total_project_amount: nextProjectAmount } : {})
      }
    }))
  }

  const removeTaskFromBoard = (taskId: number) => {
    setProjectList(prev => prev.map((pipeline) => {
      const taskToRemove = pipeline.tasks.find((task) => Number(task.id) === Number(taskId))
      if (!taskToRemove) return pipeline

      const removedAmount = toNumericAmount(taskToRemove.project_amount)
      const nextTasks = pipeline.tasks.filter((task) => Number(task.id) !== Number(taskId))
      const nextTotal = Math.max(0, (pipeline.total_tasks ?? pipeline.tasks.length) - 1)
      const nextAmount = Math.max(0, getPipelineTotalProjectAmount(pipeline) - removedAmount)

      return {
        ...pipeline,
        tasks: nextTasks,
        total_tasks: nextTotal,
        total_project_amount: nextAmount
      }
    }))
  }

  const handleDeleteOrder = async (task: Tasks) => {
    if (!CAN_DELETE_ORDER || deletingTaskId !== null) return
    if (!window.confirm(`Are you sure you want to delete order "${task.title}"?`)) return

    setDeletingTaskId(task.id)

    try {
      const response = await fetch(route('sales.destroy_order', task.id), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        }
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        throw new Error(payload?.message ?? 'Unable to delete order.')
      }

      removeTaskFromBoard(task.id)
    } catch (error: any) {
      console.error('delete-order error', error)
      window.alert(error?.message ?? 'Unable to delete order.')
    } finally {
      setDeletingTaskId(null)
    }
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
      const response = await fetch(route('sales.tasks', { status: statusKey, page: nextPage, per_page: TASKS_PAGE_SIZE, ...filterQueryParams, ...sortQueryParams }), {
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
            if (!matchesStatus(pipelineKey, statusKey)) return p
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
            return matchesStatus(pipelineKey, statusKey) ? { ...p, total_tasks: totalTasks } : p
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

  const closeScheduleModal = (restoreTask = false) => {
    if (restoreTask && pendingMove) {
      restoreTaskToStatus(pendingMove.task, pendingMove.oldStatus)
    }
    setScheduleModalOpen(false)
    setScheduleError(null)
    setPendingMove(null)
    setScheduleInitialValues({ scheduleDate: '', ownerIds: [] })
  }

  const handleScheduleSubmit = async (values: { scheduleDate: string, ownerIds: number[] }) => {
    if (!pendingMove) return
    setScheduleSaving(true)
    setScheduleError(null)

    try {
      const response = await fetch(route('sales.assign_estimate', pendingMove.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          schedule_appointment: values.scheduleDate || null,
          owner_ids: values.ownerIds
        }),
      })

      if (!response.ok) {
        if (response.status === 422) {
          const data = await response.json()
          const messages = Object.values(data.errors ?? {}).flat()
          setScheduleError(typeof messages[0] === 'string' ? messages[0] : 'Revisa los datos ingresados.')
          return
        }
        throw new Error('No se pudo guardar la asignación.')
      }

      const data = await response.json()
      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingMove.task,
        schedule_appointment: data.order.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso,
        owner_ids: data.order.owner_ids,
        owners: data.order.owners
      })

      applyTaskMove(updatedTask, pendingMove.newStatus)

      closeScheduleModal()
    } catch (error: any) {
      console.error('assign-estimate error', error)
      setScheduleError(error?.message ?? 'No se pudo guardar la asignación.')
    } finally {
      setScheduleSaving(false)
    }
  }

  const closeFollowUpModal = (restoreTask = false) => {
    if (restoreTask && pendingFollowUp) {
      restoreTaskToStatus(pendingFollowUp.task, pendingFollowUp.oldStatus)
    }
    setFollowUpModalOpen(false)
    setFollowUpError(null)
    setFollowUpSaving(false)
    setFollowUpInitialValues({ projectAmount: '', note: '' })
    setPendingFollowUp(null)
  }

  const closeStandByModal = (restoreTask = false) => {
    if (restoreTask && pendingStandBy) {
      restoreTaskToStatus(pendingStandBy.task, pendingStandBy.oldStatus)
    }
    setStandByModalOpen(false)
    setStandByError(null)
    setStandBySaving(false)
    setStandByInitialNote('')
    setPendingStandBy(null)
  }

  const closeRequestRescheduleModal = (restoreTask = false) => {
    if (restoreTask && pendingRequestReschedule) {
      restoreTaskToStatus(pendingRequestReschedule.task, pendingRequestReschedule.oldStatus)
    }
    setRequestRescheduleModalOpen(false)
    setRequestRescheduleError(null)
    setRequestRescheduleSaving(false)
    setRequestRescheduleInitialNote('')
    setPendingRequestReschedule(null)
  }

  const closePreContractModal = (restoreTask = false) => {
    if (restoreTask && pendingPreContract) {
      restoreTaskToStatus(pendingPreContract.task, pendingPreContract.oldStatus)
    }
    setPreContractModalOpen(false)
    setPreContractError(null)
    setPreContractSaving(false)
    setPreContractInitialNote('')
    setPendingPreContract(null)
  }

  const closeContractSignedModal = (restoreTask = false) => {
    if (restoreTask && pendingContractSigned) {
      restoreTaskToStatus(pendingContractSigned.task, pendingContractSigned.oldStatus)
    }
    setContractSignedModalOpen(false)
    setContractSignedError(null)
    setContractSignedConfirmation(null)
    setContractSignedPendingValues(null)
    setContractSignedSaving(false)
    setContractSignedInitialValues({ projectName: '', projectAmount: '', downPayment: '', jobAddress: '', city: '', jobState: '', jobZip: '', methodOfPayment: '', typeOfFinancing: '', clientEmailSelection: PRIMARY_CLIENT_EMAIL_SELECTION, orderCompanyContactId: null, nameCheck: false, addressCheck: false, amountCheck: false, emailCheck: false, cityPermits: false, associationPermits: false, pendingFinancingOrDeposit: null, pendingHoaApproval: null, paymentScheduleType: '', customSchedule: buildEmptyCustomSchedule() })
    setPendingContractSigned(null)
  }

  const closeLostContractModal = (restoreTask = false) => {
    if (restoreTask && pendingLostContract) {
      restoreTaskToStatus(pendingLostContract.task, pendingLostContract.oldStatus)
    }
    setLostContractModalOpen(false)
    setLostContractSaving(false)
    setLostContractError(null)
    setPendingLostContract(null)
  }

  const handleFollowUpSubmit = async (values: { projectAmount: string, note: string, attachments: File[], productLine: string, esrCost: string }) => {
    if (!pendingFollowUp) return

    setFollowUpSaving(true)
    setFollowUpError(null)

    try {
      const formData = new FormData()
      formData.append('status', pendingFollowUp.newStatus)
      formData.append('project_amount', values.projectAmount)
      formData.append('note', values.note)
      formData.append('product_line', values.productLine)
      formData.append('esr_cost', values.esrCost)

      values.attachments?.forEach((file) => {
        formData.append('attachments[]', file)
      })

      const response = await fetch(route('sales.assign_follow_up', pendingFollowUp.task.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: formData
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const projectAmountValue = data.order.project_amount ?? values.projectAmount
      const normalizedProjectAmount = Number(
        typeof projectAmountValue === 'string'
          ? projectAmountValue.replace(/,/g, '')
          : projectAmountValue
      )

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingFollowUp.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingFollowUp.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingFollowUp.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingFollowUp.task.owner_ids,
        owners: data.order.owners ?? pendingFollowUp.task.owners,
        product_line: data.order.product_line ?? pendingFollowUp.task.product_line,
        esr_cost: data.order.esr_cost ?? pendingFollowUp.task.esr_cost,
        project_amount: Number.isFinite(normalizedProjectAmount) ? normalizedProjectAmount : 0
      })

      applyTaskMove(updatedTask, pendingFollowUp.newStatus)

      closeFollowUpModal(false)
    } catch (error: any) {
      console.error('follow-up submit error', error)
      setFollowUpError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setFollowUpSaving(false)
    }
  }

  const handleStandBySubmit = async (values: { note: string, productLine: string, esrCost: string }) => {
    if (!pendingStandBy) return

    setStandBySaving(true)
    setStandByError(null)

    try {
      const response = await fetch(route('sales.assign_stand_by', pendingStandBy.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note,
          product_line: values.productLine,
          esr_cost: values.esrCost
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingStandBy.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingStandBy.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingStandBy.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingStandBy.task.owner_ids,
        owners: data.order.owners ?? pendingStandBy.task.owners,
        product_line: data.order.product_line ?? pendingStandBy.task.product_line,
        esr_cost: data.order.esr_cost ?? pendingStandBy.task.esr_cost
      })

      applyTaskMove(updatedTask, pendingStandBy.newStatus)

      closeStandByModal(false)
    } catch (error: any) {
      console.error('stand-by submit error', error)
      setStandByError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setStandBySaving(false)
    }
  }

  const handleRequestRescheduleSubmit = async (values: { note: string }) => {
    if (!pendingRequestReschedule) return

    setRequestRescheduleSaving(true)
    setRequestRescheduleError(null)

    try {
      const response = await fetch(route('sales.assign_request_reschedule', pendingRequestReschedule.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note,
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingRequestReschedule.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingRequestReschedule.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingRequestReschedule.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingRequestReschedule.task.owner_ids,
        owners: data.order.owners ?? pendingRequestReschedule.task.owners
      })

      applyTaskMove(updatedTask, pendingRequestReschedule.newStatus)

      closeRequestRescheduleModal(false)
    } catch (error: any) {
      console.error('request-reschedule submit error', error)
      setRequestRescheduleError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setRequestRescheduleSaving(false)
    }
  }

  const handlePreContractSubmit = async (values: { note: string, productLine: string }) => {
    if (!pendingPreContract) return

    setPreContractSaving(true)
    setPreContractError(null)

    try {
      const response = await fetch(route('sales.assign_pre_contract', pendingPreContract.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note,
          product_line: values.productLine
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingPreContract.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingPreContract.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingPreContract.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingPreContract.task.owner_ids,
        owners: data.order.owners ?? pendingPreContract.task.owners,
        product_line: data.order.product_line ?? pendingPreContract.task.product_line
      })

      applyTaskMove(updatedTask, pendingPreContract.newStatus)

      closePreContractModal(false)
    } catch (error: any) {
      console.error('pre-contract submit error', error)
      setPreContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setPreContractSaving(false)
    }
  }

  const handleContractSignedSubmit = async (values: ContractSignedSubmitValues, confirmCustomerRole = false) => {
    if (!pendingContractSigned) return

    setContractSignedSaving(true)
    setContractSignedError(null)
    if (!confirmCustomerRole) {
      setContractSignedConfirmation(null)
      setContractSignedPendingValues(null)
    }

    try {
      const normalizedMethod = values.methodOfPayment.trim()
      const financingRequired = [PAYMENT_METHODS.FINANCED, PAYMENT_METHODS.CASH_AND_FINANCE]
      const normalizedFinancing = financingRequired.includes(normalizedMethod) ? values.typeOfFinancing.trim() : ''
      const normalizedDownPayment = values.downPayment?.trim() ?? ''
      const normalizedCity = values.city?.trim() ?? ''
      const normalizedState = values.jobState?.trim() ?? ''
      const normalizedZip = values.jobZip?.trim() ?? ''
      const normalizedClientEmailSelection = values.clientEmailSelection.trim()

      const formData = new FormData()
      formData.append('project_name', values.projectName)
      formData.append('product_line', values.productLine)
      formData.append('esr_cost', values.esrCost)
      formData.append('project_amount', values.projectAmount)
      formData.append('job_address', values.jobAddress.trim())
      formData.append('city', normalizedCity)
      formData.append('job_state', normalizedState)
      formData.append('job_zip', normalizedZip)
      formData.append('client_email_selection', normalizedClientEmailSelection)
      formData.append('name_check', values.nameCheck ? '1' : '0')
      formData.append('address_check', values.addressCheck ? '1' : '0')
      formData.append('amount_check', values.amountCheck ? '1' : '0')
      formData.append('email_check', values.emailCheck ? '1' : '0')
      formData.append('city_permits', values.cityPermits ? '1' : '0')
      formData.append('association_permits', values.associationPermits ? '1' : '0')
      formData.append('pending_financing_or_deposit', values.pendingFinancingOrDeposit ? '1' : '0')
      formData.append('pending_hoa_approval', values.pendingHoaApproval ? '1' : '0')
      formData.append('method_of_payment', normalizedMethod)
      formData.append('type_of_financing', normalizedFinancing)
      formData.append('down_payment', normalizedDownPayment)
      formData.append('payment_schedule_type', values.paymentScheduleType)
      if (values.orderCompanyContactId) {
        formData.append('order_company_contact_id', String(values.orderCompanyContactId))
      }

      if (values.paymentScheduleType === CUSTOM_SCHEDULE_TYPE) {
        values.customSchedule.forEach((item, index) => {
          formData.append(`custom_schedule[${index}][label]`, item.label)
          formData.append(`custom_schedule[${index}][amount]`, item.amount.toString())
        })
      }

      if (confirmCustomerRole) {
        formData.append('confirm_customer_role', '1')
      }

      values.attachments?.forEach((file) => {
        formData.append('attachments[]', file)
      })

      const response = await fetch(route('sales.assign_contract_signed', pendingContractSigned.task.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: formData
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 409 && payload?.requires_confirmation) {
          setContractSignedConfirmation({
            message: payload?.message ?? 'This email already belongs to another user. Convert it to customer?',
            userEmail: payload?.user_email,
            userRoles: Array.isArray(payload?.user_roles) ? payload.user_roles : []
          })
          setContractSignedPendingValues(values)
          return
        }

        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const projectAmountValue = data.order.project_amount ?? values.projectAmount
      const normalizedProjectAmount = Number(
        typeof projectAmountValue === 'string'
          ? projectAmountValue.replace(/,/g, '')
          : projectAmountValue
      )

      const downPaymentRaw = data.order.down_payment ?? normalizedDownPayment
      const normalizedDownPaymentNumber = downPaymentRaw === '' || downPaymentRaw === null || downPaymentRaw === undefined
        ? null
        : Number(
          typeof downPaymentRaw === 'string'
            ? downPaymentRaw.replace(/,/g, '')
            : downPaymentRaw
        )

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingContractSigned.task,
        title: data.order.name ?? values.projectName,
        product_line: data.order.product_line ?? pendingContractSigned.task.product_line,
        esr_cost: data.order.esr_cost ?? pendingContractSigned.task.esr_cost,
        project_amount: Number.isFinite(normalizedProjectAmount) ? normalizedProjectAmount : 0,
        down_payment: Number.isFinite(normalizedDownPaymentNumber ?? NaN) ? normalizedDownPaymentNumber : null,
        job_address: data.order.job_address ?? values.jobAddress.trim(),
        city: data.order.city ?? (normalizedCity !== '' ? normalizedCity : null),
        job_state: data.order.job_state ?? (normalizedState !== '' ? normalizedState : null),
        job_zip: data.order.job_zip ?? (normalizedZip !== '' ? normalizedZip : null),
        method_of_payment: data.order.method_of_payment ?? normalizedMethod,
        type_of_financing: data.order.type_of_financing ?? normalizedFinancing,
        schedule_appointment: data.order.schedule_appointment ?? pendingContractSigned.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingContractSigned.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingContractSigned.task.owner_ids,
        owners: data.order.owners ?? pendingContractSigned.task.owners,
        contact_email: data.order.contact_email ?? pendingContractSigned.task.contact_email,
        client_email_selection: data.order.client_email_selection ?? normalizedClientEmailSelection,
        client_email_override: data.order.client_email_override ?? pendingContractSigned.task.client_email_override,
        client_email_options: data.order.client_email_options ?? pendingContractSigned.task.client_email_options,
        do_not_send_email: data.order.do_not_send_email ?? pendingContractSigned.task.do_not_send_email,
        name_check: data.order.name_check ?? values.nameCheck,
        address_check: data.order.address_check ?? values.addressCheck,
        amount_check: data.order.amount_check ?? values.amountCheck,
        email_check: data.order.email_check ?? values.emailCheck,
        city_permits: data.order.city_permits ?? values.cityPermits,
        association_permits: data.order.association_permits ?? values.associationPermits,
        pending_financing_or_deposit: data.order.pending_financing_or_deposit ?? values.pendingFinancingOrDeposit,
        pending_hoa_approval: data.order.pending_hoa_approval ?? values.pendingHoaApproval,
        order_company_contacts: data.order.order_company_contacts ?? pendingContractSigned.task.order_company_contacts
      })

      applyTaskMove(updatedTask, pendingContractSigned.newStatus)

      closeContractSignedModal(false)
    } catch (error: any) {
      console.error('contract-signed submit error', error)
      setContractSignedError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setContractSignedSaving(false)
    }
  }

  const handleLostContractSubmit = async (values: { lossReason: string, notes: string }) => {
    if (!pendingLostContract) return

    setLostContractSaving(true)
    setLostContractError(null)

    try {
      const response = await fetch(route('sales.assign_lost_contract', pendingLostContract.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          loss_reason_frontdesk: values.lossReason,
          notes: values.notes,
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingLostContract.task
      })

      applyTaskMove(updatedTask, pendingLostContract.newStatus)

      closeLostContractModal(false)
    } catch (error: any) {
      console.error('lost-contract submit error', error)
      setLostContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setLostContractSaving(false)
    }
  }

  /* async function updateOrderStatus (orderId: number, newStatus: string) {
    const url = route('frontdesk.updateStatus', { order: orderId })

    const response = await fetch(url, {
      met aders: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        Accept: 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Error updating status')
    }
    return await response.json()
  } */

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
      leftActions={<OrderGlobalSearch origin="sales" className="w-full max-w-[420px]" />}
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
          {!IS_OWNER ? (
            <Link
              className="btn btn-primary"
              href={route('frontdesk.create')}
            >
              <span>Create Request</span>
            </Link>
          ) : null}
        </div>
      }
    >
      <Head title="Sales" />
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
            router.get(route('sales.index'), payload, { replace: true, preserveState: true })
            setIsFilterOpen(false)
          }}
            onReset={() => {
              router.get(route('sales.index'), sortQueryParams, { replace: true, preserveState: false })
              setIsFilterOpen(false)
            }}
          />
        </div>
      </div>
      <div className="w-full h-[calc(100vh-140px)]">
        <div className="overflow-x-auto overflow-y-hidden h-full">
          <div className="flex gap-4 min-w-max h-full">
            {projectList.map((project: any) => {
              const statusKey = project.id?.toString() ?? project.title ?? ''
              const totalTasks = project.total_tasks ?? project.tasks.length
              const isInfiniteStatus = INFINITE_SCROLL_STATUSES.has(project.title)
              const hasMoreTasks = isInfiniteStatus && project.tasks.length < totalTasks
              const pagination = statusPagination[statusKey]
              const totalProjectAmount = getPipelineTotalProjectAmount(project)
              return (
                <div
                  key={project.id}
                  className="panel w-96 min-w-[24rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden"
                  data-group={project.id}
                >
                  <div className="sticky top-0 z-20 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                      <h4 className="flex-1 text-xs font-semibold leading-tight text-slate-700 dark:text-white mb-0">
                        {project.title}
                      </h4>
                      <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white">
                        <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                          <span className="text-[11px]">{totalTasks}</span>
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
                        const movedTaskId = item.getAttribute('data-id')
                        const rawOldStatus = from.closest('[data-group]')?.getAttribute('data-group') ?? ''
                        const rawNewStatus = to.closest('[data-group]')?.getAttribute('data-group') ?? ''
                        const resolveStatusValue = (rawStatus: string): string => {
                          const trimmed = rawStatus.trim()
                          const pipelineMatch = projectList.find(pipeline =>
                            matchesStatus(pipeline.id?.toString?.() ?? '', trimmed)
                          )
                          return pipelineMatch ? pipelineMatch.id.toString() : trimmed
                        }
                        const oldStatus = resolveStatusValue(rawOldStatus)
                        const newStatus = resolveStatusValue(rawNewStatus)

                        if (oldStatus === newStatus) return

                        if (
                          matchesStatus(oldStatus, REQUEST_RESCHEDULE_STATUS) &&
                          !matchesStatus(newStatus, ESTIMATE_STATUS) &&
                          !matchesStatus(newStatus, LOST_CONTRACT_STATUS)
                        ) {
                          setProjectList(prev =>
                            prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                          )
                          window.alert('Orders in REQUEST RE-SCHEDULE can only move to ESTIMATE & APPT SCHEDULE or LOST CONTRACT.')
                          return
                        }

                        let movedTask!: Tasks

                        if (matchesStatus(newStatus, ESTIMATE_STATUS)) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          if (!CAN_MOVE_TO_ESTIMATE) {
                            setProjectList(prev =>
                              prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                            )
                            window.alert('Only Admin, Frontdesk Admin, or Owner Admin can move an order to ESTIMATE & APPT SCHEDULE.')
                            return
                          }

                          setProjectList(prev =>
                            prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                          )

                          setPendingMove({ task: foundTask, oldStatus, newStatus })
                          setScheduleInitialValues({
                            scheduleDate: foundTask.schedule_appointment_iso ?? '',
                            ownerIds: (foundTask.owner_ids ?? []).map(Number)
                          })
                          setScheduleModalOpen(true)
                          return
                        }

                        const isFollowUpStatus = FOLLOW_UP_STATUSES.some(status => matchesStatus(newStatus, status))
                        if (isFollowUpStatus) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setFollowUpInitialValues({
                            projectAmount: foundTask.project_amount !== undefined && foundTask.project_amount !== null
                              ? String(foundTask.project_amount)
                              : '',
                            note: ''
                          })
                          setPendingFollowUp({ task: foundTask, oldStatus, newStatus })
                          setFollowUpModalOpen(true)
                          return
                        }

                        if (matchesStatus(newStatus, REQUEST_RESCHEDULE_STATUS)) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          if (!matchesStatus(oldStatus, ESTIMATE_STATUS)) {
                            setProjectList(prev =>
                              prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                            )
                            window.alert('You can only move to REQUEST RE-SCHEDULE from ESTIMATE & APPT SCHEDULE.')
                            return
                          }

                          setProjectList(prev =>
                            prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                          )
                          setRequestRescheduleInitialNote('')
                          setPendingRequestReschedule({ task: foundTask, oldStatus, newStatus })
                          setRequestRescheduleModalOpen(true)
                          return
                        }

                        if (matchesStatus(newStatus, 'STAND BY')) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setStandByInitialNote('')
                          setPendingStandBy({ task: foundTask, oldStatus, newStatus })
                          setStandByModalOpen(true)
                          return
                        }

                        if (matchesStatus(newStatus, 'PRE CONTRACT APPOINTMENT')) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setPreContractInitialNote('')
                          setPendingPreContract({ task: foundTask, oldStatus, newStatus })
                          setPreContractModalOpen(true)
                          return
                        }

                        if (matchesStatus(newStatus, 'CONTRACT SIGNED BY CLIENT')) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          const taskCompanyContacts = Array.isArray(foundTask.order_company_contacts)
                            ? foundTask.order_company_contacts
                            : []
                          const selectedCompanyId = (
                            taskCompanyContacts.find((item) => item.is_selected)?.id ??
                            (taskCompanyContacts.length === 1 ? taskCompanyContacts[0]?.id : null)
                          )

                          setContractSignedInitialValues({
                            projectName: foundTask.title ?? '',
                            projectAmount: foundTask.project_amount !== undefined && foundTask.project_amount !== null
                              ? String(foundTask.project_amount)
                              : '',
                            downPayment: foundTask.down_payment !== undefined && foundTask.down_payment !== null
                              ? String(foundTask.down_payment)
                              : '',
                            jobAddress: foundTask.job_address ?? '',
                            city: foundTask.city ?? '',
                            jobState: foundTask.job_state ?? '',
                            jobZip: foundTask.job_zip ?? '',
                            methodOfPayment: foundTask.method_of_payment ?? '',
                            typeOfFinancing: foundTask.type_of_financing ?? '',
                            clientEmailSelection: foundTask.client_email_selection ?? PRIMARY_CLIENT_EMAIL_SELECTION,
                            orderCompanyContactId: selectedCompanyId ?? null,
                            nameCheck: foundTask.name_check ?? false,
                            addressCheck: foundTask.address_check ?? false,
                            amountCheck: foundTask.amount_check ?? false,
                            emailCheck: foundTask.email_check ?? false,
                            cityPermits: foundTask.city_permits ?? false,
                            associationPermits: foundTask.association_permits ?? false,
                            pendingFinancingOrDeposit: foundTask.pending_financing_or_deposit ?? null,
                            pendingHoaApproval: foundTask.pending_hoa_approval ?? null,
                            paymentScheduleType: '',
                            customSchedule: buildEmptyCustomSchedule()
                          })
                          setPendingContractSigned({ task: foundTask, oldStatus, newStatus })
                          setContractSignedModalOpen(true)
                          return
                        }

                        if (matchesStatus(newStatus, LOST_CONTRACT_STATUS)) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setPendingLostContract({ task: foundTask, oldStatus, newStatus })
                          setLostContractModalOpen(true)
                          return
                        }

                        setProjectList((prev) => {
                          const updatedList = prev.map((pipeline) => {
                            if (pipeline.id.toString() === oldStatus) {
                              let removed = false
                              const newTasks = pipeline.tasks.filter((t) => {
                                if (Number(t.id) === Number(movedTaskId)) {
                                  movedTask = t
                                  removed = true
                                  return false
                                }
                                return true
                              })
                              if (!removed) return pipeline
                              const nextTotal = Math.max(0, (pipeline.total_tasks ?? pipeline.tasks.length) - 1)
                              const removedAmount = movedTask ? toNumericAmount(movedTask.project_amount) : 0
                              const nextAmount = Math.max(0, getPipelineTotalProjectAmount(pipeline) - removedAmount)
                              return { ...pipeline, tasks: newTasks, total_tasks: nextTotal, total_project_amount: nextAmount }
                            }
                            return pipeline
                          })
                          return updatedList
                        })

                        if (!movedTask) return

                        if (matchesStatus(newStatus, 'QUALIFIED')) {
                          restoreTaskToStatus(movedTask, oldStatus)
                          setLostTask(movedTask)
                          setPreviousStatusId(oldStatus)
                          setShowQuantifiedModal(true)
                          return
                        }

                        if (matchesStatus(newStatus, 'LOST REQUEST')) {
                          restoreTaskToStatus(movedTask, oldStatus)
                          setLostTask(movedTask)
                          setPreviousStatusId(oldStatus)
                          setShowModal(true)
                          return
                        }

                        const stampedTask = stampTaskAsUpdated(movedTask)
                        applyTaskMove(stampedTask, newStatus)

                        // TODO: update status for other columns when backend endpoint is ready
                      }}
                      ghostClass="sortable-ghost"
                      dragClass="sortable-drag"
                      className="min-h-[1px] space-y-4 pt-2"
                    >
                      {project.tasks.map((task: any) => {
                        const followUpAlertClass = getFollowUpStaleClass(project, task)
                        const standByAlertClass = getStandByStaleClass(project, task)
                        const estimateAlertClass = getEstimateStaleClass(project, task)
                        const cardBackgroundClass = followUpAlertClass ?? standByAlertClass ?? estimateAlertClass ?? 'bg-[#f4f4f4] dark:bg-white-dark/20'
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
                        const appointmentDisplay = typeof task.schedule_appointment === 'string'
                          ? task.schedule_appointment.trim()
                          : ''
                        const isVipClient = Boolean(task.vip_clients)
                        return (
                          <div className="sortable-list " key={task.id} data-id={task.id}>
                            <div className={`shadow ${cardBackgroundClass} p-3 pb-4 rounded-md mb-5 space-y-2 cursor-move text-xs text-slate-600`}>
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
                                  {CAN_DELETE_ORDER && (
                                    <button
                                      type="button"
                                      title="Delete Order"
                                      disabled={deletingTaskId === task.id}
                                      onClick={() => { void handleDeleteOrder(task) }}
                                      className="flex items-center gap-1 hover:text-danger disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                      <DeleteIcon className="h-4 w-4" />
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
                                          <li style={{ marginBottom: 6 }}>Owners: {ownersDisplay}</li>
                                        </ul>
                                      </div>
                                    }
                                  />
                                </div>
                              </div>
                              <div className="flex gap-2 items-center flex-wrap">
                                <ProductLineBadge productLine={task.product_line} />
                                {task.tags?.length
                                  ? (
                                      task.tags.map((tag: any, i: number) => (
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
                              {task.product_line === 'MIXED' && task.esr_cost !== undefined && task.esr_cost !== null && (
                                <p className="mt-1 break-all text-xs font-semibold text-slate-700">
                                  <span className="font-medium text-slate-500">ESR Cost: </span>
                                  {formatCurrency(toNumericAmount(task.esr_cost))}
                                </p>
                              )}
                              {appointmentDisplay && (
                                <div className="mt-1 flex justify-end">
                                  <span className="inline-flex max-w-full items-center gap-1.5 text-[11px] font-semibold text-sky-700 dark:text-sky-200">
                                    <span className="text-[10px] uppercase tracking-wide text-sky-600 dark:text-sky-300">Appt</span>
                                    <span className="break-all">{appointmentDisplay}</span>
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

      <EstimateScheduleModal
        open={scheduleModalOpen && !!pendingMove}
        taskTitle={pendingMove?.task.title ?? ''}
        initialScheduleDate={scheduleInitialValues.scheduleDate}
        initialOwnerIds={scheduleInitialValues.ownerIds}
        initialOwners={pendingMove?.task.owners ?? []}
        ownerOptions={ownerOptions}
        error={scheduleError}
        saving={scheduleSaving}
        onClose={closeScheduleModal}
        onSubmit={handleScheduleSubmit}
      />

      <FollowUpModal
        open={followUpModalOpen && !!pendingFollowUp}
        taskTitle={pendingFollowUp?.task.title ?? ''}
        targetStatus={pendingFollowUp?.newStatus ?? ''}
        initialProjectAmount={followUpInitialValues.projectAmount}
        initialNote={followUpInitialValues.note}
        initialProductLine={pendingFollowUp?.task.product_line ?? ''}
        initialEsrCost={pendingFollowUp?.task.esr_cost != null ? String(pendingFollowUp.task.esr_cost) : ''}
        requireProductLine={matchesStatus(pendingFollowUp?.oldStatus ?? '', ESTIMATE_STATUS)}
        loading={followUpSaving}
        error={followUpError}
        onCancel={() => { closeFollowUpModal(true) }}
        onSubmit={handleFollowUpSubmit}
      />

      <StandByNoteModal
        open={standByModalOpen && !!pendingStandBy}
        taskTitle={pendingStandBy?.task.title ?? ''}
        initialNote={standByInitialNote}
        initialProductLine={pendingStandBy?.task.product_line ?? ''}
        initialEsrCost={pendingStandBy?.task.esr_cost != null ? String(pendingStandBy.task.esr_cost) : ''}
        requireProductLine={matchesStatus(pendingStandBy?.oldStatus ?? '', ESTIMATE_STATUS)}
        loading={standBySaving}
        error={standByError}
        onCancel={() => { closeStandByModal(true) }}
        onSubmit={handleStandBySubmit}
      />

      <RequestRescheduleModal
        open={requestRescheduleModalOpen && !!pendingRequestReschedule}
        taskTitle={pendingRequestReschedule?.task.title ?? ''}
        initialNote={requestRescheduleInitialNote}
        loading={requestRescheduleSaving}
        error={requestRescheduleError}
        onCancel={() => { closeRequestRescheduleModal(true) }}
        onSubmit={handleRequestRescheduleSubmit}
      />

      <PreContractNoteModal
        open={preContractModalOpen && !!pendingPreContract}
        taskTitle={pendingPreContract?.task.title ?? ''}
        initialNote={preContractInitialNote}
        initialProductLine={pendingPreContract?.task.product_line ?? ''}
        requireProductLine={matchesStatus(pendingPreContract?.oldStatus ?? '', ESTIMATE_STATUS)}
        loading={preContractSaving}
        error={preContractError}
        onCancel={() => { closePreContractModal(true) }}
        onSubmit={handlePreContractSubmit}
      />

      <ContractSignedModal
        open={contractSignedModalOpen && !!pendingContractSigned}
        taskTitle={pendingContractSigned?.task.title ?? ''}
        initialProjectName={contractSignedInitialValues.projectName}
        initialProductLine={pendingContractSigned?.task.product_line ?? ''}
        initialEsrCost={pendingContractSigned?.task.esr_cost != null ? String(pendingContractSigned.task.esr_cost) : ''}
        requireProductLine={matchesStatus(pendingContractSigned?.oldStatus ?? '', ESTIMATE_STATUS)}
        initialProjectAmount={contractSignedInitialValues.projectAmount}
        initialDownPayment={contractSignedInitialValues.downPayment}
        initialJobAddress={contractSignedInitialValues.jobAddress}
        initialCity={contractSignedInitialValues.city}
        initialJobState={contractSignedInitialValues.jobState}
        initialJobZip={contractSignedInitialValues.jobZip}
        initialMethodOfPayment={contractSignedInitialValues.methodOfPayment}
        initialTypeOfFinancing={contractSignedInitialValues.typeOfFinancing}
        initialClientEmailSelection={contractSignedInitialValues.clientEmailSelection}
        clientEmailOptions={pendingContractSigned?.task.client_email_options ?? []}
        initialOrderCompanyContactId={contractSignedInitialValues.orderCompanyContactId}
        orderType={pendingContractSigned?.task.order_type ?? null}
        companyOptions={(pendingContractSigned?.task.order_company_contacts ?? []).map((item) => ({
          id: item.id,
          label: [item.company_name ?? 'Company', item.client_name ? `- ${item.client_name}` : ''].join(' ').trim(),
          client_email: item.client_email ?? null,
          clientEmailOptions: item.client_email_options ?? []
        }))}
        initialNameCheck={contractSignedInitialValues.nameCheck}
        initialAddressCheck={contractSignedInitialValues.addressCheck}
        initialAmountCheck={contractSignedInitialValues.amountCheck}
        initialEmailCheck={contractSignedInitialValues.emailCheck}
        initialCityPermits={contractSignedInitialValues.cityPermits}
        initialAssociationPermits={contractSignedInitialValues.associationPermits}
        initialPendingFinancingOrDeposit={contractSignedInitialValues.pendingFinancingOrDeposit}
        initialPendingHoaApproval={contractSignedInitialValues.pendingHoaApproval}
        initialPaymentScheduleType={contractSignedInitialValues.paymentScheduleType}
        initialCustomSchedule={contractSignedInitialValues.customSchedule}
        paymentMethods={paymentMethods}
        financingOptions={financingOptions}
        paymentScheduleTemplates={payment_schedule_templates ?? {}}
        loading={contractSignedSaving}
        error={contractSignedError}
        confirmation={contractSignedConfirmation}
        onConfirmCustomerRole={() => {
          if (contractSignedPendingValues) {
            handleContractSignedSubmit(contractSignedPendingValues, true)
          }
        }}
        onDismissConfirmation={() => {
          setContractSignedConfirmation(null)
          setContractSignedPendingValues(null)
        }}
        onCancel={() => { closeContractSignedModal(true) }}
        onSubmit={handleContractSignedSubmit}
      />

      <LostContractModal
        open={lostContractModalOpen && !!pendingLostContract}
        lossReasons={lossReasonFrontdesk}
        loading={lostContractSaving}
        error={lostContractError}
        onCancel={() => { closeLostContractModal(true) }}
        onSubmit={handleLostContractSubmit}
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

      {/* <LostRequestModal
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
      /> */}
   {/* <QuantifiedModal
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
        // errors={FormikErrors<OrderFormValues>}
      /> */}
    </AuthenticatedCalendarLayout>
  )
}
