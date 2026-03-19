import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { Head, Link, router, useForm } from '@inertiajs/react'
import { type PageProps, type Pipelines, type Role, type Tasks, type User, type CompanyContact as CompanyContactType } from '@/types'
import { type ChangeEvent, type FormEvent, type KeyboardEvent, useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { createPortal } from 'react-dom'
import type { ComponentType, SVGProps } from 'react'
import { type Attachment, type OrderStatus, type Source } from '@/types/interfaces/order'
import { type FormikErrors, type FormikHelpers } from 'formik'
import TagPicker, { type TagItem } from '@/Components/TagPicker'
import UserIcon from '@/Components/Icons/UserIcon'
import { type Client } from '@/Pages/Client/ClientCommon'
import OrderEditModal from './OrderEditModal'
import { getValueIdNotNull, loadOrderFormObj, type Order, type OrderFinancialEvent, type OrderFormValues, type PaymentInstallment, type PaymentInstallmentMovement } from './OrderCommon'
import LocationIcon from '@/Components/Icons/LocationIcon'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import EmailIcon from '@/Components/Icons/EmailIcon'
import ShareIcon from '@/Components/Icons/ShareIcon'
import CrownIcon from '@/Components/Icons/CrownIcon'
import DotsIcon from '@/Components/Icons/DotsIcon'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import BookIcon from '@/Components/Icons/BookIcon'
import FolderIcon from '@/Components/Icons/FolderIcon'
import MoneyBagIcon from '@/Components/Icons/MoneyBagIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import ReorderIcon from '@/Components/Icons/ReorderIcon'
import OrderNotesForOrder from '@/Components/OrderNotesForOrder'
import EditIcon from '@/Components/Icons/EditIcon'
import MessageIcon from '@/Components/Icons/MessageIcon'
import StarIcon from '@/Components/Icons/StarIcon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import { isAccountManager, isAccounting, isAdmin, isFrontdeskAdmin, isFrontdeskEsr, isOwner, isOwnerAdmin } from '@/Utils/user'
import { PAYMENT_METHODS, SOURCES } from '@/Utils/constants'
import EstimateScheduleModal from '@/Pages/Sales/EstimateScheduleModal'
import FollowUpModal from '@/Pages/Sales/FollowUpModal'
import StandByNoteModal from '@/Pages/Sales/StandByNoteModal'
import RequestRescheduleModal from '@/Pages/Sales/RequestRescheduleModal'
import PreContractNoteModal from '@/Pages/Sales/PreContractNoteModal'
import ContractSignedModal from '@/Pages/Sales/ContractSignedModal'
import LostContractModal from '@/Pages/Sales/LostContractModal'
import QuantifiedModal from '@/Pages/Frontdesk/QuantifiedModal'
import { ContactEditModal, type ContactFormValues } from '@/Pages/Frontdesk/ContactEditModals'
import RequestEditModal, { type RequestFormValues, type RequestFormErrors } from '@/Pages/Frontdesk/RequestEditModal'
import CompanyQuickEditModal from '@/Pages/Frontdesk/CompanyQuickEditModal'

type IndexOrderProps = PageProps & {
  orderStatuses?: OrderStatus[]
  snapshots?: OrderSnapshot[]
  order: Order
  tags: TagItem[]
  usedTags: TagItem[]
  clientOrders?: ClientOrderSummary[]
  ownerOptions?: ClientOrderOwner[]
  lossReasonFrontdesk?: string[]
  sources?: string[]
  qualifiedSources?: Source[]
  order_types?: string[]
  methods_of_payment?: string[]
  type_of_financing?: string[]
  payment_schedule_templates?: PaymentScheduleTemplates
  frame_colors?: string[]
  glass_colors?: string[]
  glass_types?: string[]
  glass_coatings?: string[]
  languages?: string[]
  clients?: Client[]
  companies?: CompanyContactType[]
  sourcesClients?: string[]
  status?: string[]
}

type PaymentScheduleTemplateItem = { label: string, percentage: number }
type PaymentScheduleTemplates = Record<string, PaymentScheduleTemplateItem[]>

type TabKey = 'home' | 'profile' | 'contact' | 'sales' | 'attachments' | 'payments'

export interface ClientOrderOwner {
  id: number
  name: string
}

export interface ClientOrderSummary {
  id: number
  name: string
  order_number?: string | null
  status?: string | null
  order_type?: string | null
  owners?: ClientOrderOwner[]
}

type SnapshotActor = {
  id?: number | string | null
  name?: string | null
  email?: string | null
}

type SnapshotUser = {
  id?: number | string | null
  name?: string | null
}

type SnapshotData = {
  actor?: SnapshotActor | null
  event_type?: string | null
  notes?: Array<Record<string, any>>
  tags?: Array<Record<string, any>>
  attachments?: Array<Record<string, any>>
  client?: Record<string, any> | null
  [key: string]: any
}

type OrderSnapshot = {
  id: number | string
  status?: string | null
  created_at?: string | null
  user?: SnapshotUser | null
  snapshot_data?: SnapshotData | null
}

type TimelineItem = {
  id: string
  createdAt: Date
  timeLabel: string
  dateLabel: string
  title: string
  description?: string
  icon: ComponentType
  iconTone: 'neutral' | 'info' | 'success' | 'warning'
}

const DUPLICATE_ORDER_ERROR_KEY = 'duplicate_order_confirmation'
const DUPLICATE_ORDER_FALLBACK_MESSAGE = 'Existe una orden con este mismo nombre y el mismo cliente asociado. ¿Desea crearla de todas formas?'

type MovementFormValues = {
  useDefaultAmount: boolean
  amount: string
  note: string
}

const HIDE_DESCRIPTION_AND_JOB_STATUS = new Set([
  'NEW REQUEST',
  'REQUEST FOLLOW UP',
  'REQUEST STAND BY',
  'LOST REQUEST'
])

const FRONTDESK_STATUS_OPTIONS = [
  'NEW REQUEST',
  'REQUEST FOLLOW UP',
  'REQUEST STAND BY',
  'LOST REQUEST',
  'QUALIFIED'
] as const
const FRONTDESK_SALES_DROPDOWN_EXTRA_STATUSES = [
  'NEW REQUEST',
  'REQUEST FOLLOW UP',
  'REQUEST STAND BY'
] as const

const CUSTOM_SCHEDULE_TYPE = 'CUSTOMIZED'
const emptyMovementFormValues = (): MovementFormValues => ({
  useDefaultAmount: true,
  amount: '',
  note: ''
})

const buildCustomSchedule = (items?: Array<{ label?: string | null, amount?: number | string | null }>) => {
  const normalized = Array.isArray(items)
    ? items.map((item) => ({
      label: item?.label ?? '',
      amount: item?.amount != null ? String(item.amount) : ''
    }))
    : []

  while (normalized.length < 6) {
    normalized.push({ label: '', amount: '' })
  }

  return normalized.slice(0, 6)
}

const SALES_STATUS_OPTIONS = [
  'PENDING COMMERCIAL',
  'PENDING RESIDENTIAL',
  'REQUEST RE-SCHEDULE',
  'ESTIMATE & APPT SCHEDULE',
  'FOLLOW UP',
  'FOLLOW UP PROJECTS',
  'STAND BY',
  'PRE CONTRACT APPOINTMENT',
  'CONTRACT SIGNED BY CLIENT',
  'LOST CONTRACT'
] as const

const ORDER_PROCESSING_STATUS_OPTIONS = [
  'RECTIFICATION OF MEASURES AND HOA',
  'ORDER MATERIALS AND FILE ORGANIZATION',
  'FILE REVIEW',
  'CLOSED WON'
] as const

const ORDER_STORAGE_STATUS_OPTIONS = [
  'ACCOUNT RECEIPT',
  'REVIEW',
  'PLANNED',
  'MATERIALS RECEIVED',
  'CONFIRMED',
  'EXECUTION',
  'ON HOLD',
  'SUPERVISION',
  'INSPECTION',
  'FINISH',
  'FINAL INSPECTION',
  'PENDING COLLECT',
  'COMPLETE'
] as const

const ORDER_STORAGE_TRANSITION_STATUSES = ['ACCOUNT RECEIPT', 'REVIEW'] as const

const REQUEST_RESCHEDULE_STATUS = 'REQUEST RE-SCHEDULE'
const ESTIMATE_STATUS = 'ESTIMATE & APPT SCHEDULE'
const FOLLOW_UP_STATUSES = ['FOLLOW UP', 'FOLLOW UP PROJECTS'] as const

const pad = (value: number): string => value.toString().padStart(2, '0')

const formatDateForInput = (date: Date): string => {
  const year = date.getFullYear()
  const month = pad(date.getMonth() + 1)
  const day = pad(date.getDate())
  const hours = pad(date.getHours())
  const minutes = pad(date.getMinutes())
  return `${year}-${month}-${day} ${hours}:${minutes}`
}

const round2 = (value: number): number => Math.round((value + Number.EPSILON) * 100) / 100

const normalizeScheduleValue = (value?: string | null): string => {
  if (!value) return ''
  const normalized = value.includes('T') ? value : value.replace(' ', 'T')
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? '' : formatDateForInput(date)
}

const formatScheduleDisplay = (value?: string | null): string | null => {
  if (!value) return null
  const normalized = value.includes('T') ? value : value.replace(' ', 'T')
  const date = new Date(normalized)
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString()
}

const formatDateOnly = (value?: string | Date | null): string | null => {
  if (!value) return null
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString()
}

const toScheduleString = (value?: Date | string | null): string | null => {
  if (!value) return null
  if (value instanceof Date) {
    const iso = value.toISOString()
    return Number.isNaN(Date.parse(iso)) ? null : iso
  }
  const str = String(value).trim()
  if (!str) return null
  const parsed = new Date(str)
  return Number.isNaN(parsed.getTime()) ? str : parsed.toISOString()
}

const normalizeStatusValue = (value: string | number): string => String(value).replace(/\s+/g, ' ').trim().toUpperCase()

const matchesStatus = (value: string | number, target: string | number): boolean =>
  normalizeStatusValue(value) === normalizeStatusValue(target)

const isFrontdeskStatus = (value: string): boolean =>
  FRONTDESK_STATUS_OPTIONS.some(status => matchesStatus(status, value))

const isSalesStatus = (value: string): boolean =>
  SALES_STATUS_OPTIONS.some(status => matchesStatus(status, value))

const isOrderProcessingStatus = (value: string): boolean =>
  ORDER_PROCESSING_STATUS_OPTIONS.some(status => matchesStatus(status, value))

const isOrderStorageStatus = (value: string): boolean =>
  ORDER_STORAGE_STATUS_OPTIONS.some(status => matchesStatus(status, value))

const isOrderStorageTransitionStatus = (value: string): boolean =>
  ORDER_STORAGE_TRANSITION_STATUSES.some(status => matchesStatus(status, value))

const mergeOptionWithCurrent = (options: readonly string[] | null | undefined, current?: string | null): string[] => {
  const base = Array.isArray(options) ? Array.from(options) : []
  const merged = typeof current === 'string' && current.length > 0
    ? [current, ...base]
    : base
  return merged.filter((value, index) => merged.indexOf(value) === index)
}

const normalizeBoolean = (value: any): boolean => {
  if (typeof value === 'boolean') return value
  if (typeof value === 'number') return value === 1
  if (typeof value === 'string') return value === '1' || value.toLowerCase() === 'true'
  return false
}

const formatTimelineDate = (value: Date): string =>
  value
    .toLocaleDateString(undefined, { month: 'short', day: '2-digit', year: 'numeric' })
    .toUpperCase()

const formatTimelineTime = (value: Date): string =>
  value.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }).toUpperCase()

const normalizeSnapshotArray = (value: any): Array<Record<string, any>> =>
  Array.isArray(value) ? value : []

const normalizeSnapshotData = (value: any): SnapshotData => {
  if (!value) return {}
  if (typeof value === 'string') {
    try {
      return JSON.parse(value) as SnapshotData
    } catch {
      return {}
    }
  }
  return value as SnapshotData
}

const snapshotKeyOf = (item: Record<string, any>): string | null => {
  if (item?.id !== undefined && item?.id !== null) return String(item.id)
  if (item?.name) return String(item.name)
  if (item?.filename) return String(item.filename)
  return null
}

const ownerDisplayName = (owner: Record<string, any>): string => {
  if (owner?.name) return String(owner.name)
  if (owner?.email) return String(owner.email)
  return 'Unknown'
}

const diffAddedItems = (current: Array<Record<string, any>>, previous: Array<Record<string, any>>): Array<Record<string, any>> => {
  const previousKeys = new Set(previous.map(snapshotKeyOf).filter(Boolean) as string[])
  return current.filter((item) => {
    const key = snapshotKeyOf(item)
    return key !== null && !previousKeys.has(key)
  })
}

const FIELD_LABELS: Record<string, string> = {
  status: 'Status',
  schedule_appointment: 'Schedule appointment',
  order_type: 'Order type',
  method_of_payment: 'Method of payment',
  project_amount: 'Project amount',
  down_payment: 'Cash amount',
  eta_date: 'ETA date'
}

const CLIENT_FIELD_LABELS: Record<string, string> = {
  source: 'Source',
  name: 'Client name',
  email: 'Email',
  phone: 'Phone',
  secondary_email: 'Secondary email',
  other_phone: 'Other phone',
  vip_clients: 'VIP',
  vip_notes: 'VIP notes'
}

const formatFieldLabel = (key: string): string => FIELD_LABELS[key] ?? key.replace(/_/g, ' ')

const trimValue = (value: any, max = 60): string => {
  if (value === null || value === undefined) return '—'
  const stringValue = String(value)
  if (stringValue.length <= max) return stringValue
  return `${stringValue.slice(0, max).trim()}…`
}

const formatFinancialAmount = (value: any): string | null => {
  const numeric = Number(value)
  if (!Number.isFinite(numeric)) return null
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(numeric)
}

const financialEventDetailText = (event: OrderFinancialEvent): string | undefined => {
  const details = event.details ?? {}
  const parts: string[] = []

  const beforeAmount = formatFinancialAmount((details as any).before_amount)
  const afterAmount = formatFinancialAmount((details as any).after_amount)
  if (beforeAmount || afterAmount) {
    parts.push(`Amount: ${beforeAmount ?? '—'} → ${afterAmount ?? '—'}`)
  }

  const beforeDueDate = (details as any).before_due_date
  const afterDueDate = (details as any).after_due_date
  if (beforeDueDate || afterDueDate) {
    parts.push(`Due date: ${beforeDueDate ?? '—'} → ${afterDueDate ?? '—'}`)
  }

  const beforeStatus = (details as any).before_status
  const afterStatus = (details as any).after_status
  if (beforeStatus || afterStatus) {
    parts.push(`Status: ${beforeStatus ?? '—'} → ${afterStatus ?? '—'}`)
  }

  if ((details as any).amount != null) {
    const amount = formatFinancialAmount((details as any).amount)
    if (amount) parts.push(`Amount: ${amount}`)
  }

  if (typeof (details as any).note === 'string' && (details as any).note.trim() !== '') {
    parts.push(`Note: ${(details as any).note}`)
  }

  if (parts.length === 0) {
    return undefined
  }

  return parts.join(' • ')
}

const isPrimitive = (value: any): boolean =>
  value === null || ['string', 'number', 'boolean'].includes(typeof value)

const diffPrimitiveFields = (current: SnapshotData, previous: SnapshotData | null) => {
  if (!previous) return []
  const ignored = new Set([
    'notes',
    'tags',
    'attachments',
    'owners',
    'status',
    'sale_form',
    'saleForm',
    'client',
    'user',
    'owners',
    'orderStatus',
    'order_status',
    'installation_teams',
    'order_products',
    'orderProducts',
    'orderColors',
    'actor',
    'event_type',
    'created_at',
    'updated_at',
    'deleted_at'
  ])

  return Object.keys(current)
    .filter((key) => !ignored.has(key))
    .map((key) => ({
      key,
      label: formatFieldLabel(key),
      from: previous[key],
      to: current[key]
    }))
    .filter(({ from, to }) => isPrimitive(from) && isPrimitive(to) && from !== to)
}

const diffClientFields = (current: SnapshotData, previous: SnapshotData | null) => {
  if (!previous) return []
  const currentClient = current.client ?? null
  const previousClient = previous.client ?? null
  if (!currentClient || !previousClient) return []
  return Object.entries(CLIENT_FIELD_LABELS)
    .map(([key, label]) => ({
      key: `client.${key}`,
      label,
      from: previousClient?.[key],
      to: currentClient?.[key]
    }))
    .filter(({ from, to }) => isPrimitive(from) && isPrimitive(to) && from !== to)
}

export default function ShowStatusOrder ({
  auth,
  orderStatuses = [],
  snapshots = [],
  tags = [],
  order: initialOrder,
  usedTags = [],
  clientOrders = [],
  ownerOptions = [],
  lossReasonFrontdesk = [],
  sources = [],
  qualifiedSources = [],
  order_types = [],
  methods_of_payment = [],
  type_of_financing = [],
  payment_schedule_templates = {},
  frame_colors = [],
  glass_colors = [],
  glass_types = [],
  glass_coatings = [],
  languages = [],
  clients = [],
  companies = [],
  sourcesClients = [],
  status: statusOptions = []
}: IndexOrderProps) {
  const [order, setOrder] = useState(initialOrder)
  const [orderEditModalOpen, setOrderEditModalOpen] = useState(false)
  const [orderEditError, setOrderEditError] = useState<string | null>(null)
  const [orderFormInitialValues, setOrderFormInitialValues] = useState<OrderFormValues>(() => loadOrderFormObj(initialOrder))
  const scheduleAppointmentIso = order.schedule_appointment_iso ?? toScheduleString(order.schedule_appointment ?? null)
  const safeOrderStatuses = Array.isArray(orderStatuses) ? orderStatuses : []
  const safeSnapshots = Array.isArray(snapshots) ? snapshots : []
  const safeFinancialEvents = Array.isArray(order.financial_events) ? order.financial_events : []
  const safeTags = Array.isArray(tags) ? tags : []
  const safeUsedTags = Array.isArray(usedTags) ? usedTags : []
  const relatedClientOrders = Array.isArray(clientOrders) ? clientOrders : []
  const associatedOrdersCount = relatedClientOrders.length
  const safeOwnerOptions = Array.isArray(ownerOptions) ? ownerOptions : []
  const modalOwnerOptions = safeOwnerOptions as unknown as User[]
  const safeClients = Array.isArray(clients) ? clients : []
  const [clientsList, setClientsList] = useState<Client[]>(safeClients)
  const safeCompanies = Array.isArray(companies) ? companies : []
  const safeSourcesClients = Array.isArray(sourcesClients) ? sourcesClients : []
  const safeQualifiedSources = Array.isArray(qualifiedSources) ? qualifiedSources : []
  const safeStatusOptions = Array.isArray(statusOptions) ? statusOptions : []
  const toNull = (value: any) => (value === 0 || value === '0' || value === '' || value === undefined || value === null ? null : value)
  const [tab, setTab] = useState<TabKey>('home')
  const authUserId = auth?.user?.id ?? null
  const roleNames = Array.isArray(auth?.user?.roles)
    ? auth.user.roles.map((role: Role) => role.name)
    : []
  const canViewPipeline = isAdmin(roleNames) || isAccountManager(roleNames) || isOwner(roleNames) || isOwnerAdmin(roleNames) || isFrontdeskAdmin(roleNames) || isAccounting(roleNames)
  const canEditPipeline = isAdmin(roleNames) || isAccountManager(roleNames) || isOwner(roleNames) || isOwnerAdmin(roleNames) || isFrontdeskAdmin(roleNames) || isAccounting(roleNames)
  const canEditPaymentInformationInModal = isAdmin(roleNames) || isAccountManager(roleNames) || isAccounting(roleNames) || isOwnerAdmin(roleNames)
  const hasReachedContractSigned = Boolean(order.has_contract_signed)
  const hasAssignedPaymentMethod = String(order.method_of_payment ?? '').trim() !== ''
  const hasAssignedPaymentSchedule = String(order.payment_schedule?.schedule_type ?? '').trim() !== ''
  const hasAssignedPaymentConfiguration = hasAssignedPaymentMethod && hasAssignedPaymentSchedule
  const canManagePaymentInformationForOrder = canEditPaymentInformationInModal && hasReachedContractSigned
  const showProjectAmountOnlyBeforeContract = !hasReachedContractSigned
  const isProjectAmountReadOnlyBeforeContract = showProjectAmountOnlyBeforeContract && isOwner(roleNames) && hasAssignedPaymentConfiguration
  const canSubmitProjectAmountBeforeContract = showProjectAmountOnlyBeforeContract && !isProjectAmountReadOnlyBeforeContract
  const isFrontdeskEsrRole = isFrontdeskEsr(roleNames)

  const [scheduleModalOpen, setScheduleModalOpen] = useState(false)
  const [scheduleInitialValues, setScheduleInitialValues] = useState<{ scheduleDate: string, ownerIds: number[] }>({
    scheduleDate: scheduleAppointmentIso ? normalizeScheduleValue(scheduleAppointmentIso) : '',
    ownerIds: Array.isArray(order.owners) ? order.owners.map(owner => Number(owner.id)).filter((id) => Number.isFinite(id)) : []
  })
  const [scheduleSaving, setScheduleSaving] = useState(false)
  const [scheduleError, setScheduleError] = useState<string | null>(null)

  const [followUpModalOpen, setFollowUpModalOpen] = useState(false)
  const [followUpInitialValues, setFollowUpInitialValues] = useState<{ projectAmount: string, note: string, targetStatus: string }>({ projectAmount: '', note: '', targetStatus: '' })
  const [followUpSaving, setFollowUpSaving] = useState(false)
  const [followUpError, setFollowUpError] = useState<string | null>(null)

  const [standByModalOpen, setStandByModalOpen] = useState(false)
  const [standBySaving, setStandBySaving] = useState(false)
  const [standByError, setStandByError] = useState<string | null>(null)
  const [standByInitialNote, setStandByInitialNote] = useState('')

  const [frontdeskStandByModalOpen, setFrontdeskStandByModalOpen] = useState(false)
  const [frontdeskStandByNote, setFrontdeskStandByNote] = useState('')
  const [frontdeskStandBySaving, setFrontdeskStandBySaving] = useState(false)
  const [frontdeskStandByError, setFrontdeskStandByError] = useState<string | null>(null)

  const [frontdeskLostModalOpen, setFrontdeskLostModalOpen] = useState(false)
  const [frontdeskLostReason, setFrontdeskLostReason] = useState('')
  const [frontdeskLostNotes, setFrontdeskLostNotes] = useState('')
  const [frontdeskLostSaving, setFrontdeskLostSaving] = useState(false)
  const [frontdeskLostError, setFrontdeskLostError] = useState<string | null>(null)

  const [frontdeskQuantifiedModalOpen, setFrontdeskQuantifiedModalOpen] = useState(false)

  const [requestRescheduleModalOpen, setRequestRescheduleModalOpen] = useState(false)
  const [requestRescheduleSaving, setRequestRescheduleSaving] = useState(false)
  const [requestRescheduleError, setRequestRescheduleError] = useState<string | null>(null)
  const [requestRescheduleInitialNote, setRequestRescheduleInitialNote] = useState('')

  const [preContractModalOpen, setPreContractModalOpen] = useState(false)
  const [preContractSaving, setPreContractSaving] = useState(false)
  const [preContractError, setPreContractError] = useState<string | null>(null)
  const [preContractInitialNote, setPreContractInitialNote] = useState('')

  const [contractSignedModalOpen, setContractSignedModalOpen] = useState(false)
  const [contractSignedSaving, setContractSignedSaving] = useState(false)
  const [contractSignedError, setContractSignedError] = useState<string | null>(null)
  const initialScheduleType = initialOrder.payment_schedule?.schedule_type ?? ''
  const initialCustomSchedule = initialScheduleType === CUSTOM_SCHEDULE_TYPE
    ? buildCustomSchedule(initialOrder.payment_schedule?.installments)
    : buildCustomSchedule()
  const [contractSignedInitialValues, setContractSignedInitialValues] = useState({
    projectName: order.name ?? '',
    projectAmount: order.project_amount ? String(order.project_amount) : '',
    downPayment: order.down_payment ? String(order.down_payment) : '',
    jobAddress: order.job_address ?? '',
    city: order.city ?? '',
    jobState: order.job_state ?? '',
    jobZip: order.job_zip ?? '',
    methodOfPayment: order.method_of_payment ?? '',
    typeOfFinancing: order.type_of_financing ?? '',
    contactEmail: order.client?.email ?? '',
    orderCompanyContactId: null as number | null,
    nameCheck: Boolean(order.name_check),
    addressCheck: Boolean(order.address_check),
    amountCheck: Boolean(order.amount_check),
    emailCheck: Boolean(order.email_check),
    cityPermits: Boolean(order.city_permits),
    associationPermits: Boolean(order.association_permits),
    paymentScheduleType: initialScheduleType,
    customSchedule: initialCustomSchedule
  })
  const [paymentEdits, setPaymentEdits] = useState<Record<number, { dueDate: string }>>({})
  const [paymentSavingId, setPaymentSavingId] = useState<number | null>(null)
  const [paymentError, setPaymentError] = useState<string | null>(null)
  const [movementDrafts, setMovementDrafts] = useState<Record<number, MovementFormValues>>({})
  const [movementEdits, setMovementEdits] = useState<Record<number, MovementFormValues>>({})
  const [movementSavingKey, setMovementSavingKey] = useState<string | null>(null)
  const [changeOrderStatus, setChangeOrderStatus] = useState<string>(initialOrder.change_order_payment?.status ?? 'PENDING')
  const [changeOrderSaving, setChangeOrderSaving] = useState(false)
  const [changeOrderError, setChangeOrderError] = useState<string | null>(null)

  const [lostContractModalOpen, setLostContractModalOpen] = useState(false)
  const [lostContractSaving, setLostContractSaving] = useState(false)
  const [lostContractError, setLostContractError] = useState<string | null>(null)

  const [pendingMove, setPendingMove] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingFollowUp, setPendingFollowUp] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingStandBy, setPendingStandBy] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingRequestReschedule, setPendingRequestReschedule] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingPreContract, setPendingPreContract] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingContractSigned, setPendingContractSigned] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingLostContract, setPendingLostContract] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [pendingOrderProcessingMove, setPendingOrderProcessingMove] = useState<{ oldStatus: string, newStatus: string } | null>(null)
  const [statusChangeSaving, setStatusChangeSaving] = useState(false)
  const [statusChangeError, setStatusChangeError] = useState<string | null>(null)
  const [orderProcessingModalOpen, setOrderProcessingModalOpen] = useState(false)
  const [orderProcessingNote, setOrderProcessingNote] = useState('')
  const [orderProcessingInvoiceNumber, setOrderProcessingInvoiceNumber] = useState('')
  const [orderProcessingAttachments, setOrderProcessingAttachments] = useState<File[]>([])
  const [orderProcessingError, setOrderProcessingError] = useState<string | null>(null)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
  const [companyEditModalOpen, setCompanyEditModalOpen] = useState(false)
  const [companyEditTarget, setCompanyEditTarget] = useState<CompanyContactType | null>(null)
  const [contactModalOpen, setContactModalOpen] = useState(false)
  const [contactModalTargetClientId, setContactModalTargetClientId] = useState<number | null>(null)
  const [requestModalOpen, setRequestModalOpen] = useState(false)
  const clientIsContact = normalizeBoolean(order.client?.is_contact)
  const [contactFormValues, setContactFormValues] = useState<ContactFormValues>({
    client_name: initialOrder.client?.name ?? '',
    email: initialOrder.client?.email ?? '',
    secondary_email: initialOrder.client?.secondary_email ?? '',
    phone: initialOrder.client?.phone ?? '',
    other_phone: initialOrder.client?.other_phone ?? '',
    source: initialOrder.client?.source ?? '',
    vip_clients: normalizeBoolean(initialOrder.client?.vip_clients),
    vip_notes: initialOrder.client?.vip_notes ?? ''
  })
  const [requestFormValues, setRequestFormValues] = useState<RequestFormValues>({
    client_name: initialOrder.client?.name ?? initialOrder.name ?? '',
    phone: initialOrder.client?.phone ?? '',
    status: initialOrder.status ?? (FRONTDESK_STATUS_OPTIONS[0] ?? ''),
    source: initialOrder.client?.source ?? (sources?.[0] ?? ''),
    notes: initialOrder.notes ?? ''
  })
  const [contactFormErrors, setContactFormErrors] = useState<Record<string, string[]>>({})
  const [requestFormErrors, setRequestFormErrors] = useState<RequestFormErrors>({})
  const [contactSubmitError, setContactSubmitError] = useState<string | null>(null)
  const [requestSubmitError, setRequestSubmitError] = useState<string | null>(null)
  const [contactSaving, setContactSaving] = useState(false)
  const [requestSaving, setRequestSaving] = useState(false)
  const canEditContact = clientIsContact

  useEffect(() => {
    setChangeOrderStatus(order.change_order_payment?.status ?? 'PENDING')
  }, [order.change_order_payment?.id, order.change_order_payment?.status])

  const contactSourceOptions = useMemo(
    () => mergeOptionWithCurrent(sources, contactFormValues.source),
    [sources, contactFormValues.source]
  )
  const requestSourceOptions = useMemo(
    () => mergeOptionWithCurrent(sources, requestFormValues.source),
    [sources, requestFormValues.source]
  )
  const requestStatusOptions = useMemo(
    () => mergeOptionWithCurrent(FRONTDESK_STATUS_OPTIONS, requestFormValues.status),
    [requestFormValues.status]
  )

  type DetailIcon = ComponentType<SVGProps<SVGSVGElement>>

  const openContactModal = () => {
    if (!canEditContact) return
    setContactFormValues({
      client_name: order.client?.name ?? '',
      email: order.client?.email ?? '',
      secondary_email: order.client?.secondary_email ?? '',
      phone: order.client?.phone ?? '',
      other_phone: order.client?.other_phone ?? '',
      source: order.client?.source ?? '',
      vip_clients: normalizeBoolean(order.client?.vip_clients),
      vip_notes: order.client?.vip_notes ?? ''
    })
    setContactFormErrors({})
    setContactSubmitError(null)
    setContactModalOpen(true)
  }

  const openCommercialContactModal = (client?: Client | null) => {
    if (!client) return
    setContactFormValues({
      client_name: client.name ?? '',
      email: client.email ?? '',
      secondary_email: client.secondary_email ?? '',
      phone: client.phone ?? '',
      other_phone: client.other_phone ?? '',
      source: client.source ?? '',
      vip_clients: normalizeBoolean(client.vip_clients),
      vip_notes: client.vip_notes ?? ''
    })
    setContactFormErrors({})
    setContactSubmitError(null)
    setContactModalTargetClientId(client.id)
    setContactModalOpen(true)
  }

  const openCompanyEditModal = (company?: CompanyContactType | null) => {
    if (!company) return
    setCompanyEditTarget(company)
    setCompanyEditModalOpen(true)
  }

  const handleCompanyUpdated = (company: CompanyContactType) => {
    setOrder(prev => {
      const next = { ...prev } as any
      if (Array.isArray(next.order_company_contacts)) {
        next.order_company_contacts = next.order_company_contacts.map((item: any) => {
          const itemCompanyId = item?.company_contact?.id ?? item?.companyContact?.id ?? item?.company_contact_id
          if (Number(itemCompanyId) !== Number(company.id)) return item
          const updatedCompany = { ...(item.company_contact ?? item.companyContact ?? {}), ...company }
          return { ...item, company_contact: updatedCompany, companyContact: updatedCompany }
        })
      }
      if (Array.isArray(next.orderCompanyContacts)) {
        next.orderCompanyContacts = next.orderCompanyContacts.map((item: any) => {
          const itemCompanyId = item?.company_contact?.id ?? item?.companyContact?.id ?? item?.company_contact_id
          if (Number(itemCompanyId) !== Number(company.id)) return item
          const updatedCompany = { ...(item.company_contact ?? item.companyContact ?? {}), ...company }
          return { ...item, company_contact: updatedCompany, companyContact: updatedCompany }
        })
      }
      return next
    })
  }

  const openRequestModal = () => {
    setRequestFormValues({
      client_name: order.client?.name ?? order.name ?? '',
      phone: order.client?.phone ?? '',
      status: order.status ?? (FRONTDESK_STATUS_OPTIONS[0] ?? ''),
      source: order.client?.source ?? (sources[0] ?? ''),
      notes: order.notes ?? ''
    })
    setRequestFormErrors({})
    setRequestSubmitError(null)
    setRequestModalOpen(true)
  }

  const handleRequestFieldChange = (field: keyof RequestFormValues, value: RequestFormValues[keyof RequestFormValues]) => {
    setRequestFormValues(prev => ({
      ...prev,
      [field]: value
    }) as RequestFormValues)
  }

  const handleContactSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (contactSaving) return
    setContactSubmitError(null)
    setContactFormErrors({})
    setContactSaving(true)
    try {
      const response = await fetch(route('frontdesk.orders.update-contact', { order: order.id }), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          mode: 'contact',
          ...contactFormValues,
          client_id: contactModalTargetClientId ?? undefined
        })
      })
      const responseData = await response.json().catch(() => null)
      if (!response.ok) {
        if (responseData?.errors) {
          setContactFormErrors(responseData.errors)
        }
        setContactSubmitError(responseData?.message ?? 'Unable to update contact information.')
        return
      }
      if (responseData?.order) {
        setOrder(prev => ({
          ...prev,
          ...responseData.order
        }))
      }
      setContactModalOpen(false)
      setContactModalTargetClientId(null)
    } catch (error) {
      console.error('contact update error', error)
      setContactSubmitError('Unable to update contact information.')
    } finally {
      setContactSaving(false)
    }
  }

  const handleRequestSubmit = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    if (requestSaving) return
    setRequestSubmitError(null)
    setRequestFormErrors({})
    setRequestSaving(true)
    try {
      const response = await fetch(route('frontdesk.orders.update-contact', { order: order.id }), {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({
          mode: 'frontdesk',
          ...requestFormValues
        })
      })
      const responseData = await response.json().catch(() => null)
      if (!response.ok) {
        if (responseData?.errors) {
          setRequestFormErrors(responseData.errors as RequestFormErrors)
        }
        setRequestSubmitError(responseData?.message ?? 'Unable to update request information.')
        return
      }
      if (responseData?.order) {
        setOrder(prev => ({
          ...prev,
          ...responseData.order
        }))
      }
      setRequestModalOpen(false)
    } catch (error) {
      console.error('request update error', error)
      setRequestSubmitError('Unable to update request information.')
    } finally {
      setRequestSaving(false)
    }
  }

  const openOrderEditModal = () => {
    setOrderEditError(null)
    setOrderEditModalOpen(true)
  }

  const handleOrderEditSubmit = async (values: OrderFormValues, helpers: FormikHelpers<OrderFormValues>) => {
    setOrderEditError(null)
    const currentStatusValue = typeof order.status === 'string'
      ? order.status
      : getValueIdNotNull(order.status)
    try {
      const normalizedStatus = typeof values.status === 'string'
        ? values.status
        : getValueIdNotNull(values.status)

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

      if (canManagePaymentInformationForOrder) {
        const isCash = values.method_of_payment === PAYMENT_METHODS.CASH
        const isCashAndFinanced = values.method_of_payment === PAYMENT_METHODS.CASH_AND_FINANCE
        const requiresSchedule = isCash || isCashAndFinanced
        const resolvedPaymentScheduleType = requiresSchedule
          ? (isCashAndFinanced ? CUSTOM_SCHEDULE_TYPE : (values.payment_schedule_type || null))
          : null

        payload.method_of_payment = values.method_of_payment || null
        payload.type_of_financing = (values.method_of_payment === PAYMENT_METHODS.FINANCED || isCashAndFinanced)
          ? (values.type_of_financing || null)
          : null
        payload.down_payment = isCashAndFinanced
          ? (values.down_payment ?? null)
          : null
        payload.payment_schedule_type = resolvedPaymentScheduleType
        payload.custom_schedule = requiresSchedule && resolvedPaymentScheduleType === CUSTOM_SCHEDULE_TYPE
          ? (values.custom_schedule ?? [])
            .map((item: { label?: string, amount?: string | number }) => ({
              label: String(item.label ?? '').trim(),
              amount: Number(String(item.amount ?? '').replace(/,/g, ''))
            }))
            .filter((item: { label: string, amount: number }) => item.label !== '' && Number.isFinite(item.amount))
          : []
      } else {
        if (!canSubmitProjectAmountBeforeContract) {
          delete payload.project_amount
        }
        delete payload.change_order_enabled
        delete payload.change_order_amount
        delete payload.change_order_note
        delete payload.method_of_payment
        delete payload.type_of_financing
        delete payload.down_payment
        delete payload.payment_schedule_type
        delete payload.custom_schedule
      }

      if (normalizedStatus && (!currentStatusValue || !matchesStatus(normalizedStatus, currentStatusValue))) {
        payload.status = normalizedStatus
      }

      const submitOrderEdit = async (forceDuplicate = false): Promise<any | null> => {
        const response = await fetch(route('frontdesk.orders.update-qualified', { order: order.id }), {
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

        if (response.status === 422) {
          const data = await response.json().catch(() => null)
          const duplicateMessageRaw = data?.errors?.[DUPLICATE_ORDER_ERROR_KEY]
          const duplicateMessage = Array.isArray(duplicateMessageRaw)
            ? duplicateMessageRaw[0]
            : duplicateMessageRaw

          if (!forceDuplicate && typeof duplicateMessage === 'string' && duplicateMessage.trim() !== '') {
            const shouldContinue = window.confirm(duplicateMessage || DUPLICATE_ORDER_FALLBACK_MESSAGE)
            if (shouldContinue) {
              return await submitOrderEdit(true)
            }
            return null
          }

          const formattedErrors: FormikErrors<OrderFormValues> = {}
          Object.entries(data?.errors ?? {}).forEach(([field, messages]) => {
            if (Array.isArray(messages) && messages.length > 0) {
              formattedErrors[field as keyof OrderFormValues] = messages[0]
            } else if (typeof messages === 'string') {
              formattedErrors[field as keyof OrderFormValues] = messages
            }
          })
          helpers.setErrors(formattedErrors)
          setOrderEditError(data?.message ?? 'Please fix the highlighted fields.')
          return null
        }

        if (!response.ok) {
          throw new Error('Failed to update order. Please try again later.')
        }

        return await response.json().catch(() => null)
      }

      const data = await submitOrderEdit()
      if (!data) {
        return
      }
      const updatedOrder: Order | undefined = data?.order
      if (!updatedOrder) {
        throw new Error('Unexpected server response.')
      }

      setOrder(prev => ({
        ...prev,
        ...updatedOrder
      }))
      if (Array.isArray((updatedOrder as any).order_company_contacts)) {
        setClientsList(prev => {
          const next = [...prev]
          for (const item of (updatedOrder as any).order_company_contacts) {
            const clientId = item?.client?.id ?? item?.client_id
            if (!clientId) continue
            const companyId = item?.company_contact_id ?? item?.company_contact?.id ?? item?.companyContact?.id ?? null
            const idx = next.findIndex(c => Number(c.id) === Number(clientId))
            if (idx >= 0) {
              next[idx] = { ...next[idx], company_contact_id: companyId ?? next[idx].company_contact_id }
            }
          }
          return next
        })
      }
      setOrderFormInitialValues(loadOrderFormObj(updatedOrder))
      setOrderEditModalOpen(false)

      setContactFormValues({
        client_name: updatedOrder.client?.name ?? '',
        email: updatedOrder.client?.email ?? '',
        secondary_email: updatedOrder.client?.secondary_email ?? '',
        phone: updatedOrder.client?.phone ?? '',
        other_phone: updatedOrder.client?.other_phone ?? '',
        source: updatedOrder.client?.source ?? '',
        vip_clients: normalizeBoolean(updatedOrder.client?.vip_clients),
        vip_notes: updatedOrder.client?.vip_notes ?? ''
      })

      setRequestFormValues(prev => ({
        ...prev,
        client_name: updatedOrder.client?.name ?? updatedOrder.name ?? prev.client_name,
        phone: updatedOrder.client?.phone ?? prev.phone,
        status: updatedOrder.status ?? prev.status,
        source: updatedOrder.client?.source ?? prev.source,
        notes: updatedOrder.notes ?? prev.notes
      }))
    } catch (error) {
      setOrderEditError(error instanceof Error ? error.message : 'Unable to update order.')
    } finally {
      helpers.setSubmitting(false)
    }
  }

  const contactDetails: Array<{ label: string, value?: string | null, fallback: string, Icon: DetailIcon }> = [
    { label: 'Contact Name', value: order.client?.name, fallback: 'No contact assigned', Icon: UserIcon },
    { label: 'Phone', value: order.client?.phone, fallback: 'No phone available', Icon: PhoneIcon },
    { label: 'Email', value: order.client?.email, fallback: 'No email available', Icon: EmailIcon }
  ]
  const normalizeDetailValue = (value?: string | null) => {
    if (typeof value !== 'string') return null
    const normalized = value.trim()
    return normalized === '' ? null : normalized
  }

  const jobLocation = [order.job_address, order.city, order.job_state, order.job_zip].filter(Boolean).join(', ')
  const sourceName = order.client?.source
  const isReferralSource = sourceName != null && [
    SOURCES.EXTERNAL_REFERAL,
    SOURCES.INTERNAL_REFERAL,
    SOURCES.ESW_REFER,
    SOURCES.ESR_REFER
  ].includes(sourceName)
  const referralRecord = order.client?.referral as {
    name?: string | null
    phone?: string | null
    email?: string | null
    referrer_client?: {
      name?: string | null
      phone?: string | null
      email?: string | null
    } | null
    referrer_user?: {
      name?: string | null
      phone?: string | null
      email?: string | null
    } | null
    referrerClient?: {
      name?: string | null
      phone?: string | null
      email?: string | null
    } | null
    referrerUser?: {
      name?: string | null
      phone?: string | null
      email?: string | null
    } | null
  } | null | undefined
  const linkedReferrer = referralRecord?.referrer_client
    ?? referralRecord?.referrerClient
    ?? referralRecord?.referrer_user
    ?? referralRecord?.referrerUser
  const referralDetails: Array<{ label: string, value?: string | null, fallback: string, Icon: DetailIcon }> = [
    { label: 'Referral Name', value: normalizeDetailValue(referralRecord?.name) ?? normalizeDetailValue(linkedReferrer?.name), fallback: 'No referral name available', Icon: UserIcon },
    { label: 'Referral Phone', value: normalizeDetailValue(referralRecord?.phone) ?? normalizeDetailValue(linkedReferrer?.phone), fallback: 'No referral phone available', Icon: PhoneIcon },
    { label: 'Referral Email', value: normalizeDetailValue(referralRecord?.email) ?? normalizeDetailValue(linkedReferrer?.email), fallback: 'No referral email available', Icon: EmailIcon }
  ]
  const descriptionText = order.description?.trim()
  const rawCompany = order.client?.company_contact as unknown
  const companyContacts = rawCompany
    ? (Array.isArray(rawCompany) ? rawCompany : [rawCompany])
    : []
  const orderCompanyContacts = Array.isArray((order as any).order_company_contacts)
    ? (order as any).order_company_contacts
    : Array.isArray((order as any).orderCompanyContacts)
      ? (order as any).orderCompanyContacts
      : []
  const sortedOrderCompanyContacts = [...orderCompanyContacts].sort((a, b) => {
    const selectedDiff = Number(Boolean(b?.is_selected)) - Number(Boolean(a?.is_selected))
    if (selectedDiff !== 0) return selectedDiff
    const nameA = String(a?.company_contact?.name ?? '').toLowerCase()
    const nameB = String(b?.company_contact?.name ?? '').toLowerCase()
    return nameA.localeCompare(nameB)
  })
  const commercialCompanyOptions = sortedOrderCompanyContacts.map((item: any) => ({
    id: item.id,
    label: [item.company_contact?.name ?? item.companyContact?.name ?? 'Company', item.client?.name ? `- ${item.client?.name}` : ''].join(' ').trim(),
    client_email: item.client?.email ?? null
  }))
  const selectedCommercialCompanyId = sortedOrderCompanyContacts.find((item: any) => item.is_selected)?.id
    ?? (sortedOrderCompanyContacts.length === 1 ? sortedOrderCompanyContacts[0]?.id : null)
  const ownerNames = Array.isArray(order.owners)
    ? order.owners.map((owner: any) => owner?.name).filter(Boolean)
    : []
  const primaryOwnerDisplay = ownerNames.length > 0
    ? ownerNames.join(', ')
    : (order.user?.name ?? '')
  const isVipClient = Boolean(order.client?.vip_clients)
  const isCommercialOrder = order.order_type?.toLowerCase() === 'commercial'
  const selectedCommercialCompany = isCommercialOrder
    ? sortedOrderCompanyContacts.find((item: any) => item?.is_selected)
      ?? (sortedOrderCompanyContacts.length === 1 ? sortedOrderCompanyContacts[0] : null)
    : null
  const commercialBidDueDateLabel = formatDateOnly(
    selectedCommercialCompany?.company_contact?.bid_due_date
      ?? selectedCommercialCompany?.companyContact?.bid_due_date
      ?? order.bid_due_date
      ?? null
  )
  const scheduleAppointmentLabel = formatScheduleDisplay(scheduleAppointmentIso)
  const lossReasonFrontdeskValue = order.loss_reason_frontdesk?.trim()

  const initialAttachments = Array.isArray(order.attachments) ? order.attachments : []
  const [attachments, setAttachments] = useState<Attachment[]>(initialAttachments)
  const [newFiles, setNewFiles] = useState<File[]>([])
  const [uploading, setUploading] = useState(false)
  const [uploadError, setUploadError] = useState<string | null>(null)
  const [deleteError, setDeleteError] = useState<string | null>(null)
  const [deletingIds, setDeletingIds] = useState<number[]>([])
  const fileInputRef = useRef<HTMLInputElement | null>(null)
  const attachmentDateFormatter = useRef(
    typeof window !== 'undefined'
      ? new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
      })
      : null
  )
  const [activityRefreshKey, setActivityRefreshKey] = useState(0)

  useEffect(() => {
    setOrder(initialOrder)
    setOrderFormInitialValues(loadOrderFormObj(initialOrder))
    setPaymentEdits({})
    setPaymentError(null)
    setPaymentSavingId(null)
    setMovementDrafts({})
    setMovementEdits({})
    setMovementSavingKey(null)
    const nextScheduleIso = initialOrder.schedule_appointment_iso ?? toScheduleString(initialOrder.schedule_appointment ?? null)
    setScheduleInitialValues({
      scheduleDate: nextScheduleIso ? normalizeScheduleValue(nextScheduleIso) : '',
      ownerIds: Array.isArray(initialOrder.owners)
        ? initialOrder.owners
          .map(owner => Number(owner.id))
          .filter(id => Number.isFinite(id))
        : []
    })
    setAttachments(Array.isArray(initialOrder.attachments) ? initialOrder.attachments : [])
  }, [initialOrder])

  const refreshOrderActivity = useCallback(() => {
    setActivityRefreshKey(prev => prev + 1)
    router.reload({
      only: ['order', 'snapshots'],
      preserveScroll: true,
      preserveState: true
    })
  }, [])

  const mapStatusToPipeline = (status: string): string => {
    if (isOrderProcessingStatus(status)) {
      return status
    }
    if (matchesStatus(status, 'RECTIFICATION OF MEASURES AND HOA') || matchesStatus(status, 'ORDER MATERIALS AND FILE ORGANIZATION')) {
      return 'CONTRACT SIGNED BY CLIENT'
    }
    return status
  }

  const actualStatusValue = order.status ?? ''
  const isLostRequest = matchesStatus(actualStatusValue, 'LOST REQUEST')
  const orderInFrontdeskFlow = isFrontdeskStatus(actualStatusValue)
  const orderInSalesFlow = isSalesStatus(actualStatusValue)
  const orderInOrderStorageFlow = isOrderStorageStatus(actualStatusValue)
  const orderStorageTransitionsEnabled = orderInOrderStorageFlow && isOrderStorageTransitionStatus(actualStatusValue)
  const isAdminOrOwnerAdmin = isAdmin(roleNames) || isOwnerAdmin(roleNames)
  const pipelineStatuses = orderInFrontdeskFlow
    ? FRONTDESK_STATUS_OPTIONS
    : (orderInSalesFlow ? SALES_STATUS_OPTIONS : (orderInOrderStorageFlow ? ORDER_STORAGE_STATUS_OPTIONS : ORDER_PROCESSING_STATUS_OPTIONS))
  const pipelineDropdownStatuses = useMemo(() => {
    let base = Array.from(pipelineStatuses) as string[]

    if (orderInOrderStorageFlow) {
      base = orderStorageTransitionsEnabled
        ? Array.from(ORDER_STORAGE_TRANSITION_STATUSES)
        : (actualStatusValue ? [actualStatusValue] : [])
    }

    if (orderInSalesFlow && isAdminOrOwnerAdmin) {
      for (const extraStatus of FRONTDESK_SALES_DROPDOWN_EXTRA_STATUSES) {
        if (!base.some((statusOption) => matchesStatus(statusOption, extraStatus))) {
          base.push(extraStatus)
        }
      }
    }

    if (actualStatusValue && !base.some((statusOption) => matchesStatus(statusOption, actualStatusValue))) {
      base.unshift(actualStatusValue)
    }

    return base
  }, [actualStatusValue, isAdminOrOwnerAdmin, orderInSalesFlow, orderInOrderStorageFlow, orderStorageTransitionsEnabled, pipelineStatuses])
  const pipelineStatusValue = mapStatusToPipeline(actualStatusValue)
  const calculatedStatusIndex = pipelineStatuses.findIndex(status => matchesStatus(status, pipelineStatusValue))
  const fallbackStatusIndex = pipelineStatuses.length > 0
    ? (actualStatusValue ? pipelineStatuses.length - 1 : 0)
    : -1
  const currentStatusIndex = calculatedStatusIndex >= 0 ? calculatedStatusIndex : fallbackStatusIndex
  const pipelineTitle = orderInFrontdeskFlow
    ? 'Frontdesk Pipeline'
    : (orderInSalesFlow ? 'Sales Pipeline' : (orderInOrderStorageFlow ? 'Order Storage Pipeline' : 'Order Processing Pipeline'))
  const pipelineHint = canEditPipeline
    ? 'Click a stage to move the order and complete the required workflow.'
    : 'View the workflow stages for this order.'
  const [statusPickerAnchor, setStatusPickerAnchor] = useState<{ status: string, element: HTMLButtonElement } | null>(null)
  const [statusPickerPosition, setStatusPickerPosition] = useState<{ left: number, top: number } | null>(null)
  const [statusSearch, setStatusSearch] = useState('')
  const pipelineButtonWidthClass = orderInFrontdeskFlow
    ? 'w-[6.5rem]'
    : (orderInSalesFlow ? 'w-[6.5rem]' : 'w-[8.5rem]')
  const pipelineDropdownWidthClass = orderInFrontdeskFlow
    ? 'w-64'
    : (orderInSalesFlow ? 'w-72' : 'w-80')
  const frontdeskQuantifiedTask = useMemo(() => ({ id: order.id } as Tasks), [order.id])
  const noopSetProjectList = useCallback<React.Dispatch<React.SetStateAction<Pipelines[]>>>((value: React.SetStateAction<Pipelines[]>) => {}, [])
  const noopUpdateOrderStatus = useCallback(async () => {}, [])

  const closeStatusPicker = useCallback(() => {
    setStatusPickerAnchor(null)
    setStatusPickerPosition(null)
    setStatusSearch('')
  }, [])

  const closeOrderProcessingModal = useCallback(() => {
    setOrderProcessingModalOpen(false)
    setPendingOrderProcessingMove(null)
    setOrderProcessingNote('')
    setOrderProcessingInvoiceNumber('')
    setOrderProcessingAttachments([])
    setOrderProcessingError(null)
  }, [])

  const closeFrontdeskStandByModal = () => {
    setFrontdeskStandByModalOpen(false)
    setFrontdeskStandByNote('')
    setFrontdeskStandByError(null)
    setFrontdeskStandBySaving(false)
  }

  const closeFrontdeskLostModal = () => {
    setFrontdeskLostModalOpen(false)
    setFrontdeskLostReason('')
    setFrontdeskLostNotes('')
    setFrontdeskLostError(null)
    setFrontdeskLostSaving(false)
  }

  const updateStatusPickerPosition = useCallback((element: HTMLButtonElement | null) => {
    if (!element) return
    const rect = element.getBoundingClientRect()
    setStatusPickerPosition({
      left: rect.left + (rect.width / 2),
      top: rect.bottom + 8
    })
  }, [])

  type SimpleStatusChangeOptions = {
    note?: string
    attachments?: File[]
    invoice_number?: string
    onError?: (message: string) => void
  }

  const handleSimpleStatusChange = async (
    targetStatus: string,
    options?: SimpleStatusChangeOptions,
    confirmCustomerRole = false
  ): Promise<boolean> => {
    setStatusChangeSaving(true)
    setStatusChangeError(null)
    closeStatusPicker()

    let errorMessage = 'Unable to update status.'
    try {
      const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
      const noteContent = options?.note?.trim() ?? ''
      const invoiceNumber = options?.invoice_number?.trim() ?? ''
      const attachments = options?.attachments ?? []

      const response = options
        ? await fetch(route('frontdesk.updateStatus', { order: order.id }), {
          method: 'POST',
          headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf
          },
          body: (() => {
            const formData = new FormData()
            formData.append('status', targetStatus)
            if (noteContent !== '') {
              formData.append('note', noteContent)
            }
            if (invoiceNumber !== '') {
              formData.append('invoice_number', invoiceNumber)
            }
            if (confirmCustomerRole) {
              formData.append('confirm_customer_role', '1')
            }
            attachments.forEach((file) => {
              formData.append('attachments[]', file)
            })
            return formData
          })()
        })
        : await fetch(route('frontdesk.updateStatus', { order: order.id }), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrf
          },
          body: JSON.stringify({
            status: targetStatus,
            ...(invoiceNumber !== '' ? { invoice_number: invoiceNumber } : {}),
            ...(confirmCustomerRole ? { confirm_customer_role: true } : {})
          })
        })

      const payload = await response.json().catch(() => null)

      if (!response.ok || !payload?.order) {
        if (response.status === 409 && payload?.requires_confirmation) {
          const confirmed = window.confirm(payload?.message ?? 'This email already belongs to another user. Convert it to customer?')
          if (!confirmed) {
            return false
          }

          return await handleSimpleStatusChange(targetStatus, options, true)
        }

        const message = payload?.message ?? 'Unable to update status.'
        throw new Error(message)
      }

      setOrder((prev) => ({
        ...prev,
        status: targetStatus
      }))
      refreshOrderActivity()
      return true
    } catch (error: any) {
      console.error('status change error', error)
      errorMessage = error?.message ?? errorMessage
      setStatusChangeError(errorMessage)
      options?.onError?.(errorMessage)
      return false
    } finally {
      setStatusChangeSaving(false)
    }
  }

  const handleOrderProcessingSubmit = async () => {
    if (!pendingOrderProcessingMove) return

    setOrderProcessingError(null)
    setStatusChangeError(null)

    const shouldSendInvoice = matchesStatus(pendingOrderProcessingMove.newStatus, 'REVIEW')

    const success = await handleSimpleStatusChange(pendingOrderProcessingMove.newStatus, {
      note: orderProcessingNote,
      attachments: orderProcessingAttachments,
      ...(shouldSendInvoice ? { invoice_number: orderProcessingInvoiceNumber } : {}),
      onError: (message) => { setOrderProcessingError(message) }
    })

    if (success) {
      closeOrderProcessingModal()
    }
  }

  const handleFrontdeskStandBySubmit = async () => {
    if (!frontdeskStandByNote.trim()) {
      setFrontdeskStandByError('Note is required.')
      return
    }
    setFrontdeskStandBySaving(true)
    setFrontdeskStandByError(null)
    try {
      const response = await fetch(route('frontdesk.updateStatusStandBy', { order: order.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({ note: frontdeskStandByNote })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok || !payload?.order) {
        const message = payload?.message ?? 'Unable to update status.'
        throw new Error(message)
      }

      setOrder(prev => ({
        ...prev,
        ...payload.order
      }))
      refreshOrderActivity()
      closeFrontdeskStandByModal()
    } catch (error: any) {
      console.error('frontdesk stand-by error', error)
      setFrontdeskStandByError(error?.message ?? 'Unable to update status.')
    } finally {
      setFrontdeskStandBySaving(false)
    }
  }

  const handleFrontdeskLostSubmit = async () => {
    if (!frontdeskLostReason.trim()) {
      setFrontdeskLostError('Loss reason is required.')
      return
    }
    setFrontdeskLostSaving(true)
    setFrontdeskLostError(null)
    try {
      const response = await fetch(route('frontdesk.updateStatusLost', { order: order.id }), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          status: 'LOST REQUEST',
          loss_reason_frontdesk: frontdeskLostReason,
          notes: frontdeskLostNotes || null
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok || !payload?.order) {
        const message = payload?.message ?? 'Unable to update status.'
        throw new Error(message)
      }

      setOrder(prev => ({
        ...prev,
        ...payload.order
      }))
      refreshOrderActivity()
      closeFrontdeskLostModal()
    } catch (error: any) {
      console.error('frontdesk lost error', error)
      setFrontdeskLostError(error?.message ?? 'Unable to update status.')
    } finally {
      setFrontdeskLostSaving(false)
    }
  }

  const handleStatusSelection = (targetStatus: string) => {
    const normalizedTarget = normalizeStatusValue(targetStatus)
    const normalizedCurrent = normalizeStatusValue(pipelineStatusValue)

    closeStatusPicker()

    if (normalizedTarget === normalizedCurrent) return

    setStatusChangeError(null)

    if (orderInOrderStorageFlow) {
      if (!isOrderStorageTransitionStatus(actualStatusValue) || !isOrderStorageTransitionStatus(targetStatus)) {
        setStatusChangeError('Only ACCOUNT RECEIPT and REVIEW can be updated from this workflow.')
        return
      }
    }

    if (orderInFrontdeskFlow && matchesStatus(targetStatus, 'REQUEST STAND BY')) {
      closeStatusPicker()
      setFrontdeskStandByNote('')
      setFrontdeskStandByError(null)
      setFrontdeskStandByModalOpen(true)
      return
    }

    if (orderInFrontdeskFlow && matchesStatus(targetStatus, 'LOST REQUEST')) {
      closeStatusPicker()
      setFrontdeskLostReason('')
      setFrontdeskLostNotes('')
      setFrontdeskLostError(null)
      setFrontdeskLostModalOpen(true)
      return
    }

    if (orderInFrontdeskFlow && matchesStatus(targetStatus, 'QUALIFIED')) {
      closeStatusPicker()
      setFrontdeskQuantifiedModalOpen(true)
      return
    }

    if (isFrontdeskStatus(targetStatus)) {
      handleSimpleStatusChange(targetStatus)
      return
    }

    if (matchesStatus(targetStatus, ESTIMATE_STATUS)) {
      setPendingMove({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setScheduleInitialValues({
        scheduleDate: scheduleAppointmentIso ? normalizeScheduleValue(scheduleAppointmentIso) : '',
        ownerIds: Array.isArray(order.owners) ? order.owners.map(owner => Number(owner.id)).filter((id) => Number.isFinite(id)) : []
      })
      setScheduleModalOpen(true)
      return
    }

    if (FOLLOW_UP_STATUSES.some(status => matchesStatus(status, targetStatus))) {
      setPendingFollowUp({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setFollowUpInitialValues({
        projectAmount: order.project_amount ? String(order.project_amount) : '',
        note: '',
        targetStatus
      })
      setFollowUpModalOpen(true)
      return
    }

    if (matchesStatus(targetStatus, REQUEST_RESCHEDULE_STATUS)) {
      if (!matchesStatus(actualStatusValue, ESTIMATE_STATUS)) {
      setStatusChangeError('Orders can only move to REQUEST RE-SCHEDULE from ESTIMATE & APPT SCHEDULE.')
      return
    }
      setPendingRequestReschedule({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setRequestRescheduleInitialNote('')
      setRequestRescheduleModalOpen(true)
      return
    }

    if (matchesStatus(targetStatus, 'STAND BY')) {
      setPendingStandBy({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setStandByInitialNote('')
      setStandByModalOpen(true)
      return
    }

    if (matchesStatus(targetStatus, 'PRE CONTRACT APPOINTMENT')) {
      setPendingPreContract({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setPreContractInitialNote('')
      setPreContractModalOpen(true)
      return
    }

    if (matchesStatus(targetStatus, 'CONTRACT SIGNED BY CLIENT')) {
      setPendingContractSigned({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setContractSignedInitialValues((prev) => ({
        ...prev,
        projectName: order.name ?? prev.projectName,
        projectAmount: order.project_amount ? String(order.project_amount) : prev.projectAmount,
        downPayment: order.down_payment ? String(order.down_payment) : prev.downPayment,
        jobAddress: order.job_address ?? prev.jobAddress,
        city: order.city ?? prev.city,
        jobState: order.job_state ?? prev.jobState,
        jobZip: order.job_zip ?? prev.jobZip,
        methodOfPayment: order.method_of_payment ?? prev.methodOfPayment,
        typeOfFinancing: order.type_of_financing ?? prev.typeOfFinancing,
        contactEmail: order.client?.email ?? prev.contactEmail,
        orderCompanyContactId: selectedCommercialCompanyId ?? prev.orderCompanyContactId ?? null,
        nameCheck: Boolean(order.name_check),
        addressCheck: Boolean(order.address_check),
        amountCheck: Boolean(order.amount_check),
        emailCheck: Boolean(order.email_check),
        cityPermits: Boolean(order.city_permits),
        associationPermits: Boolean(order.association_permits),
        paymentScheduleType: order.payment_schedule?.schedule_type ?? prev.paymentScheduleType,
        customSchedule: order.payment_schedule?.schedule_type === CUSTOM_SCHEDULE_TYPE
          ? buildCustomSchedule(order.payment_schedule?.installments)
          : buildCustomSchedule()
      }))
      setContractSignedModalOpen(true)
      return
    }

    if (matchesStatus(targetStatus, 'LOST CONTRACT')) {
      setPendingLostContract({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setLostContractModalOpen(true)
      return
    }

    if (!orderInFrontdeskFlow && !orderInSalesFlow) {
      setPendingOrderProcessingMove({ oldStatus: actualStatusValue, newStatus: targetStatus })
      setOrderProcessingNote('')
      setOrderProcessingInvoiceNumber(order.invoice_number ?? '')
      setOrderProcessingAttachments([])
      setOrderProcessingError(null)
      setOrderProcessingModalOpen(true)
      return
    }

    handleSimpleStatusChange(targetStatus)
  }

  useEffect(() => {
    const anchorElement = statusPickerAnchor?.element
    if (!anchorElement) return

    const handlePositionChange = () => {
      if (!document.body.contains(anchorElement)) {
        closeStatusPicker()
        return
      }
      updateStatusPickerPosition(anchorElement)
    }

    handlePositionChange()

    const handleScroll = () => { handlePositionChange() }
    const handleResize = () => { handlePositionChange() }

    document.addEventListener('scroll', handleScroll, true)
    window.addEventListener('resize', handleResize)

    return () => {
      document.removeEventListener('scroll', handleScroll, true)
      window.removeEventListener('resize', handleResize)
    }
  }, [statusPickerAnchor?.element, closeStatusPicker, updateStatusPickerPosition])

  const closeScheduleModal = () => {
    setScheduleModalOpen(false)
    setScheduleError(null)
    setScheduleSaving(false)
    setPendingMove(null)
  }

  const handleScheduleSubmit = async (values: { scheduleDate: string, ownerIds: number[] }) => {
    if (!pendingMove) return
    setScheduleSaving(true)
    setScheduleError(null)

    try {
      const response = await fetch(route('sales.assign_estimate', order.id), {
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

      setOrder(prev => ({
        ...prev,
        status: pendingMove.newStatus,
        schedule_appointment: data.order.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? prev.owner_ids,
        owners: data.order.owners ?? prev.owners
      }))

      refreshOrderActivity()
      closeScheduleModal()
    } catch (error: any) {
      console.error('assign-estimate error', error)
      setScheduleError(error?.message ?? 'No se pudo guardar la asignación.')
    } finally {
      setScheduleSaving(false)
    }
  }

  const closeFollowUpModal = () => {
    setFollowUpModalOpen(false)
    setFollowUpError(null)
    setFollowUpSaving(false)
    setFollowUpInitialValues({ projectAmount: '', note: '', targetStatus: '' })
    setPendingFollowUp(null)
  }

  const handleFollowUpSubmit = async (values: { projectAmount: string, note: string, attachments: File[] }) => {
    if (!pendingFollowUp) return

    setFollowUpSaving(true)
    setFollowUpError(null)

    try {
      const formData = new FormData()
      formData.append('status', pendingFollowUp.newStatus)
      formData.append('project_amount', values.projectAmount)
      formData.append('note', values.note)

      values.attachments?.forEach((file) => {
        formData.append('attachments[]', file)
      })

      const response = await fetch(route('sales.assign_follow_up', order.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: formData
      })

      if (!response.ok) {
        const payload = await response.json().catch(() => null)
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }
        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      const payload = await response.json()
      const data = payload.order
      const projectAmountValue = data.project_amount ?? values.projectAmount
      const normalizedProjectAmount = Number(
        typeof projectAmountValue === 'string'
          ? projectAmountValue.replace(/,/g, '')
          : projectAmountValue
      )

      setOrder(prev => ({
        ...prev,
        status: pendingFollowUp.newStatus,
        schedule_appointment: data.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.owner_ids ?? prev.owner_ids,
        owners: data.owners ?? prev.owners,
        project_amount: Number.isFinite(normalizedProjectAmount) ? normalizedProjectAmount : prev.project_amount
      }))

      refreshOrderActivity()
      closeFollowUpModal()
    } catch (error: any) {
      console.error('follow-up submit error', error)
      setFollowUpError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setFollowUpSaving(false)
    }
  }

  const closeStandByModal = () => {
    setStandByModalOpen(false)
    setStandByError(null)
    setStandBySaving(false)
    setStandByInitialNote('')
    setPendingStandBy(null)
  }

  const handleStandBySubmit = async (values: { note: string }) => {
    if (!pendingStandBy) return

    setStandBySaving(true)
    setStandByError(null)

    try {
      const response = await fetch(route('sales.assign_stand_by', order.id), {
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

      const data = payload.order

      setOrder(prev => ({
        ...prev,
        status: pendingStandBy.newStatus,
        schedule_appointment: data.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.owner_ids ?? prev.owner_ids,
        owners: data.owners ?? prev.owners
      }))

      refreshOrderActivity()
      closeStandByModal()
    } catch (error: any) {
      console.error('stand-by submit error', error)
      setStandByError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setStandBySaving(false)
    }
  }

  const closeRequestRescheduleModal = () => {
    setRequestRescheduleModalOpen(false)
    setRequestRescheduleError(null)
    setRequestRescheduleSaving(false)
    setRequestRescheduleInitialNote('')
    setPendingRequestReschedule(null)
  }

  const handleRequestRescheduleSubmit = async (values: { note: string }) => {
    if (!pendingRequestReschedule) return

    setRequestRescheduleSaving(true)
    setRequestRescheduleError(null)

    try {
      const response = await fetch(route('sales.assign_request_reschedule', order.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note
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

      const data = payload.order

      setOrder(prev => ({
        ...prev,
        status: pendingRequestReschedule.newStatus,
        schedule_appointment: data.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.owner_ids ?? prev.owner_ids,
        owners: data.owners ?? prev.owners
      }))

      refreshOrderActivity()
      closeRequestRescheduleModal()
    } catch (error: any) {
      console.error('request-reschedule submit error', error)
      setRequestRescheduleError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setRequestRescheduleSaving(false)
    }
  }

  const closePreContractModal = () => {
    setPreContractModalOpen(false)
    setPreContractError(null)
    setPreContractSaving(false)
    setPreContractInitialNote('')
    setPendingPreContract(null)
  }

  const handlePreContractSubmit = async (values: { note: string }) => {
    if (!pendingPreContract) return

    setPreContractSaving(true)
    setPreContractError(null)

    try {
      const response = await fetch(route('sales.assign_pre_contract', order.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note
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

      const data = payload.order

      setOrder(prev => ({
        ...prev,
        status: pendingPreContract.newStatus,
        schedule_appointment: data.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.owner_ids ?? prev.owner_ids,
        owners: data.owners ?? prev.owners
      }))

      refreshOrderActivity()
      closePreContractModal()
    } catch (error: any) {
      console.error('pre-contract submit error', error)
      setPreContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setPreContractSaving(false)
    }
  }

  const closeContractSignedModal = () => {
    setContractSignedModalOpen(false)
    setContractSignedError(null)
    setContractSignedSaving(false)
    setPendingContractSigned(null)
  }

  const handleContractSignedSubmit = async (values: any) => {
    if (!pendingContractSigned) return

    setContractSignedSaving(true)
    setContractSignedError(null)

    try {
      const formData = new FormData()
      const fieldMap: Record<string, string> = {
        projectName: 'project_name',
        projectAmount: 'project_amount',
        downPayment: 'down_payment',
        jobAddress: 'job_address',
        city: 'city',
        jobState: 'job_state',
        jobZip: 'job_zip',
        methodOfPayment: 'method_of_payment',
        typeOfFinancing: 'type_of_financing',
        contactEmail: 'contact_email',
        paymentScheduleType: 'payment_schedule_type',
        nameCheck: 'name_check',
        addressCheck: 'address_check',
        amountCheck: 'amount_check',
        emailCheck: 'email_check',
        cityPermits: 'city_permits',
        associationPermits: 'association_permits',
        orderCompanyContactId: 'order_company_contact_id'
      }

      const booleanFields = new Set(['nameCheck', 'addressCheck', 'amountCheck', 'emailCheck', 'cityPermits', 'associationPermits'])
      Object.entries(values).forEach(([key, value]) => {
        if (key === 'attachments' && Array.isArray(value)) {
          value.forEach((file: File) => {
            formData.append('attachments[]', file)
          })
        } else if (key === 'customSchedule') {
          return
        } else if (key === 'orderCompanyContactId' && !value) {
          return
        } else {
          const payloadKey = fieldMap[key] ?? key
          if (booleanFields.has(key)) {
            formData.append(payloadKey, value ? '1' : '0')
          } else {
            formData.append(payloadKey, value != null ? String(value) : '')
          }
        }
      })

      if (values.paymentScheduleType === CUSTOM_SCHEDULE_TYPE && Array.isArray(values.customSchedule)) {
        values.customSchedule.forEach((item: { label: string, amount: number }, index: number) => {
          formData.append(`custom_schedule[${index}][label]`, item.label)
          formData.append(`custom_schedule[${index}][amount]`, item.amount.toString())
        })
      }

      const response = await fetch(route('sales.assign_contract_signed', order.id), {
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

      const data = payload.order

      setOrder(prev => ({
        ...prev,
        ...data,
        name: data.name ?? prev.name,
        status: data.status ?? prev.status,
        schedule_appointment: data.schedule_appointment ?? prev.schedule_appointment,
        schedule_appointment_iso: data.schedule_appointment_iso ?? prev.schedule_appointment_iso,
        owner_ids: data.owner_ids ?? prev.owner_ids,
        owners: data.owners ?? prev.owners,
        project_amount: data.project_amount ?? prev.project_amount,
        down_payment: data.down_payment ?? prev.down_payment,
        job_address: data.job_address ?? prev.job_address,
        city: data.city ?? prev.city,
        job_state: data.job_state ?? prev.job_state,
        job_zip: data.job_zip ?? prev.job_zip,
        method_of_payment: data.method_of_payment ?? prev.method_of_payment,
        type_of_financing: data.type_of_financing ?? prev.type_of_financing,
        contact_email: data.contact_email ?? prev.contact_email,
        name_check: data.name_check ?? prev.name_check,
        address_check: data.address_check ?? prev.address_check,
        amount_check: data.amount_check ?? prev.amount_check,
        email_check: data.email_check ?? prev.email_check,
        city_permits: data.city_permits ?? prev.city_permits,
        association_permits: data.association_permits ?? prev.association_permits,
        client: prev.client
          ? { ...prev.client, email: data.contact_email ?? prev.client.email }
          : prev.client,
        order_company_contacts: data.order_company_contacts ?? (prev as any).order_company_contacts
      }))

      refreshOrderActivity()
      closeContractSignedModal()
    } catch (error: any) {
      console.error('contract-signed submit error', error)
      setContractSignedError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setContractSignedSaving(false)
    }
  }

  const mergeUpdatedInstallment = (updated: PaymentInstallment) => {
    setOrder(prev => {
      const schedule = prev.payment_schedule
      if (!schedule || !Array.isArray(schedule.installments)) return prev

      const updatedInstallments = schedule.installments.map((item) =>
        item.id === updated.id ? { ...item, ...updated } : item
      )

      const paidAmount = updatedInstallments.reduce((total, installment) => {
        const value = Number(installment.paid_amount ?? 0)
        return Number.isFinite(value) ? total + value : total
      }, 0)
      const totalAmount = Number(schedule.total_amount ?? 0)

      return {
        ...prev,
        payment_schedule: {
          ...schedule,
          installments: updatedInstallments,
          paid_amount: round2(paidAmount),
          remaining_amount: round2(Math.max(0, totalAmount - paidAmount)),
          credit_amount: round2(Math.max(0, paidAmount - totalAmount))
        }
      }
    })
  }

  const handleInstallmentFieldChange = (installmentId: number, field: 'dueDate', value: string) => {
    setPaymentEdits(prev => ({
      ...prev,
      [installmentId]: {
        ...(prev[installmentId] ?? {}),
        [field]: value
      }
    }))
  }

  const handleInstallmentSave = async (installmentId: number, defaultDueDate: string | null) => {
    setPaymentSavingId(installmentId)
    setPaymentError(null)

    try {
      const editValues = paymentEdits[installmentId] ?? {}
      const dueDate = editValues.dueDate ?? (defaultDueDate ?? '')

      const response = await fetch(route('payment_installments.update', installmentId), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          due_date: dueDate || null
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update installment.')
        }

        throw new Error(payload?.message ?? 'Unable to update installment.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const updated = payload.installment

      mergeUpdatedInstallment(updated)

      setPaymentEdits(prev => ({
        ...prev,
        [installmentId]: {
          dueDate: updated.due_date ?? dueDate
        }
      }))
      refreshOrderActivity()
    } catch (error: any) {
      console.error('payment installment update error', error)
      setPaymentError(error?.message ?? 'No se pudo actualizar el pago.')
    } finally {
      setPaymentSavingId(prev => (prev === installmentId ? null : prev))
    }
  }

  const handleMovementDraftChange = (installmentId: number, field: keyof MovementFormValues, value: string | boolean) => {
    setMovementDrafts(prev => ({
      ...prev,
      [installmentId]: {
        ...(prev[installmentId] ?? emptyMovementFormValues()),
        [field]: value
      }
    }))
  }

  const handleMovementEditChange = (movement: PaymentInstallmentMovement, field: keyof MovementFormValues, value: string) => {
    const defaultValues: MovementFormValues = {
      useDefaultAmount: false,
      amount: String(movement.amount ?? ''),
      note: movement.note ?? ''
    }

    setMovementEdits(prev => ({
      ...prev,
      [movement.id]: {
        ...(prev[movement.id] ?? defaultValues),
        [field]: value
      }
    }))
  }

  const handleMovementCreate = async (installment: PaymentInstallment) => {
    const installmentId = installment.id
    const draft = movementDrafts[installmentId] ?? emptyMovementFormValues()
    const scheduledAmount = Number(installment.amount ?? 0)
    const remainingAmount = Number(installment.balance ?? Math.max(0, scheduledAmount - Number(installment.paid_amount ?? 0)))
    const defaultAmount = remainingAmount > 0 ? remainingAmount : scheduledAmount
    const amount = draft.useDefaultAmount
      ? defaultAmount
      : Number(String(draft.amount ?? '').replace(/,/g, '').trim())
    const schedule = order.payment_schedule
    const scheduleTotal = Number(schedule?.total_amount ?? 0)
    const schedulePaid = Number(schedule?.paid_amount ?? 0)
    const remainingCapacity = Math.max(0, round2(scheduleTotal - schedulePaid))

    if (!Number.isFinite(amount) || amount <= 0) {
      setPaymentError(draft.useDefaultAmount
        ? 'This installment has no pending default amount to pay.'
        : 'Enter a valid payment amount greater than 0.')
      return
    }

    if (amount > remainingCapacity + 0.01) {
      setPaymentError(`Total paid cannot exceed schedule total. Maximum allowed now is ${formatScheduleCurrency(remainingCapacity)}.`)
      return
    }

    setMovementSavingKey(`create-${installmentId}`)
    setPaymentError(null)

    try {
      const response = await fetch(route('payment_installment_movements.store', installmentId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          amount,
          note: draft.note || null
        })
      })

      const payload = await response.json().catch(() => null)
      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to create payment movement.')
        }
        throw new Error(payload?.message ?? 'Unable to create payment movement.')
      }

      if (!payload?.installment) {
        throw new Error('Unexpected server response.')
      }

      mergeUpdatedInstallment(payload.installment)
      setMovementDrafts(prev => ({
        ...prev,
        [installmentId]: emptyMovementFormValues()
      }))
      refreshOrderActivity()
    } catch (error: any) {
      console.error('payment movement create error', error)
      setPaymentError(error?.message ?? 'No se pudo registrar el pago.')
    } finally {
      setMovementSavingKey(prev => (prev === `create-${installmentId}` ? null : prev))
    }
  }

  const handleMovementUpdate = async (movement: PaymentInstallmentMovement) => {
    const editedValues = movementEdits[movement.id] ?? {
      useDefaultAmount: false,
      amount: String(movement.amount ?? ''),
      note: movement.note ?? ''
    }
    const amountRaw = editedValues.amount
    const amount = Number(String(amountRaw).replace(/,/g, '').trim())
    const currentAmount = Number(movement.amount ?? 0)
    const schedule = order.payment_schedule
    const scheduleTotal = Number(schedule?.total_amount ?? 0)
    const schedulePaid = Number(schedule?.paid_amount ?? 0)
    const remainingCapacity = Math.max(0, round2(scheduleTotal - schedulePaid))
    const maxAllowed = round2(currentAmount + remainingCapacity)

    if (!Number.isFinite(amount) || amount <= 0) {
      setPaymentError('Enter a valid payment amount greater than 0.')
      return
    }

    if (amount > maxAllowed + 0.01) {
      setPaymentError(`Total paid cannot exceed schedule total. Maximum allowed now is ${formatScheduleCurrency(maxAllowed)}.`)
      return
    }

    setMovementSavingKey(`update-${movement.id}`)
    setPaymentError(null)

    try {
      const response = await fetch(route('payment_installment_movements.update', movement.id), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          amount,
          note: editedValues.note || null
        })
      })

      const payload = await response.json().catch(() => null)
      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update payment movement.')
        }
        throw new Error(payload?.message ?? 'Unable to update payment movement.')
      }

      if (!payload?.installment) {
        throw new Error('Unexpected server response.')
      }

      mergeUpdatedInstallment(payload.installment)
      setMovementEdits(prev => {
        const next = { ...prev }
        delete next[movement.id]
        return next
      })
      refreshOrderActivity()
    } catch (error: any) {
      console.error('payment movement update error', error)
      setPaymentError(error?.message ?? 'No se pudo actualizar el movimiento.')
    } finally {
      setMovementSavingKey(prev => (prev === `update-${movement.id}` ? null : prev))
    }
  }

  const handleMovementVoid = async (movementId: number, installmentId: number) => {
    const confirmVoid = window.confirm('This payment will be deleted and removed from totals. Continue?')
    if (!confirmVoid) return

    setMovementSavingKey(`void-${movementId}`)
    setPaymentError(null)

    try {
      const response = await fetch(route('payment_installment_movements.void', movementId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          note: movementEdits[movementId]?.note || null
        })
      })

      const payload = await response.json().catch(() => null)
      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to void payment movement.')
        }
        throw new Error(payload?.message ?? 'Unable to void payment movement.')
      }

      if (!payload?.installment) {
        throw new Error('Unexpected server response.')
      }

      mergeUpdatedInstallment(payload.installment)
      setMovementEdits(prev => {
        const next = { ...prev }
        delete next[movementId]
        return next
      })
      refreshOrderActivity()
    } catch (error: any) {
      console.error('payment movement void error', error)
      setPaymentError(error?.message ?? 'No se pudo anular el movimiento.')
    } finally {
      setMovementSavingKey(prev => (prev === `void-${movementId}` ? null : prev))
      setMovementDrafts(prev => ({
        ...prev,
        [installmentId]: prev[installmentId] ?? emptyMovementFormValues()
      }))
    }
  }

  const handleChangeOrderSave = async () => {
    if (!changeOrderPayment) return
    setChangeOrderSaving(true)
    setChangeOrderError(null)

    try {
      const response = await fetch(route('order_payments.update', changeOrderPayment.id), {
        method: 'PATCH',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify({
          status: changeOrderStatus
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update change order payment.')
        }

        throw new Error(payload?.message ?? 'Unable to update change order payment.')
      }

      if (!payload?.payment) {
        throw new Error('Unexpected server response.')
      }

      setOrder(prev => ({
        ...prev,
        change_order_payment: payload.payment
      }))
      refreshOrderActivity()
    } catch (error: any) {
      setChangeOrderError(error?.message ?? 'Unable to update change order payment.')
    } finally {
      setChangeOrderSaving(false)
    }
  }

  const closeLostContractModal = () => {
    setLostContractModalOpen(false)
    setLostContractError(null)
    setLostContractSaving(false)
    setPendingLostContract(null)
  }

  const handleLostContractSubmit = async (values: { lossReason: string, notes: string }) => {
    if (!pendingLostContract) return

    setLostContractSaving(true)
    setLostContractError(null)

    try {
      const response = await fetch(route('sales.assign_lost_contract', order.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          loss_reason_frontdesk: values.lossReason,
          notes: values.notes ?? null
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

      const data = payload.order

      setOrder(prev => ({
        ...prev,
        status: data.status ?? prev.status,
        loss_reason_frontdesk: data.loss_reason_frontdesk ?? prev.loss_reason_frontdesk
      }))

      refreshOrderActivity()
      closeLostContractModal()
    } catch (error: any) {
      console.error('lost-contract submit error', error)
      setLostContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setLostContractSaving(false)
    }
  }

  const handleFileSelection = (event: ChangeEvent<HTMLInputElement>) => {
    setUploadError(null)
    const files = event.target.files ? Array.from(event.target.files) : []
    setNewFiles(files)
  }

  const resetFileInput = () => {
    setNewFiles([])
    if (fileInputRef.current) {
      fileInputRef.current.value = ''
    }
  }

  const handleAttachmentUpload = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault()
    setUploadError(null)

    if (newFiles.length === 0) {
      setUploadError('Selecciona al menos un archivo para subir.')
      return
    }

    setUploading(true)

    try {
      const formData = new FormData()
      newFiles.forEach(file => formData.append('attachments[]', file))

      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

      const response = await fetch(route('order.attachments.store', order.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': token
        },
        body: formData
      })

      const data = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && data?.errors) {
          const messages = Object.values(data.errors as Record<string, string[]>).flat()
          throw new Error(messages[0] ?? 'No se pudieron subir los archivos.')
        }

        throw new Error(data?.message ?? 'No se pudieron subir los archivos.')
      }

      const attachmentList = Array.isArray(data?.attachments) ? data.attachments as Attachment[] : []
      setAttachments(attachmentList)
      setDeleteError(null)
      resetFileInput()
    } catch (error: any) {
      console.error('upload attachments error', error)
      setUploadError(error?.message ?? 'No se pudieron subir los archivos.')
    } finally {
      setUploading(false)
    }
  }

  const handleAttachmentDelete = async (attachmentId: number) => {
    setDeleteError(null)
    setDeletingIds(prev => [...prev, attachmentId])

    try {
      const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

      const response = await fetch(route('order.drop_attachment', attachmentId), {
        method: 'DELETE',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': token
        }
      })

      const data = await response.json().catch(() => null)

      if (!response.ok) {
        throw new Error(data?.message ?? 'No se pudo eliminar el archivo.')
      }

      setAttachments(prev => prev.filter(attachment => attachment.id !== attachmentId))
    } catch (error: any) {
      console.error('delete attachment error', error)
      setDeleteError(error?.message ?? 'No se pudo eliminar el archivo.')
    } finally {
      setDeletingIds(prev => prev.filter(id => id !== attachmentId))
    }
  }

  const tabsBase: Array<{ key: TabKey, label: string, Icon: DetailIcon }> = [
    { key: 'home', label: 'Notes', Icon: EmailIcon },
    { key: 'profile', label: 'Timeline', Icon: UserIcon },
    { key: 'contact', label: 'Associated Orders', Icon: ReorderIcon },
    { key: 'sales', label: 'Sales Form', Icon: BookIcon },
    { key: 'payments', label: 'Payments', Icon: MoneyBagIcon },
    { key: 'attachments', label: 'Attachments', Icon: FolderIcon }
  ]
  const tabs = isFrontdeskEsrRole
    ? tabsBase.filter((tab) => tab.key !== 'payments')
    : tabsBase

  const projectAmountNumber = Number(order.project_amount ?? 0)
  const showProjectAmount = Number.isFinite(projectAmountNumber) && projectAmountNumber > 1
  const formattedProjectAmount = showProjectAmount
    ? `$${projectAmountNumber.toLocaleString()}`
    : null
  const paymentSchedule = order.payment_schedule ?? null
  const changeOrderPayment = order.change_order_payment ?? null
  const paymentInstallments: PaymentInstallment[] = Array.isArray(paymentSchedule?.installments)
    ? paymentSchedule?.installments ?? []
    : []
  const paymentTotalAmount = Number(paymentSchedule?.total_amount ?? 0)
  const paymentPaidAmount = Number(paymentSchedule?.paid_amount ?? paymentInstallments.reduce((total, installment) => {
    const amountValue = Number(installment.paid_amount ?? 0)
    return Number.isFinite(amountValue) ? total + amountValue : total
  }, 0))
  const paymentRemainingAmount = Number(paymentSchedule?.remaining_amount ?? Math.max(0, paymentTotalAmount - paymentPaidAmount))
  const paymentCreditAmount = Number(paymentSchedule?.credit_amount ?? Math.max(0, paymentPaidAmount - paymentTotalAmount))
  const scheduleRemainingCapacity = Math.max(0, paymentTotalAmount - paymentPaidAmount)
  const formatScheduleCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)
  const formatPaidAt = (value?: string | null) => {
    if (!value) return '-'
    const parsed = new Date(value)
    return Number.isNaN(parsed.getTime()) ? value : parsed.toLocaleString()
  }
  const paymentMethodLabel = typeof order.method_of_payment === 'string'
    ? order.method_of_payment.trim()
    : ''
  const normalizedPaymentMethod = paymentMethodLabel.toUpperCase()
  const hasFinancingPaymentMethod = normalizedPaymentMethod === 'FINANCED' || normalizedPaymentMethod === 'CASH AND FINANCED'
  const isCashAndFinancedPaymentMethod = normalizedPaymentMethod === 'CASH AND FINANCED'
  const financingTypeLabel = typeof order.type_of_financing === 'string' && order.type_of_financing.trim() !== ''
    ? order.type_of_financing.trim()
    : '-'
  const downPaymentNumber = Number(order.down_payment ?? Number.NaN)
  const paymentScheduleTotalAmount = Number(paymentSchedule?.total_amount ?? Number.NaN)
  const cashAmountNumber = Number.isFinite(downPaymentNumber)
    ? downPaymentNumber
    : (isCashAndFinancedPaymentMethod && Number.isFinite(paymentScheduleTotalAmount) ? paymentScheduleTotalAmount : Number.NaN)
  const cashAmountLabel = Number.isFinite(cashAmountNumber)
    ? formatScheduleCurrency(cashAmountNumber)
    : '-'
  const amountToFinanceNumber = Number.isFinite(projectAmountNumber) && Number.isFinite(cashAmountNumber)
    ? projectAmountNumber - cashAmountNumber
    : null
  const amountToFinanceLabel = amountToFinanceNumber != null && Number.isFinite(amountToFinanceNumber)
    ? formatScheduleCurrency(amountToFinanceNumber)
    : '-'

  const onKeyDown = (e: KeyboardEvent<HTMLUListElement>) => {
    const idx = tabs.findIndex((t) => t.key === tab)
    if (e.key === 'ArrowRight') {
      setTab(tabs[(idx + 1) % tabs.length].key)
    } else if (e.key === 'ArrowLeft') {
      setTab(tabs[(idx - 1 + tabs.length) % tabs.length].key)
    }
  }

  const { data, setData, processing, patch } = useForm<{ tags: TagItem[] }>({
    tags: safeTags
  })
  const normalizedOrderStatus = typeof order.status === 'string' ? order.status.trim().toUpperCase() : ''
  const shouldHideDescriptionAndJobInfo = normalizedOrderStatus
    ? HIDE_DESCRIPTION_AND_JOB_STATUS.has(normalizedOrderStatus)
    : false
  const selectedTagCount = data.tags?.length ?? 0
  const statusCount = safeOrderStatuses.length
  const timelineItems = useMemo(() => {
    const sortedSnapshots = [...safeSnapshots]
      .filter((snapshot) => snapshot?.snapshot_data)
      .sort((a, b) => {
        const aTime = a.created_at ? new Date(a.created_at).getTime() : 0
        const bTime = b.created_at ? new Date(b.created_at).getTime() : 0
        return aTime - bTime
      })

    const items: TimelineItem[] = []

    sortedSnapshots.forEach((snapshot, index) => {
      const data = normalizeSnapshotData(snapshot.snapshot_data)
      if (snapshot.status) {
        data.status = snapshot.status
      }

      const previousSnapshot = index > 0 ? sortedSnapshots[index - 1] : null
      const previousData = previousSnapshot ? normalizeSnapshotData(previousSnapshot.snapshot_data) : null
      if (previousSnapshot?.status && previousData) {
        previousData.status = previousSnapshot.status
      }
      const createdAt = snapshot.created_at ? new Date(snapshot.created_at) : new Date()
      const actorName = data.actor?.name ?? snapshot.user?.name ?? 'System'

      if (!previousData) {
        items.push({
          id: `snapshot-${snapshot.id}-created`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Request created by ${actorName}`,
          description: data.name ? String(data.name) : undefined,
          icon: BookIcon,
          iconTone: 'success'
        })
        return
      }

      const notesAdded = diffAddedItems(
        normalizeSnapshotArray(data.notes),
        normalizeSnapshotArray(previousData.notes)
      )
      const tagsAdded = diffAddedItems(
        normalizeSnapshotArray(data.tags),
        normalizeSnapshotArray(previousData.tags)
      )
      const attachmentsAdded = diffAddedItems(
        normalizeSnapshotArray(data.attachments),
        normalizeSnapshotArray(previousData.attachments)
      )
      const ownersAdded = diffAddedItems(
        normalizeSnapshotArray(data.owners),
        normalizeSnapshotArray(previousData.owners)
      )
      const ownersRemoved = diffAddedItems(
        normalizeSnapshotArray(previousData.owners),
        normalizeSnapshotArray(data.owners)
      )

      const previousStatus = previousData.status
      const currentStatus = data.status
      if (currentStatus !== undefined && previousStatus !== undefined && currentStatus !== previousStatus) {
        items.push({
          id: `snapshot-${snapshot.id}-status`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Status updated by ${actorName}`,
          description: `${trimValue(previousStatus)} → ${trimValue(currentStatus)}`,
          icon: EditIcon,
          iconTone: 'neutral'
        })
      }

      notesAdded.forEach((note, noteIndex) => {
        items.push({
          id: `snapshot-${snapshot.id}-note-${snapshotKeyOf(note) ?? noteIndex}`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Note added by ${actorName}`,
          description: note?.content ? trimValue(note.content) : undefined,
          icon: MessageIcon,
          iconTone: 'info'
        })
      })

      tagsAdded.forEach((tag, tagIndex) => {
        items.push({
          id: `snapshot-${snapshot.id}-tag-${snapshotKeyOf(tag) ?? tagIndex}`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Tag added by ${actorName}`,
          description: tag?.name ? String(tag.name) : undefined,
          icon: StarIcon,
          iconTone: 'warning'
        })
      })

      attachmentsAdded.forEach((attachment, attachmentIndex) => {
        items.push({
          id: `snapshot-${snapshot.id}-attachment-${snapshotKeyOf(attachment) ?? attachmentIndex}`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Attachment added by ${actorName}`,
          description: attachment?.filename ? String(attachment.filename) : undefined,
          icon: FolderIcon,
          iconTone: 'neutral'
        })
      })

      if (ownersAdded.length > 0 || ownersRemoved.length > 0) {
        const addedNames = ownersAdded.map(ownerDisplayName).join(', ')
        const removedNames = ownersRemoved.map(ownerDisplayName).join(', ')
        let description = ''
        if (addedNames && removedNames) {
          description = `Added: ${addedNames} · Removed: ${removedNames}`
        } else if (addedNames) {
          description = `Added: ${addedNames}`
        } else if (removedNames) {
          description = `Removed: ${removedNames}`
        }

        items.push({
          id: `snapshot-${snapshot.id}-owners`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title: `Owner updated by ${actorName}`,
          description,
          icon: UserIcon,
          iconTone: 'info'
        })
      }

      const primitiveChanges = diffPrimitiveFields(data, previousData)
      const clientChanges = diffClientFields(data, previousData)
      const allChanges = [...primitiveChanges, ...clientChanges]

      if (allChanges.length > 0) {
        const title = allChanges.length === 1
          ? `${allChanges[0].label} updated by ${actorName}`
          : `${allChanges.length} fields updated by ${actorName}`

        const description = allChanges.length === 1
          ? `${trimValue(allChanges[0].from)} → ${trimValue(allChanges[0].to)}`
          : allChanges
            .slice(0, 4)
            .map((change) => change.label)
            .join(', ')

        items.push({
          id: `snapshot-${snapshot.id}-update`,
          createdAt,
          timeLabel: formatTimelineTime(createdAt),
          dateLabel: formatTimelineDate(createdAt),
          title,
          description,
          icon: EditIcon,
          iconTone: 'neutral'
        })
      }
    })

    safeFinancialEvents.forEach((event) => {
      const createdAt = event?.created_at ? new Date(event.created_at) : new Date()
      const actorName = event?.user?.name ?? 'System'
      const summary = (event?.summary ?? '').trim()
      const title = summary !== '' ? `${summary} by ${actorName}` : `Financial update by ${actorName}`
      const upperEventType = String(event?.event_type ?? '').toUpperCase()
      const iconTone: TimelineItem['iconTone'] = upperEventType.includes('VOID')
        ? 'warning'
        : upperEventType.includes('PAID') || upperEventType.includes('STATUS')
          ? 'success'
          : 'info'

      items.push({
        id: `financial-${event.id}`,
        createdAt,
        timeLabel: formatTimelineTime(createdAt),
        dateLabel: formatTimelineDate(createdAt),
        title,
        description: financialEventDetailText(event),
        icon: MoneyBagIcon,
        iconTone
      })
    })

    return items.sort((a, b) => b.createdAt.getTime() - a.createdAt.getTime())
  }, [safeSnapshots, safeFinancialEvents])

  const timelineGroups = useMemo(() => {
    const groups = new Map<string, TimelineItem[]>()
    timelineItems.forEach((item) => {
      if (!groups.has(item.dateLabel)) {
        groups.set(item.dateLabel, [])
      }
      groups.get(item.dateLabel)?.push(item)
    })

    return Array.from(groups.entries()).map(([dateLabel, items]) => ({
      dateLabel,
      items
    }))
  }, [timelineItems])

  const timelineToneClasses: Record<TimelineItem['iconTone'], string> = {
    neutral: 'border-slate-200 text-slate-500',
    info: 'border-sky-200 text-sky-500',
    success: 'border-emerald-200 text-emerald-500',
    warning: 'border-amber-200 text-amber-500'
  }

  useEffect(() => {
    setOrderFormInitialValues(loadOrderFormObj(order))
  }, [order])

  function submit (e: React.FormEvent<HTMLFormElement>) {
    e.preventDefault()
    // ruta PATCH para actualizar solo tags del pedido
    patch(route('frontdesk.tags_update', order.id), { preserveScroll: true })
  }
  useEffect(() => {
    const handleDocumentClick = (event: MouseEvent) => {
      if (!(event.target instanceof HTMLElement)) return
      if (!event.target.closest('[data-sales-status-picker]')) {
        closeStatusPicker()
      }
    }
    document.addEventListener('click', handleDocumentClick)
    return () => {
      document.removeEventListener('click', handleDocumentClick)
    }
  }, [closeStatusPicker])

  return (
    <AuthenticatedLayout
      auth={auth}
      pageTitle={`${order.name}${order.order_type ? ` (${order.order_type})` : ''}`}
    >
      <Head title="Order View" />

      <div className="px-4 pb-10 lg:px-6">
        <div className="space-y-6">
          <div className="panel flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
            <div className="flex items-start gap-4">
              <span className="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-sky-100 text-sky-600">
                <CrownIcon className="h-6 w-6" />
              </span>
              <div className="space-y-2">
                <div className="space-y-1">
                  <div className="flex flex-wrap items-center gap-3">
                    <h1 className="text-xl font-semibold text-slate-800">
                      {order.name}
                    </h1>
                    {isVipClient && (
                      <span className="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-wide text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/20 dark:text-rose-200 dark:ring-rose-400/40">
                        VIP
                      </span>
                    )}
                    {!canEditContact && (
                      <button
                        type="button"
                        onClick={openRequestModal}
                        className="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-sky-400 hover:text-sky-600"
                      >
                        Edit Request
                      </button>
                    )}
                    {canEditContact && !isFrontdeskEsrRole && (
                      <button
                        type="button"
                        onClick={openOrderEditModal}
                        className="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-slate-500 transition hover:border-sky-400 hover:text-sky-600"
                      >
                        Edit Order
                      </button>
                    )}
                  </div>
                  <div className="flex flex-wrap items-center gap-2 text-sm text-slate-500">
                    {order.order_type && (
                      <span className="inline-flex items-center gap-2 rounded-full bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-600">
                        {order.order_type}
                        {order.is_supply ? (
                          <span className="text-xs font-bold uppercase tracking-wide text-sky-600">(SUPPLY)</span>
                        ) : null}
                      </span>
                    )}
                    {primaryOwnerDisplay && (
                      <span className="inline-flex items-center gap-2 rounded-full bg-slate-50 px-3 py-1 text-xs font-medium text-slate-600">
                        <UserIcon className="h-4 w-4 text-slate-400" />
                        {primaryOwnerDisplay}
                      </span>
                    )}
                    {isCommercialOrder && commercialBidDueDateLabel && (
                      <span className="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-rose-200">
                        <span className="text-[10px] uppercase tracking-wide text-rose-600">Bid Due Date</span>
                        <span className="normal-case text-rose-800">{commercialBidDueDateLabel}</span>
                      </span>
                    )}
                    {scheduleAppointmentLabel && (
                      <span className="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600">
                        <CalendarIcon className="h-3.5 w-3.5 text-slate-400" />
                        {scheduleAppointmentLabel}
                      </span>
                    )}
                  </div>
                </div>
                {/* {order.notes && (
                    <p className="text-sm text-slate-500">
                      {order.notes}
                    </p>
                  )} */}
              </div>
            </div>
            <div className="flex flex-col items-start gap-2 md:items-end">
              <span className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-4 py-1.5 text-sm font-semibold text-sky-600">
                <DotsIcon className="h-4 w-4" />
                {order.status ?? 'No status'}
              </span>
              {showProjectAmount && (
                <span className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 md:text-right">
                  <span>Project Amount</span>
                  <span className="text-emerald-800">{formattedProjectAmount}</span>
                </span>
              )}
            </div>
          </div>

          {canViewPipeline && (
            <div className="panel space-y-4" data-sales-status-picker>
              <div>
                <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">{pipelineTitle}</h2>
                <p className="text-xs text-slate-500">{pipelineHint}</p>
              </div>

              {statusChangeError && (
                <p className="text-sm text-rose-600">{statusChangeError}</p>
              )}

              <div className="relative">
                <div className="pipeline-scroll overflow-x-auto pb-2">
                  <div className="flex min-w-max items-center gap-2">
                  {pipelineStatuses.map((status, index) => {
                    const isCompleted = currentStatusIndex > index
                    const isCurrent = currentStatusIndex === index
                    const isOpen = statusPickerAnchor?.status === status
                    const canOpenDropdown = canEditPipeline && (!orderInOrderStorageFlow
                      || (orderStorageTransitionsEnabled && isOrderStorageTransitionStatus(status))
                    )
                    return (
                      <div key={status} className="relative flex items-center gap-2">
                        <button
                          type="button"
                          className={[
                            `flex min-h-[2.35rem] ${pipelineButtonWidthClass} shrink-0 items-center justify-center gap-1.5 rounded-full border px-2 text-center text-[10px] font-semibold leading-tight transition focus:outline-none whitespace-normal`,
                            isCurrent
                              ? 'border-sky-500 bg-sky-500 text-white shadow'
                              : (isCompleted
                                  ? 'border-emerald-400 bg-emerald-50 text-emerald-600'
                                  : 'border-slate-200 bg-white text-slate-500')
                          ].join(' ')}
                          onClick={(event) => {
                            event.stopPropagation()
                            if (statusChangeSaving) return
                            if (!canOpenDropdown) return
                            const button = event.currentTarget as HTMLButtonElement
                            if (statusPickerAnchor?.status === status) {
                              closeStatusPicker()
                              return
                            }
                            setStatusPickerAnchor({ status, element: button })
                            updateStatusPickerPosition(button)
                          }}
                          disabled={statusChangeSaving || !canOpenDropdown}
                          title={status}
                        >
                          {isCompleted && <span>✓</span>}
                          <span>{status}</span>
                          {canOpenDropdown && <span className="text-xs">▾</span>}
                        </button>
                        {isOpen && statusPickerAnchor && statusPickerPosition && createPortal(
                          <div
                            className={`z-[9999] ${pipelineDropdownWidthClass} rounded-2xl border border-slate-200 bg-white shadow-2xl`}
                            style={{
                              position: 'fixed',
                              left: statusPickerPosition.left,
                              top: statusPickerPosition.top,
                              transform: 'translateX(-50%)'
                            }}
                            onClick={(event) => { event.stopPropagation() }}
                          >
                            <div className="border-b border-slate-100 px-3 py-2">
                              <input
                                type="text"
                                value={statusSearch}
                                onChange={(event) => { setStatusSearch(event.target.value) }}
                                placeholder="Search"
                                className="w-full rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-700 outline-none focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                              />
                            </div>
                            <div className="max-h-60 overflow-y-auto p-1">
                              {pipelineDropdownStatuses
                                .filter(option => option.toLowerCase().includes(statusSearch.toLowerCase()))
                                .map(option => (
                                  <button
                                    key={option}
                                    type="button"
                                    className={[
                                      'flex w-full items-center justify-between rounded-lg px-3 py-2 text-left text-sm',
                                      matchesStatus(option, pipelineStatusValue)
                                        ? 'bg-sky-50 text-sky-700'
                                        : 'text-slate-600 hover:bg-slate-50'
                                    ].join(' ')}
                                    onClick={(event) => {
                                      event.stopPropagation()
                                      handleStatusSelection(option)
                                    }}
                                  >
                                    <span>{option}</span>
                                    {matchesStatus(option, pipelineStatusValue) && <span className="text-xs">Current</span>}
                                  </button>
                                ))}
                            </div>
                          </div>,
                          document.body
                        )}
                        {index < pipelineStatuses.length - 1 && (
                          <div className={`h-0.5 w-7 shrink-0 ${currentStatusIndex > index ? 'bg-sky-400' : 'bg-slate-200'}`} />
                        )}
                      </div>
                    )
                  })}
                  </div>
                </div>
                <div className="pointer-events-none absolute inset-y-0 left-0 w-6 bg-gradient-to-r from-white/90 via-white/50 to-transparent" />
                <div className="pointer-events-none absolute inset-y-0 right-0 w-6 bg-gradient-to-l from-white/90 via-white/50 to-transparent" />
              </div>
            </div>
          )}

          <div className="grid gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">
            <aside className="space-y-6">
              {order.order_type?.toLowerCase() !== 'commercial' && (
                <div className="panel space-y-4">
                  <div className="flex items-center justify-between gap-3">
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Related Contact</h2>
                    <div className="flex items-center gap-3">
                      {canEditContact && !isFrontdeskEsrRole && (
                        <button
                          type="button"
                          onClick={openContactModal}
                          className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                          title="Edit contact"
                        >
                          <span className="sr-only">Edit contact</span>
                          <EditIcon className="h-4 w-4" />
                        </button>
                      )}
                    </div>
                  </div>
                  <div className="space-y-3">
                    {contactDetails.map(({ label, value, fallback, Icon }) => (
                      <div
                        key={label}
                        className="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-3 shadow-sm"
                      >
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                          <Icon className="h-4 w-4" />
                        </span>
                        <div className="flex-1 text-sm">
                          <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                          <p className="font-medium text-slate-700">
                            {value ?? <span className="text-slate-400">{fallback}</span>}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                  {!canEditContact && (
                    <p className="text-xs text-slate-400">
                      Contact details can only be edited once the client is confirmed, but you can still update the request information.
                    </p>
                  )}
                </div>
              )}

              {order.order_type?.toLowerCase() === 'commercial' && sortedOrderCompanyContacts.length > 0 && (
                <div className="mt-4 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50 p-3 shadow-sm">
                  <div className="flex items-center justify-between">
                    <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-400">Commercial Companies</h3>
                    <span className="text-[10px] font-medium text-slate-400">{sortedOrderCompanyContacts.length} linked</span>
                  </div>
                  <div className="space-y-3">
                    {sortedOrderCompanyContacts.map((item: any, index: number) => {
                      const isSelected = Boolean(item.is_selected)
                      return (
                      <div
                        key={item.id ?? index}
                        className={`rounded-xl border p-4 shadow-sm ${isSelected ? 'border-emerald-300/80 bg-emerald-50/70' : 'border-slate-200/70 bg-white'}`}
                      >
                        <div className="space-y-2">
                          <div className="min-w-0">
                            <div className="flex items-start gap-2">
                              <p className="truncate text-base font-semibold text-slate-800">
                                {item.company_contact?.name ?? item.companyContact?.name ?? 'Company'}
                              </p>
                              {(item.company_contact?.id || item.companyContact?.id) && (
                                <button
                                  type="button"
                                  onClick={() => { openCompanyEditModal(item.company_contact ?? item.companyContact) }}
                                  className="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                  title="Edit company"
                                  aria-label="Edit company"
                                >
                                  <EditIcon className="h-3 w-3" />
                                </button>
                              )}
                            </div>
                            <p className="text-xs text-slate-500">
                              Company
                              {isSelected && <span className="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Selected</span>}
                            </p>
                          </div>
                          <div className="flex flex-wrap items-center gap-2" />
                        </div>

                        <div className="mt-3 space-y-3 text-xs text-slate-500">
                          <div className="rounded-lg bg-slate-50 px-3 py-2">
                            <div className="flex items-center gap-2">
                              <span className="uppercase tracking-wide text-slate-400">Contact</span>
                              {item.client?.id && (
                                <Link
                                  href="#"
                                  onClick={(event) => {
                                    event.preventDefault()
                                    openCommercialContactModal(item.client)
                                  }}
                                  className="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                                  title="Edit contact"
                                  aria-label="Edit contact"
                                >
                                  <EditIcon className="h-3 w-3" />
                                </Link>
                              )}
                            </div>
                            <div className="mt-0.5 font-medium text-slate-700">
                              {item.client?.name ?? 'Unassigned'}
                            </div>
                            <div className="mt-1 text-slate-500">
                              Phone:{' '}
                              <span className="font-medium text-slate-600">{item.client?.phone ?? '—'}</span>
                            </div>
                          </div>
                          <div className="rounded-lg bg-slate-50 px-3 py-2">
                            <span className="uppercase tracking-wide text-slate-400">Email</span>
                            <div className="mt-0.5 break-all font-medium text-slate-700">
                              {item.client?.email ?? '—'}
                            </div>
                          </div>
                          <div className="rounded-lg bg-slate-100 px-3 py-2">
                            <span className="uppercase tracking-wide text-slate-400">Source</span>
                            <div className="mt-0.5 font-medium text-slate-700">{item.source?.name ?? '—'}</div>
                          </div>
                        </div>
                      </div>
                      )
                    })}
                  </div>
                </div>
              )}

              {order.order_type?.toLowerCase() !== 'commercial' && companyContacts.length > 0 && (
                <div className="mt-4 space-y-3 rounded-xl border border-slate-200/80 bg-slate-50 p-3 shadow-sm">
                  <div className="flex items-center justify-between">
                    <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-400">Related Company</h3>
                    <span className="text-[10px] font-medium text-slate-400">{companyContacts.length} linked</span>
                  </div>
                  <div className="space-y-3">
                    {companyContacts.map((company, index) => (
                      <div key={company.id ?? index} className="space-y-2 rounded-lg bg-white/70 p-3 shadow">
                        <p className="text-sm font-semibold text-slate-700">{company.name}</p>
                        {company.bid_due_date && (
                          <div className="flex items-center justify-between rounded-md bg-slate-100 px-3 py-1.5 text-xs font-semibold uppercase tracking-wide text-slate-500">
                            <span>Bid Due Date</span>
                            <span className="text-slate-700 normal-case">
                              {new Date(company.bid_due_date).toLocaleDateString()}
                            </span>
                          </div>
                        )}
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Sales timeline component will render elsewhere */}

              {!isFrontdeskEsrRole && (
                <form onSubmit={submit} className="panel space-y-4">
                  <div className="flex items-center justify-between">
                    <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Tags</h2>
                    <span className="text-xs text-slate-400">{selectedTagCount} selected</span>
                  </div>
                  <TagPicker
                    value={data.tags}
                    onChange={(t) => { setData('tags', t) }}
                    placeholder="Agregar tag"
                    suggestions={safeUsedTags}
                  />
                  <div className="flex justify-end">
                    <button
                      type="submit"
                      disabled={processing}
                      className="btn btn-sm btn-primary disabled:opacity-60"
                    >
                      {processing
                        ? (
                          <svg viewBox="0 0 24 24" className="h-4 w-4 animate-spin" fill="none">
                            <circle cx="12" cy="12" r="9" stroke="currentColor" strokeOpacity="0.2" strokeWidth="3" />
                            <path d="M21 12a9 9 0 0 1-9 9" stroke="currentColor" strokeWidth="3" />
                          </svg>
                          )
                        : (
                          <span className="flex items-center gap-2">
                            Guardar
                            <svg viewBox="0 0 20 20" className="h-4 w-4" fill="currentColor" aria-hidden="true">
                              <path d="M8.5 13.5 4.9 10l1.2-1.2 2.4 2.3 5-5L15.7 7l-6 6.5z" />
                            </svg>
                          </span>
                        )}
                    </button>
                  </div>
                </form>
              )}

              {isLostRequest && (
                <div className="panel space-y-3">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Loss Reason</h2>
                  {lossReasonFrontdeskValue
                    ? <p className="text-sm leading-relaxed text-slate-600">{lossReasonFrontdeskValue}</p>
                    : <p className="text-sm text-slate-400">No loss reason recorded.</p>}
                </div>
              )}

              {!shouldHideDescriptionAndJobInfo && (
                <div className="panel space-y-3">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Description</h2>
                  {descriptionText
                    ? <p className="text-sm leading-relaxed text-slate-600">{descriptionText}</p>
                    : <p className="text-sm text-slate-400">No description available.</p>}
                </div>
              )}

              {!shouldHideDescriptionAndJobInfo && (
                <div className="panel space-y-3">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Job Site</h2>
                  {jobLocation
                    ? (
                      <div className="flex items-start gap-3 text-sm text-slate-600">
                        <span className="mt-1 text-sky-500">
                          <LocationIcon className="h-5 w-5" />
                        </span>
                        <span>{jobLocation}</span>
                      </div>
                      )
                    : (
                      <p className="text-sm text-slate-400">No job site information provided.</p>
                      )}
                </div>
              )}

              <div className="panel space-y-3">
                <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Source</h2>
                {sourceName
                  ? (
                    <div className="flex items-center gap-3 text-sm text-slate-600">
                      <span className="text-sky-500">
                        <ShareIcon className="h-5 w-5" />
                      </span>
                      <span>{sourceName}</span>
                    </div>
                    )
                  : (
                    <p className="text-sm text-slate-400">No source recorded.</p>
                    )}
              </div>

              {isReferralSource && (
                <div className="panel space-y-3">
                  <h2 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Referred By</h2>
                  <div className="space-y-3">
                    {referralDetails.map(({ label, value, fallback, Icon }) => (
                      <div
                        key={label}
                        className="flex items-center gap-3 rounded-xl border border-slate-200/80 bg-slate-50 px-3 py-3 shadow-sm"
                      >
                        <span className="inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                          <Icon className="h-4 w-4" />
                        </span>
                        <div className="flex-1 text-sm">
                          <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                          <p className="font-medium text-slate-700">
                            {value ?? <span className="text-slate-400">{fallback}</span>}
                          </p>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              )}

            </aside>

            <section className="panel flex h-full flex-col gap-5">
              <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                  <h2 className="text-base font-semibold text-slate-800">Order Activity</h2>
                  <p className="text-sm text-slate-400">Notes, history, and key order details.</p>
                </div>
              </div>

              <ul
                role="tablist"
                aria-label="Order detail tabs"
                className="flex flex-wrap gap-2 rounded-xl bg-slate-50 p-1"
                onKeyDown={onKeyDown}
              >
                {tabs.map(({ key, label, Icon }) => {
                  const active = tab === key
                  const displayLabel = key === 'contact'
                    ? `${label} (${associatedOrdersCount})`
                    : (key === 'attachments' ? `${label} (${attachments.length})` : label)
                  return (
                    <li key={key}>
                      <button
                        id={`tab-${key}`}
                        role="tab"
                        aria-selected={active}
                        aria-controls={`panel-${key}`}
                        className={[
                          'inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition-all duration-150',
                          active
                            ? 'bg-white text-sky-600 shadow-sm'
                            : 'text-slate-500 hover:bg-white/70 hover:text-slate-700'
                        ].join(' ')}
                        onClick={() => { setTab(key) }}
                      >
                        <Icon className="h-4 w-4" aria-hidden="true" />
                        {displayLabel}
                      </button>
                    </li>
                  )
                })}
              </ul>

              <div className="flex-1 overflow-hidden rounded-xl border border-slate-200/70 bg-white">
                {tab === 'home' && (
                  <div id="panel-home" role="tabpanel" aria-labelledby="tab-home" className="h-full">
                    <OrderNotesForOrder orderId={order.id} canCreate refreshKey={activityRefreshKey} />
                  </div>
                )}

                {tab === 'profile' && (
                  <div id="panel-profile" role="tabpanel" aria-labelledby="tab-profile" className="space-y-6 p-6 text-sm text-slate-600">
                    <div>
                      <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">History</h3>
                      {timelineGroups.length > 0
                        ? (
                          <div className="mt-4 space-y-6">
                            {timelineGroups.map((group) => (
                              <div key={group.dateLabel} className="space-y-4">
                                <div className="flex items-center gap-3 text-xs font-semibold uppercase tracking-wide text-slate-400">
                                  <span className="h-2 w-2 rounded-full bg-emerald-500" />
                                  <span>{group.dateLabel}</span>
                                </div>
                                <div className="space-y-4">
                                  {group.items.map((item, index) => {
                                    const Icon = item.icon
                                    const isLast = index === group.items.length - 1
                                    return (
                                      <div key={item.id} className="relative flex gap-4 pb-5">
                                        <div className="w-20 text-right text-[11px] font-medium text-slate-400">{item.timeLabel}</div>
                                        <div className="relative flex flex-col items-center">
                                          <span className={`relative z-10 flex h-9 w-9 items-center justify-center rounded-full border bg-white shadow-sm ${timelineToneClasses[item.iconTone]}`}>
                                            <Icon />
                                          </span>
                                          {!isLast && (
                                            <span className="absolute left-1/2 top-9 h-full w-px -translate-x-1/2 bg-slate-200" />
                                          )}
                                        </div>
                                        <div className="flex-1 space-y-1">
                                          <p className="text-sm font-semibold text-slate-700">{item.title}</p>
                                          {item.description && (
                                            <p className="text-xs text-slate-500">{item.description}</p>
                                          )}
                                        </div>
                                      </div>
                                    )
                                  })}
                                </div>
                              </div>
                            ))}
                          </div>
                          )
                        : (
                          <p className="mt-3 text-sm text-slate-400">No history recorded for this order.</p>
                          )}
                    </div>

                    <div>
                      <h3 className="text-sm font-semibold uppercase tracking-wide text-slate-400">Status History</h3>
                      {statusCount > 0
                        ? (
                          <ul className="mt-4 space-y-3">
                            {safeOrderStatuses.map((status) => (
                              <li key={status.id} className="rounded-xl border border-slate-200/70 bg-white px-4 py-3 shadow-sm">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                  <span className="text-sm font-semibold text-slate-700">{status.status}</span>
                                  <span className="text-xs text-slate-400">{status.created_at_formatted}</span>
                                </div>
                              </li>
                            ))}
                          </ul>
                          )
                        : (
                          <p className="mt-3 text-sm text-slate-400">No status history recorded for this order.</p>
                          )}
                    </div>
                  </div>
                )}

                {tab === 'contact' && (
                  <div id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" className="space-y-5 p-6 text-sm text-slate-600">
                    <p className="text-sm text-slate-500">
                      Keep the team aligned by reviewing this client's contact details and associated orders in one place.
                    </p>
                    <div className="grid gap-4 sm:grid-cols-2">
                      {contactDetails.map(({ label, value, fallback, Icon }) => (
                        <div key={label} className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3">
                          <div className="flex items-center gap-3">
                            <span className="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-sky-500 shadow-sm">
                              <Icon className="h-4 w-4" />
                            </span>
                            <div>
                              <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                              <p className="text-sm font-medium text-slate-700">{value ?? <span className="text-slate-400">{fallback}</span>}</p>
                            </div>
                          </div>
                        </div>
                      ))}
                    </div>
                    <div className="space-y-3">
                      <div className="flex items-center justify-between">
                        <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-400">Other orders linked to this contact</h3>
                        {relatedClientOrders.length > 0 && (
                          <span className="text-[11px] font-medium text-slate-500">{relatedClientOrders.length} linked</span>
                        )}
                      </div>
                      {relatedClientOrders.length > 0
                        ? (
                          <ul className="space-y-3">
                            {relatedClientOrders.map((clientOrder) => {
                              const ownerNames = Array.isArray(clientOrder.owners)
                                ? clientOrder.owners
                                  .map(owner => owner?.name)
                                  .filter((name): name is string => Boolean(name))
                                : []
                              return (
                                <li key={clientOrder.id} className="rounded-xl border border-slate-200/70 bg-white px-4 py-3 shadow-sm">
                                  <div className="flex flex-col gap-2">
                                    <div className="flex flex-wrap items-center justify-between gap-2">
                                      <div>
                                        <p className="text-sm font-semibold text-slate-700">
                                          {clientOrder.name}
                                        </p>
                                        <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                          {clientOrder.order_number && (
                                            <span>#{clientOrder.order_number}</span>
                                          )}
                                          {clientOrder.order_type && (
                                            <span>{clientOrder.order_type}</span>
                                          )}
                                          {ownerNames.length > 0 && (
                                            <span className="rounded-full bg-sky-50 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-sky-600">
                                              {ownerNames.length > 1 ? 'Owners' : 'Owner'}: {ownerNames.join(', ')}
                                            </span>
                                          )}
                                        </div>
                                      </div>
                                      {clientOrder.status && (
                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-slate-600">
                                          {clientOrder.status}
                                        </span>
                                      )}
                                    </div>
                                  <div className="flex flex-wrap items-center justify-between gap-2 text-xs">
                                    <a
                                      href={route('frontdesk.order_view', { id: clientOrder.id })}
                                      className="font-semibold text-sky-600 hover:text-sky-700"
                                    >
                                      View details
                                    </a>
                                  </div>
                                  {clientOrder.order_type?.toLowerCase() === 'commercial' && Array.isArray((clientOrder as any).order_company_contacts) && (clientOrder as any).order_company_contacts.length > 0 && (
                                    <div className="mt-3 space-y-2">
                                      <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Companies</p>
                                      <div className="flex flex-wrap gap-2">
                                        {(clientOrder as any).order_company_contacts.map((item: any) => (
                                          <span
                                            key={item.id}
                                            className={`rounded-full px-2 py-1 text-[10px] font-semibold uppercase tracking-wide ${item.is_selected ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}
                                          >
                                            {item.company_name ?? 'Company'}
                                            {item.client_name ? ` · ${item.client_name}` : ''}
                                          </span>
                                        ))}
                                      </div>
                                    </div>
                                  )}
                                </div>
                              </li>
                            )
                          })}
                          </ul>
                          )
                        : (
                          <p className="rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                            No hay otras órdenes asociadas a este cliente.
                          </p>
                          )}
                    </div>
                  </div>
                )}

                {tab === 'sales' && (
                  <div id="panel-sales" role="tabpanel" aria-labelledby="tab-sales" className="space-y-5 p-6 text-sm text-slate-600">
                    {order.sale_form
                      ? (
                        <>
                          <div className="flex items-center justify-between">
                            <p className="text-sm text-slate-500">View and download the sales form generated for this order.</p>
                            <a
                              href={route('frontdesk.order.sale_form', { order: order.id, download: 1 })}
                              className="rounded-lg bg-sky-600 px-3 py-1.5 text-xs font-semibold text-white shadow hover:bg-sky-700"
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              Download PDF
                            </a>
                          </div>
                          <div className="overflow-hidden rounded-lg border border-slate-200">
                            <object
                              data={route('frontdesk.order.sale_form', { order: order.id })}
                              type="application/pdf"
                              className="h-[600px] w-full"
                            >
                              <p className="p-4 text-sm text-slate-500">
                                No pudimos mostrar el PDF incrustado. Puedes <a className="text-sky-600 underline" href={route('frontdesk.order.sale_form', { order: order.id, download: 1 })} target="_blank" rel="noopener noreferrer">descargarlo aquí</a>.
                              </p>
                            </object>
                          </div>
                        </>
                        )
                      : (
                        <p className="text-sm text-slate-400">No sale form attachments available for this order.</p>
                    )}
                  </div>
                )}

                {tab === 'payments' && (
                  <div id="panel-payments" role="tabpanel" aria-labelledby="tab-payments" className="space-y-5 p-6 text-sm text-slate-600">
                    {hasFinancingPaymentMethod && (
                      <div className="rounded-xl border border-sky-200 bg-sky-50 px-4 py-3">
                        <div className={`grid gap-3 ${isCashAndFinancedPaymentMethod ? 'md:grid-cols-4' : 'md:grid-cols-2'}`}>
                          <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Project Payment Method</p>
                            <p className="text-sm font-semibold text-slate-700">{paymentMethodLabel || 'FINANCED'}</p>
                          </div>
                          <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Type of Financing</p>
                            <p className="text-sm font-semibold text-slate-700">{financingTypeLabel}</p>
                          </div>
                          {isCashAndFinancedPaymentMethod && (
                            <div>
                              <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Cash Amount</p>
                              <p className="text-sm font-semibold text-slate-700">{cashAmountLabel}</p>
                            </div>
                          )}
                          {isCashAndFinancedPaymentMethod && (
                            <div>
                              <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Amount to Finance</p>
                              <p className="text-sm font-semibold text-slate-700">{amountToFinanceLabel}</p>
                            </div>
                          )}
                        </div>
                      </div>
                    )}
                    {paymentSchedule
                      ? (
                        <div className="space-y-4">
                          <div className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                              <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Schedule Type</p>
                                <p className="text-sm font-semibold text-slate-700">{paymentSchedule.schedule_type}</p>
                              </div>
                              <div className="text-right">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">{isCashAndFinancedPaymentMethod ? 'Cash Schedule Total' : 'Total'}</p>
                                <p className="text-sm font-semibold text-slate-700">
                                  {formatScheduleCurrency(Number(paymentSchedule.total_amount ?? 0))}
                                </p>
                              </div>
                            </div>
                          </div>

                          <div className="grid gap-3 md:grid-cols-4">
                            <div className="rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">
                              <p className="text-xs font-semibold uppercase tracking-wide text-emerald-600">Paid</p>
                              <p className="text-sm font-semibold text-emerald-700">
                                {formatScheduleCurrency(paymentPaidAmount)}
                              </p>
                            </div>
                            <div className="rounded-xl border border-amber-100 bg-amber-50 px-4 py-3">
                              <p className="text-xs font-semibold uppercase tracking-wide text-amber-600">Remaining</p>
                              <p className="text-sm font-semibold text-amber-700">
                                {formatScheduleCurrency(paymentRemainingAmount)}
                              </p>
                            </div>
                            <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                              <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Total</p>
                              <p className="text-sm font-semibold text-slate-700">
                                {formatScheduleCurrency(paymentTotalAmount)}
                              </p>
                            </div>
                            <div className="rounded-xl border border-sky-100 bg-sky-50 px-4 py-3">
                              <p className="text-xs font-semibold uppercase tracking-wide text-sky-600">Credit</p>
                              <p className="text-sm font-semibold text-sky-700">
                                {formatScheduleCurrency(paymentCreditAmount)}
                              </p>
                            </div>
                          </div>
                          <p className="text-xs text-slate-500">
                            Total paid is capped to the schedule total. No final total credit is allowed.
                          </p>

                          {paymentError && (
                            <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                              {paymentError}
                            </div>
                          )}

                          {paymentInstallments.length > 0
                            ? (
                              <div className="space-y-4">
                                {paymentInstallments.map((installment) => {
                                  const dueDateValue = paymentEdits[installment.id]?.dueDate ?? installment.due_date ?? ''
                                  const movements = Array.isArray(installment.movements) ? installment.movements : []
                                  const movementDraft = movementDrafts[installment.id] ?? emptyMovementFormValues()
                                  const scheduledAmount = Number(installment.amount ?? 0)
                                  const paidAmount = Number(installment.paid_amount ?? 0)
                                  const remainingAmount = Number(installment.balance ?? Math.max(0, scheduledAmount - paidAmount))
                                  const defaultAmount = Math.min(remainingAmount > 0 ? remainingAmount : scheduledAmount, scheduleRemainingCapacity)
                                  const progress = scheduledAmount > 0
                                    ? Math.min(100, Math.max(0, Math.round((paidAmount / scheduledAmount) * 100)))
                                    : 0
                                  const saving = paymentSavingId === installment.id

                                  return (
                                    <div key={installment.id} className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                      <div className="flex flex-wrap items-center justify-between gap-3">
                                        <div>
                                          <p className="text-base font-semibold text-slate-800">{installment.label}</p>
                                          <p className="text-xs text-slate-500">{Number(installment.percentage ?? 0).toFixed(2)}% of schedule</p>
                                        </div>
                                        <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                          {installment.status ?? 'PENDING'}
                                        </span>
                                      </div>

                                      <div className="mt-3 h-2 overflow-hidden rounded-full bg-slate-100">
                                        <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${progress}%` }} />
                                      </div>

                                      <div className="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        <div className="rounded-lg border border-slate-200 bg-slate-50 p-2">
                                          <p className="text-[11px] uppercase text-slate-400">Scheduled</p>
                                          <p className="text-sm font-semibold text-slate-700">{formatScheduleCurrency(scheduledAmount)}</p>
                                        </div>
                                        <div className="rounded-lg border border-emerald-100 bg-emerald-50 p-2">
                                          <p className="text-[11px] uppercase text-emerald-600">Paid</p>
                                          <p className="text-sm font-semibold text-emerald-700">{formatScheduleCurrency(paidAmount)}</p>
                                        </div>
                                        <div className="rounded-lg border border-amber-100 bg-amber-50 p-2">
                                          <p className="text-[11px] uppercase text-amber-600">Balance</p>
                                          <p className="text-sm font-semibold text-amber-700">{formatScheduleCurrency(Number(installment.balance ?? 0))}</p>
                                        </div>
                                        <div className="rounded-lg border border-sky-100 bg-sky-50 p-2">
                                          <p className="text-[11px] uppercase text-sky-600">Credit</p>
                                          <p className="text-sm font-semibold text-sky-700">{formatScheduleCurrency(Number(installment.credit ?? 0))}</p>
                                        </div>
                                      </div>

                                      <div className="mt-4 grid gap-2 lg:grid-cols-[220px_1fr_auto]">
                                        <input
                                          type="date"
                                          value={dueDateValue}
                                          onChange={(event) => { handleInstallmentFieldChange(installment.id, 'dueDate', event.target.value) }}
                                          className="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                        />
                                        <div className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
                                          Last payment: {formatPaidAt(installment.paid_at)} by {installment.paid_by?.name ?? '-'}
                                        </div>
                                        <button
                                          type="button"
                                          onClick={() => { handleInstallmentSave(installment.id, installment.due_date ?? null) }}
                                          className="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-sky-400 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                          disabled={saving}
                                        >
                                          {saving ? 'Saving...' : 'Save due date'}
                                        </button>
                                      </div>

                                      <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 p-3">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Register Payment</p>
                                        <div className="mt-2 grid gap-2 md:grid-cols-4">
                                          <select
                                            value={movementDraft.useDefaultAmount ? 'default' : 'custom'}
                                            onChange={(event) => { handleMovementDraftChange(installment.id, 'useDefaultAmount', event.target.value === 'default') }}
                                            className="rounded-lg border border-slate-200 px-2 py-2 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                          >
                                            <option value="default">Default amount</option>
                                            <option value="custom">Custom amount</option>
                                          </select>
                                          <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={movementDraft.useDefaultAmount ? String(round2(defaultAmount)) : movementDraft.amount}
                                            onChange={(event) => { handleMovementDraftChange(installment.id, 'amount', event.target.value) }}
                                            className="rounded-lg border border-slate-200 px-2 py-2 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                            placeholder={movementDraft.useDefaultAmount ? 'Using default' : 'Amount'}
                                            disabled={movementDraft.useDefaultAmount}
                                          />
                                          <input
                                            type="text"
                                            value={movementDraft.note}
                                            onChange={(event) => { handleMovementDraftChange(installment.id, 'note', event.target.value) }}
                                            className="rounded-lg border border-slate-200 px-2 py-2 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                            placeholder="Note (optional)"
                                          />
                                          <button
                                            type="button"
                                            onClick={() => { handleMovementCreate(installment) }}
                                            className="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 hover:border-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                                            disabled={movementSavingKey === `create-${installment.id}`}
                                          >
                                            {movementSavingKey === `create-${installment.id}` ? 'Saving...' : 'Add payment'}
                                          </button>
                                        </div>
                                        <p className="mt-2 text-[11px] text-slate-500">
                                          Default amount now: {formatScheduleCurrency(defaultAmount)}
                                        </p>
                                      </div>

                                      <div className="mt-4">
                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Payment History</p>
                                        {movements.length > 0
                                          ? (
                                            <div className="mt-2 space-y-2">
                                              {movements.map((movement) => {
                                                const hasEdit = Object.prototype.hasOwnProperty.call(movementEdits, movement.id)
                                                const edited = hasEdit
                                                  ? movementEdits[movement.id]
                                                  : {
                                                      useDefaultAmount: false,
                                                      amount: String(movement.amount ?? ''),
                                                      note: movement.note ?? ''
                                                    }
                                                return (
                                                  <div key={movement.id} className="rounded-lg border border-slate-200 bg-white p-3">
                                                    <div className="grid gap-2 md:grid-cols-3">
                                                      <input
                                                        type="number"
                                                        step="0.01"
                                                        min="0"
                                                        value={edited.amount}
                                                        onChange={(event) => { handleMovementEditChange(movement, 'amount', event.target.value) }}
                                                        className="rounded-lg border border-slate-200 px-2 py-2 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                                      />
                                                      <input
                                                        type="text"
                                                        value={edited.note}
                                                        onChange={(event) => { handleMovementEditChange(movement, 'note', event.target.value) }}
                                                        className="rounded-lg border border-slate-200 px-2 py-2 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                                                        placeholder="Note (optional)"
                                                      />
                                                      <div className="flex items-center justify-end gap-2">
                                                        <button
                                                          type="button"
                                                          onClick={() => { handleMovementUpdate(movement) }}
                                                          className="rounded border border-slate-200 px-2 py-1.5 text-[11px] font-semibold text-slate-600 hover:border-sky-400 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                                                          disabled={movementSavingKey === `update-${movement.id}`}
                                                        >
                                                          {movementSavingKey === `update-${movement.id}` ? 'Updating...' : 'Update'}
                                                        </button>
                                                        <button
                                                          type="button"
                                                          onClick={() => { handleMovementVoid(movement.id, installment.id) }}
                                                          className="rounded border border-rose-200 px-2 py-1.5 text-[11px] font-semibold text-rose-600 hover:border-rose-300 disabled:cursor-not-allowed disabled:opacity-60"
                                                          disabled={movementSavingKey === `void-${movement.id}`}
                                                        >
                                                          {movementSavingKey === `void-${movement.id}` ? 'Deleting...' : 'Delete'}
                                                        </button>
                                                      </div>
                                                    </div>
                                                    <p className="mt-2 text-[11px] text-slate-500">
                                                      Recorded by {movement.paid_by?.name ?? '-'} on {formatPaidAt(movement.paid_at)}
                                                    </p>
                                                  </div>
                                                )
                                              })}
                                            </div>
                                            )
                                          : (
                                            <p className="mt-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500">
                                              No payments recorded yet for this installment.
                                            </p>
                                            )}
                                      </div>
                                    </div>
                                  )
                                })}
                              </div>
                              )
                            : (
                              <p className="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
                                No payment installments recorded for this order.
                              </p>
                              )}
                        </div>
                        )
                      : (
                        <p className="text-sm text-slate-400">No payment schedule has been created for this order.</p>
                        )}
                    {changeOrderPayment && (
                      <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                          <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Change Order Payment</p>
                            <p className="text-sm font-semibold text-slate-700">
                              {changeOrderPayment.note || 'Change Order'}
                            </p>
                          </div>
                          <div className="text-right">
                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Amount</p>
                            <p className="text-sm font-semibold text-slate-700">
                              {formatScheduleCurrency(Number(changeOrderPayment.amount ?? 0))}
                            </p>
                          </div>
                        </div>
                        {changeOrderError && (
                          <div className="mt-3 rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                            {changeOrderError}
                          </div>
                        )}
                        <div className="mt-3 flex flex-wrap gap-4 text-xs text-slate-500">
                          <div>
                            <span className="font-semibold text-slate-600">Status:</span>{' '}
                            <select
                              value={changeOrderStatus}
                              onChange={(event) => { setChangeOrderStatus(event.target.value) }}
                              className="ml-2 rounded-lg border border-slate-200 px-2 py-1 text-xs text-slate-700 focus:border-sky-400 focus:ring-2 focus:ring-sky-100"
                            >
                              <option value="PENDING">PENDING</option>
                              <option value="PAID">PAID</option>
                            </select>
                          </div>
                          <div>
                            <span className="font-semibold text-slate-600">Paid at:</span>{' '}
                            {formatPaidAt(changeOrderPayment.paid_at)}
                          </div>
                          <div>
                            <span className="font-semibold text-slate-600">Paid by:</span>{' '}
                            {changeOrderPayment.paid_by?.name ?? '-'}
                          </div>
                          <div>
                            <button
                              type="button"
                              onClick={handleChangeOrderSave}
                              className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:border-sky-400 hover:text-sky-700 disabled:cursor-not-allowed disabled:opacity-60"
                              disabled={changeOrderSaving}
                            >
                              {changeOrderSaving ? 'Saving...' : 'Save'}
                            </button>
                          </div>
                        </div>
                      </div>
                    )}
                    <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                      <div className="flex items-center justify-between gap-3">
                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-400">Financial History</p>
                        <span className="text-xs text-slate-500">{safeFinancialEvents.length} events</span>
                      </div>
                      {safeFinancialEvents.length > 0
                        ? (
                          <div className="mt-3 space-y-2">
                            {safeFinancialEvents.map((event) => (
                              <div key={event.id} className="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                                <div className="flex flex-wrap items-center justify-between gap-2">
                                  <p className="text-xs font-semibold text-slate-700">{event.summary}</p>
                                  <span className="text-[11px] text-slate-500">{formatPaidAt(event.created_at)}</span>
                                </div>
                                <p className="mt-1 text-[11px] text-slate-500">
                                  By {event.user?.name ?? 'System'}
                                  {event.event_type ? ` • ${event.event_type}` : ''}
                                </p>
                                {financialEventDetailText(event) && (
                                  <p className="mt-1 text-[11px] text-slate-600">
                                    {financialEventDetailText(event)}
                                  </p>
                                )}
                              </div>
                            ))}
                          </div>
                          )
                        : (
                          <p className="mt-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-500">
                            No financial events recorded yet.
                          </p>
                          )}
                    </div>
                  </div>
                )}

                {tab === 'attachments' && (
                  <div id="panel-attachments" role="tabpanel" aria-labelledby="tab-attachments" className="space-y-5 p-6 text-sm text-slate-600">
                    <form onSubmit={handleAttachmentUpload} className="space-y-3 rounded-xl border border-dashed border-slate-200 bg-slate-50 p-4">
                      <div>
                        <label htmlFor="order-attachments" className="text-xs font-semibold uppercase tracking-wide text-slate-500">Agregar archivos</label>
                        <input
                          id="order-attachments"
                          ref={fileInputRef}
                          type="file"
                          multiple
                          onChange={handleFileSelection}
                          className="mt-2 block w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:cursor-pointer file:rounded-md file:border-0 file:bg-sky-600 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white hover:border-sky-300 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100 disabled:cursor-not-allowed"
                          disabled={uploading}
                        />
                      </div>

                      {newFiles.length > 0 && (
                        <ul className="space-y-1 text-xs text-slate-500">
                          {newFiles.map(file => (
                            <li key={`${file.name}-${file.lastModified}`}>{file.name}</li>
                          ))}
                        </ul>
                      )}

                      {uploadError && (
                        <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                          {uploadError}
                        </div>
                      )}

                      <div className="flex justify-end">
                        <button
                          type="submit"
                          className="inline-flex items-center gap-2 rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                          disabled={uploading}
                        >
                          {uploading ? 'Subiendo…' : 'Subir archivos'}
                        </button>
                      </div>
                    </form>

                    {deleteError && (
                      <div className="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-600">
                        {deleteError}
                      </div>
                    )}

                    {attachments.length > 0
                      ? (
                        <ul className="space-y-3">
                          {attachments.map((attachment) => {
                            const isDeleting = deletingIds.includes(attachment.id)
                            const createdAtValue = attachment.created_at ?? (attachment as any)?.created_at
                            const createdAtLabel = createdAtValue
                              ? attachmentDateFormatter.current?.format(new Date(createdAtValue)) ?? new Date(createdAtValue).toLocaleString()
                              : null
                            const uploaderName = attachment.uploaded_by ?? (attachment as any)?.user?.name ?? null
                            const uploadedByLabel = uploaderName ?? 'Usuario desconocido'
                            const canDeleteAttachment = (
                              (authUserId !== null && attachment.user_id === authUserId) ||
                              isAdmin(roleNames) ||
                              isAccountManager(roleNames) ||
                              isOwnerAdmin(roleNames)
                            )
                            return (
                              <li key={attachment.id} className="flex items-center justify-between gap-3 rounded-xl border border-slate-200/70 bg-slate-50 px-4 py-3 shadow-sm">
                                <div>
                                  <p className="text-sm font-semibold text-slate-700">{attachment.filename}</p>
                                  {(createdAtLabel || uploaderName) && (
                                    <div className="mt-1 text-xs text-slate-400">
                                      {createdAtLabel && (
                                        <div>
                                          <span>{createdAtLabel}</span>
                                          {uploadedByLabel && (
                                            <>
                                              <span> By</span>
                                              <br />
                                              <span className="text-slate-500">{uploadedByLabel}</span>
                                            </>
                                          )}
                                        </div>
                                      )}
                                      {!createdAtLabel && uploadedByLabel && (
                                        <div>
                                          <span>By</span>
                                          <br />
                                          <span className="text-slate-500">{uploadedByLabel}</span>
                                        </div>
                                      )}
                                    </div>
                                  )}
                                </div>
                                <div className="flex items-center gap-2">
                                  <a
                                    href={route('download.file', { id: attachment.id })}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-sky-100 text-sky-600 hover:bg-sky-200"
                                  >
                                    <ExportIcon className="h-4 w-4" />
                                  </a>
                                  <button
                                    type="button"
                                    onClick={() => { handleAttachmentDelete(attachment.id) }}
                                    className="inline-flex h-8 w-8 items-center justify-center rounded-full bg-rose-50 text-rose-600 hover:bg-rose-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    disabled={isDeleting || !canDeleteAttachment}
                                    aria-label="Eliminar adjunto"
                                    title={!canDeleteAttachment ? 'Solo puedes eliminar archivos que subiste o tener rol autorizado' : 'Eliminar adjunto'}
                                  >
                                    <DeleteIcon className="h-4 w-4" />
                                  </button>
                                </div>
                              </li>
                            )
                          })}
                        </ul>
                        )
                      : (
                        <p className="text-sm text-slate-400">No hay archivos adjuntos para esta orden.</p>
                        )}
                  </div>
                )}
              </div>
            </section>
      </div>
    </div>
  </div>
      <OrderEditModal
        open={orderEditModalOpen}
        initialValues={orderFormInitialValues}
        onClose={() => { setOrderEditModalOpen(false) }}
        onSubmit={handleOrderEditSubmit}
        clients={clientsList}
        owners={modalOwnerOptions}
        status={safeStatusOptions}
        sources={safeQualifiedSources}
        order_types={order_types ?? []}
        companies={safeCompanies}
        sourcesClients={safeSourcesClients}
        frame_colors={frame_colors ?? []}
        glass_colors={glass_colors ?? []}
        glass_types={glass_types ?? []}
        glass_coatings={glass_coatings ?? []}
        languages={languages ?? []}
        methodsOfPayment={methods_of_payment ?? []}
        financingOptions={type_of_financing ?? []}
        paymentScheduleTemplates={payment_schedule_templates ?? {}}
        showPaymentInformationSection={canManagePaymentInformationForOrder}
        showProjectAmountOnlySection={showProjectAmountOnlyBeforeContract}
        projectAmountReadOnly={isProjectAmountReadOnlyBeforeContract}
        attachments={Array.isArray(order.attachments) ? order.attachments : []}
        errorMessage={orderEditError}
      />

      <CompanyQuickEditModal
        open={companyEditModalOpen}
        company={companyEditTarget}
        onClose={() => { setCompanyEditModalOpen(false); setCompanyEditTarget(null) }}
        onUpdated={handleCompanyUpdated}
      />

      <ContactEditModal
        open={contactModalOpen}
        saving={contactSaving}
        canEditRequest={!canEditContact}
        values={contactFormValues}
        errors={contactFormErrors}
        sourceOptions={contactSourceOptions}
        errorMessage={contactSubmitError}
        onClose={() => { setContactModalOpen(false); setContactModalTargetClientId(null) }}
        onEditRequest={openRequestModal}
        onChange={(field, value) => {
          setContactFormValues(prev => ({
            ...prev,
            [field]: value
          }) as ContactFormValues)
        }}
        onSubmit={handleContactSubmit}
      />

      <RequestEditModal
        open={requestModalOpen}
        saving={requestSaving}
        values={requestFormValues}
        errors={requestFormErrors}
        statusOptions={requestStatusOptions}
        sourceOptions={requestSourceOptions}
        errorMessage={requestSubmitError}
        onClose={() => { setRequestModalOpen(false) }}
        onChange={handleRequestFieldChange}
        onSubmit={handleRequestSubmit}
      />

      <EstimateScheduleModal
        open={scheduleModalOpen && !!pendingMove}
        taskTitle={order.name ?? ''}
        initialScheduleDate={scheduleInitialValues.scheduleDate}
        initialOwnerIds={scheduleInitialValues.ownerIds}
        ownerOptions={safeOwnerOptions}
        error={scheduleError}
        saving={scheduleSaving}
        onClose={closeScheduleModal}
        onSubmit={handleScheduleSubmit}
      />

      <FollowUpModal
        open={followUpModalOpen && !!pendingFollowUp}
        taskTitle={order.name ?? ''}
        targetStatus={followUpInitialValues.targetStatus}
        initialProjectAmount={followUpInitialValues.projectAmount}
        initialNote={followUpInitialValues.note}
        loading={followUpSaving}
        error={followUpError}
        onCancel={closeFollowUpModal}
        onSubmit={handleFollowUpSubmit}
      />

      <StandByNoteModal
        open={standByModalOpen && !!pendingStandBy}
        taskTitle={order.name ?? ''}
        initialNote={standByInitialNote}
        loading={standBySaving}
        error={standByError}
        onCancel={closeStandByModal}
        onSubmit={handleStandBySubmit}
      />

      <RequestRescheduleModal
        open={requestRescheduleModalOpen && !!pendingRequestReschedule}
        taskTitle={order.name ?? ''}
        initialNote={requestRescheduleInitialNote}
        loading={requestRescheduleSaving}
        error={requestRescheduleError}
        onCancel={closeRequestRescheduleModal}
        onSubmit={handleRequestRescheduleSubmit}
      />

      <PreContractNoteModal
        open={preContractModalOpen && !!pendingPreContract}
        taskTitle={order.name ?? ''}
        initialNote={preContractInitialNote}
        loading={preContractSaving}
        error={preContractError}
        onCancel={closePreContractModal}
        onSubmit={handlePreContractSubmit}
      />

      <ContractSignedModal
        open={contractSignedModalOpen && !!pendingContractSigned}
        taskTitle={order.name ?? ''}
        initialProjectName={contractSignedInitialValues.projectName}
        initialProjectAmount={contractSignedInitialValues.projectAmount}
        initialDownPayment={contractSignedInitialValues.downPayment}
        initialJobAddress={contractSignedInitialValues.jobAddress}
        initialCity={contractSignedInitialValues.city}
        initialJobState={contractSignedInitialValues.jobState}
        initialJobZip={contractSignedInitialValues.jobZip}
        initialMethodOfPayment={contractSignedInitialValues.methodOfPayment}
        initialTypeOfFinancing={contractSignedInitialValues.typeOfFinancing}
        initialContactEmail={contractSignedInitialValues.contactEmail}
        initialOrderCompanyContactId={contractSignedInitialValues.orderCompanyContactId}
        orderType={order.order_type}
        companyOptions={commercialCompanyOptions}
        initialNameCheck={contractSignedInitialValues.nameCheck}
        initialAddressCheck={contractSignedInitialValues.addressCheck}
        initialAmountCheck={contractSignedInitialValues.amountCheck}
        initialEmailCheck={contractSignedInitialValues.emailCheck}
        initialCityPermits={contractSignedInitialValues.cityPermits}
        initialAssociationPermits={contractSignedInitialValues.associationPermits}
        initialPaymentScheduleType={contractSignedInitialValues.paymentScheduleType}
        initialCustomSchedule={contractSignedInitialValues.customSchedule}
        paymentMethods={methods_of_payment ?? []}
        financingOptions={type_of_financing ?? []}
        paymentScheduleTemplates={payment_schedule_templates ?? {}}
        loading={contractSignedSaving}
        error={contractSignedError}
        onCancel={closeContractSignedModal}
        onSubmit={handleContractSignedSubmit}
      />

      <LostContractModal
        open={lostContractModalOpen && !!pendingLostContract}
        lossReasons={lossReasonFrontdesk ?? []}
        loading={lostContractSaving}
        error={lostContractError}
        onCancel={closeLostContractModal}
        onSubmit={handleLostContractSubmit}
      />

      {orderProcessingModalOpen && pendingOrderProcessingMove && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div key={pendingOrderProcessingMove.newStatus} className="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <div>
                <h3 className="text-lg font-semibold text-slate-800">
                  {isOrderStorageStatus(pendingOrderProcessingMove.newStatus) ? 'Order Storage Update' : 'Order Processing Update'}
                </h3>
                <p className="text-xs text-slate-500">
                  {pendingOrderProcessingMove.oldStatus || 'Current status'} → {pendingOrderProcessingMove.newStatus}
                </p>
              </div>
              <button
                type="button"
                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                onClick={closeOrderProcessingModal}
                disabled={statusChangeSaving}
              >
                <span className="sr-only">Close</span>
                ×
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="order-processing-note">
                  Note
                </label>
                <textarea
                  id="order-processing-note"
                  className="form-textarea w-full resize-none placeholder:text-slate-400"
                  rows={4}
                  value={orderProcessingNote}
                  onChange={(event) => {
                    setOrderProcessingNote(event.target.value)
                    if (orderProcessingError) setOrderProcessingError(null)
                  }}
                  placeholder="Add context for this status change (optional)"
                  disabled={statusChangeSaving}
                />
              </div>
              {matchesStatus(pendingOrderProcessingMove.newStatus, 'REVIEW') && (
                <div>
                  <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="order-processing-invoice-number">
                    Invoice Number
                  </label>
                  <input
                    id="order-processing-invoice-number"
                    type="text"
                    className="form-input w-full placeholder:text-slate-400"
                    value={orderProcessingInvoiceNumber}
                    onChange={(event) => { setOrderProcessingInvoiceNumber(event.target.value) }}
                    placeholder="Add invoice number (optional)"
                    disabled={statusChangeSaving}
                  />
                </div>
              )}
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="order-processing-attachments">
                  Attachments
                </label>
                <input
                  id="order-processing-attachments"
                  type="file"
                  className="form-input w-full"
                  multiple
                  onChange={(event) => {
                    const files = Array.from(event.target.files ?? [])
                    setOrderProcessingAttachments(files)
                    if (orderProcessingError) setOrderProcessingError(null)
                  }}
                  disabled={statusChangeSaving}
                />
                {orderProcessingAttachments.length > 0 && (
                  <ul className="mt-2 space-y-1 text-xs text-slate-500">
                    {orderProcessingAttachments.map((file) => (
                      <li key={`${file.name}-${file.lastModified}`}>{file.name}</li>
                    ))}
                  </ul>
                )}
              </div>
              {(orderProcessingError || statusChangeError) && (
                <p className="text-sm text-rose-600">{orderProcessingError ?? statusChangeError}</p>
              )}
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeOrderProcessingModal}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={statusChangeSaving}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleOrderProcessingSubmit}
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={statusChangeSaving}
                >
                  {statusChangeSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}

      {frontdeskStandByModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-slate-800">Request Stand By</h3>
              <button
                type="button"
                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                onClick={closeFrontdeskStandByModal}
              >
                <span className="sr-only">Close</span>
                ×
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="frontdesk-standby-note">
                  Note <span className="text-red-500">*</span>
                </label>
                <textarea
                  id="frontdesk-standby-note"
                  className="form-textarea w-full resize-none placeholder:text-slate-400"
                  rows={4}
                  value={frontdeskStandByNote}
                  onChange={(event) => {
                    setFrontdeskStandByNote(event.target.value)
                    if (frontdeskStandByError) setFrontdeskStandByError(null)
                  }}
                  placeholder="Describe why the order is going to Request Stand By"
                />
                {frontdeskStandByError && <p className="mt-2 text-sm text-rose-600">{frontdeskStandByError}</p>}
              </div>
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeFrontdeskStandByModal}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={frontdeskStandBySaving}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleFrontdeskStandBySubmit}
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={frontdeskStandBySaving}
                >
                  {frontdeskStandBySaving ? 'Saving...' : 'Save Note'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      {frontdeskLostModalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 px-4">
          <div className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="text-lg font-semibold text-slate-800">Mark as Lost Request</h3>
              <button
                type="button"
                className="rounded-full p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600"
                onClick={closeFrontdeskLostModal}
              >
                <span className="sr-only">Close</span>
                ×
              </button>
            </div>
            <div className="space-y-4">
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="frontdesk-lost-reason">
                  Loss Reason <span className="text-red-500">*</span>
                </label>
                <select
                  id="frontdesk-lost-reason"
                  className="form-select w-full"
                  value={frontdeskLostReason}
                  onChange={(event) => {
                    setFrontdeskLostReason(event.target.value)
                    if (frontdeskLostError) setFrontdeskLostError(null)
                  }}
                >
                  <option value="">Select a reason</option>
                  {lossReasonFrontdesk?.map((reason) => (
                    <option key={reason} value={reason}>{reason}</option>
                  ))}
                </select>
              </div>
              <div>
                <label className="mb-1 block text-sm font-medium text-slate-600" htmlFor="frontdesk-lost-notes">
                  Notes (optional)
                </label>
                <textarea
                  id="frontdesk-lost-notes"
                  className="form-textarea w-full resize-none placeholder:text-slate-400"
                  rows={4}
                  value={frontdeskLostNotes}
                  onChange={(event) => { setFrontdeskLostNotes(event.target.value) }}
                  placeholder="Additional details about why the request was lost"
                />
                {frontdeskLostError && <p className="mt-2 text-sm text-rose-600">{frontdeskLostError}</p>}
              </div>
              <div className="flex items-center justify-end gap-3 pt-2">
                <button
                  type="button"
                  onClick={closeFrontdeskLostModal}
                  className="rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                  disabled={frontdeskLostSaving}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={handleFrontdeskLostSubmit}
                  className="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-sky-700 disabled:cursor-not-allowed disabled:bg-sky-400"
                  disabled={frontdeskLostSaving}
                >
                  {frontdeskLostSaving ? 'Saving...' : 'Save'}
                </button>
              </div>
            </div>
          </div>
        </div>
      )}
      <QuantifiedModal
        showModal={frontdeskQuantifiedModalOpen}
        onClose={() => { setFrontdeskQuantifiedModalOpen(false) }}
        task={frontdeskQuantifiedTask}
        setProjectList={noopSetProjectList}
        updateOrderStatus={noopUpdateOrderStatus}
        lostStatusId="QUALIFIED"
        lossReasonFrontdesk={lossReasonFrontdesk ?? []}
        sources={sources ?? []}
        previousStatusId={null}
        order_types={order_types ?? []}
        frame_colors={frame_colors ?? []}
        glass_colors={glass_colors ?? []}
        glass_types={glass_types ?? []}
        glass_coatings={glass_coatings ?? []}
        languages={languages ?? []}
        onSuccess={(updatedOrder) => {
          if (updatedOrder) {
            setOrder(prev => ({
              ...prev,
              ...updatedOrder
            }))
            refreshOrderActivity()
          }
          setFrontdeskQuantifiedModalOpen(false)
        }}
      />
    </AuthenticatedLayout>
  )
}
