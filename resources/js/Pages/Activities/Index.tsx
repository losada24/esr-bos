import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Head } from '@inertiajs/react'
import '@mobiscroll/react/dist/css/mobiscroll.min.css'
import { Eventcalendar, setOptions } from '@mobiscroll/react'
import type { MbscCalendarEventData, MbscEventcalendarView } from '@mobiscroll/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import Modal from '@/Components/Modal'
import CalendarIcon from '@/Components/Icons/CalendarIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import OrderNotesForOrder from '@/Components/OrderNotesForOrder'
import PhoneIcon from '@/Components/Icons/PhoneIcon'
import PlusIcon from '@/Components/Icons/PlusIcon'
import { type PageProps } from '@/types'

setOptions({
  theme: 'ios',
  themeVariant: 'light'
})

type TabKey = 'calendar' | 'events' | 'calls'
type ActivityModalTab = 'details' | 'notes'
type ViewMode = 'month' | 'week' | 'day'
type OwnershipFilter = 'mine' | 'all'
type StatusFilter = 'open' | 'closed' | 'all'
type ActivityTypeFilters = {
  events: boolean
  calls: boolean
  tasks: boolean
}

type CalendarQuickAction = {
  date: Date
  x: number
  y: number
} | null

interface UserOption {
  id: number
  name: string
  email?: string | null
}

interface ParticipantOption {
  email: string
  label: string
  source: 'user' | 'contact' | 'external'
}

interface ActivityEventRow {
  id: number
  host_id?: number | null
  order_id?: number | null
  client_id?: number | null
  title: string
  from: string
  to: string
  starts_at?: string | null
  ends_at?: string | null
  status?: string | null
  status_value?: string | null
  status_color?: string | null
  is_inactive?: boolean
  order?: RelatedOrder | null
  client?: RelatedClient | null
  related_to?: string | null
  host?: string | null
  reminder_enabled?: boolean
  reminder_minutes_before?: number | null
  location?: string | null
  online_meeting?: boolean
  meeting_link?: string | null
  participants?: string[] | null
  description?: string | null
}

interface ActivityCallRow {
  id: number
  owner_id?: number | null
  order_id?: number | null
  client_id?: number | null
  to_from: string
  call_type: string
  outgoing_call_status?: string | null
  call_start_time: string
  call_start_at?: string | null
  call_duration?: string | null
  call_duration_minutes?: number | null
  order?: RelatedOrder | null
  client?: RelatedClient | null
  related_to?: string | null
  owner?: string | null
  reminder_enabled?: boolean
  reminder_minutes_before?: number | null
  call_purpose?: string | null
  call_agenda?: string | null
}

interface RelatedOrder {
  id: number
  name?: string
  label: string
  client?: RelatedClient | null
  default_owner_id?: number | null
  default_owner_name?: string | null
}

interface RelatedClient {
  id: number
  name: string
  phone?: string | null
  email?: string | null
  label: string
}

interface ActivitiesProps extends PageProps {
  events: ActivityEventRow[]
  calls: ActivityCallRow[]
  users: UserOption[]
  canManageAll: boolean
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
const EVENT_COLOR = '#2563eb'
const EVENT_CLOSED_COLOR = EVENT_COLOR
const EVENT_CANCELLED_COLOR = '#dc2626'
const CALL_COLOR = '#7c3aed'
const CALL_CANCELLED_COLOR = EVENT_CANCELLED_COLOR

const pad = (value: number) => value.toString().padStart(2, '0')

const localInputValue = (date = new Date()) => {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

const localInputFromValue = (value?: string | null) => {
  if (!value) return localInputValue()
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? localInputValue() : localInputValue(date)
}

const plusHours = (value: string, hours: number) => {
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return localInputValue()
  date.setHours(date.getHours() + hours)
  return localInputValue(date)
}

const dateAtDefaultActivityTime = (date: Date) => {
  const nextDate = new Date(date)
  const now = new Date()
  const isToday = nextDate.toDateString() === now.toDateString()

  if (isToday) {
    nextDate.setHours(now.getHours() + 1, 0, 0, 0)
  } else {
    nextDate.setHours(9, 0, 0, 0)
  }

  return nextDate
}

const dateFromCalendarArgs = (args: any): Date | null => {
  const rawDate = args?.date ?? args?.day ?? args?.selectedDate
  if (!rawDate) return null

  const date = rawDate instanceof Date ? rawDate : new Date(rawDate)
  return Number.isNaN(date.getTime()) ? null : date
}

const positionFromElement = (element: HTMLElement) => {
  const rect = element.getBoundingClientRect()

  return {
    x: Math.min(rect.left + rect.width - 8, window.innerWidth - 190),
    y: Math.min(rect.top + rect.height + 4, window.innerHeight - 140)
  }
}

const requestJson = async (url: string, payload: Record<string, unknown>, method = 'POST') => {
  const response = await fetch(url, {
    method,
    credentials: 'include',
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-CSRF-TOKEN': csrfToken(),
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify(payload)
  })

  const json = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new Error(json?.message ?? 'Unable to save activity.')
  }

  return json
}

const searchJson = async <T,>(url: string): Promise<T[]> => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  const json = await response.json().catch(() => ({}))
  return Array.isArray(json?.data) ? json.data as T[] : []
}

const fetchJson = async <T,>(url: string): Promise<T> => {
  const response = await fetch(url, {
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    }
  })
  const json = await response.json().catch(() => ({}))

  if (!response.ok) {
    throw new Error(json?.message ?? 'Unable to load activity.')
  }

  return json as T
}

const eventColorForStatus = (status?: string | null) => {
  if (status === 'Cancelled') return EVENT_CANCELLED_COLOR
  if (status === 'Closed') return EVENT_CLOSED_COLOR
  return EVENT_COLOR
}

const eventIsInactive = (status?: string | null) => ['Closed', 'Cancelled'].includes(status ?? '')
const callColorForStatus = (status?: string | null) => status === 'Cancelled' ? CALL_CANCELLED_COLOR : CALL_COLOR
const callIsInactive = (status?: string | null) => status === 'Completed'

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const extractEmail = (value: unknown): string | null => {
  if (typeof value === 'string') {
    const match = value.match(/[^\s@,;]+@[^\s@,;]+\.[^\s@,;]+/)
    return match?.[0]?.toLowerCase() ?? null
  }

  if (value && typeof value === 'object' && 'email' in value) {
    return extractEmail((value as { email?: unknown }).email)
  }

  return null
}

const participantEmails = (participants?: unknown[] | string | null) => {
  const values = Array.isArray(participants) ? participants : (participants ? participants.split(',') : [])
  return Array.from(new Set(values.map(extractEmail).filter((email): email is string => Boolean(email))))
}

const calendarItemFromEvent = (event: ActivityEventRow) => ({
  id: `event-${event.id}`,
  title: event.title,
  text: event.title,
  start: event.starts_at,
  end: event.ends_at,
  color: event.status_color ?? eventColorForStatus(event.status),
  type: 'event',
  activity_status: event.status ?? 'Open'
})

const calendarItemFromCall = (call: ActivityCallRow) => {
  const start = call.call_start_at
  const startDate = start ? new Date(start) : null
  const endDate = startDate && !Number.isNaN(startDate.getTime())
    ? new Date(startDate.getTime() + (call.call_duration_minutes ?? 30) * 60000)
    : null

  return {
    id: `call-${call.id}`,
    title: `Call: ${call.to_from}`,
    text: `Call: ${call.to_from}`,
    start,
    end: endDate?.toISOString(),
    color: callColorForStatus(call.outgoing_call_status),
    type: 'call',
    activity_status: call.outgoing_call_status ?? 'Scheduled'
  }
}

const buildInitialCalendarItems = (events: ActivityEventRow[], calls: ActivityCallRow[]) => {
  return [
    ...events.filter((event) => event.starts_at).map(calendarItemFromEvent),
    ...calls.filter((call) => call.call_start_at).map(calendarItemFromCall)
  ]
}

const filteredInitialCalendarItems = (
  events: ActivityEventRow[],
  calls: ActivityCallRow[],
  activityTypes: ActivityTypeFilters
) => {
  return buildInitialCalendarItems(
    activityTypes.events ? events : [],
    activityTypes.calls ? calls : []
  )
}

const firstActivityDate = (events: ActivityEventRow[], calls: ActivityCallRow[]) => {
  const dates = [
    ...events.map((event) => event.starts_at),
    ...calls.map((call) => call.call_start_at)
  ]
    .filter(Boolean)
    .map((value) => new Date(value as string))
    .filter((date) => !Number.isNaN(date.getTime()))
    .sort((a, b) => b.getTime() - a.getTime())

  return dates[0] ?? new Date()
}

function RelatedPicker ({
  order,
  client,
  onOrderChange,
  onClientChange
}: {
  order: RelatedOrder | null
  client: RelatedClient | null
  onOrderChange: (order: RelatedOrder | null) => void
  onClientChange: (client: RelatedClient | null) => void
}) {
  const [orderQuery, setOrderQuery] = useState('')
  const [clientQuery, setClientQuery] = useState('')
  const [orders, setOrders] = useState<RelatedOrder[]>([])
  const [clients, setClients] = useState<RelatedClient[]>([])

  useEffect(() => {
    if (orderQuery.trim().length < 2) {
      setOrders([])
      return
    }

    let alive = true
    searchJson<RelatedOrder>(route('activities.orders.search', { q: orderQuery }))
      .then((data) => {
        if (alive) setOrders(data)
      })
      .catch(() => {
        if (alive) setOrders([])
      })

    return () => {
      alive = false
    }
  }, [orderQuery])

  useEffect(() => {
    if (clientQuery.trim().length < 2) {
      setClients([])
      return
    }

    let alive = true
    searchJson<RelatedClient>(route('activities.clients.search', { q: clientQuery }))
      .then((data) => {
        if (alive) {
          setClients(data.map((item: any) => ({
            id: item.id,
            name: item.name,
            phone: item.phone,
            email: item.email,
            label: [item.name, item.phone].filter(Boolean).join(' - ')
          })))
        }
      })
      .catch(() => {
        if (alive) setClients([])
      })

    return () => {
      alive = false
    }
  }, [clientQuery])

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
      <div>
        <label className="mb-1 block text-xs font-semibold text-slate-600">Related Order</label>
        {order
          ? (
            <div className="flex min-h-[38px] items-center justify-between rounded-md border border-slate-300 px-3 text-sm">
              <span className="truncate">{order.label}</span>
              <button type="button" className="text-slate-500 hover:text-danger" onClick={() => { onOrderChange(null) }}>x</button>
            </div>
            )
          : (
            <>
              <input
                className="form-input"
                value={orderQuery}
                placeholder="Search orders"
                onChange={(event) => { setOrderQuery(event.target.value) }}
              />
              {orders.length > 0 && (
                <div className="mt-1 max-h-36 overflow-y-auto rounded-md border border-slate-200 bg-white shadow">
                  {orders.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      className="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                      onClick={() => {
                        onOrderChange(item)
                        if (item.client) onClientChange(item.client)
                        setOrderQuery('')
                        setOrders([])
                      }}
                    >
                      {item.label}
                    </button>
                  ))}
                </div>
              )}
            </>
            )}
      </div>
      <div>
        <label className="mb-1 block text-xs font-semibold text-slate-600">Contact</label>
        {client
          ? (
            <div className="flex min-h-[38px] items-center justify-between rounded-md border border-slate-300 px-3 text-sm">
              <span className="truncate">{client.label}</span>
              <button type="button" className="text-slate-500 hover:text-danger" onClick={() => { onClientChange(null) }}>x</button>
            </div>
            )
          : (
            <>
              <input
                className="form-input"
                value={clientQuery}
                placeholder="Search contacts"
                onChange={(event) => { setClientQuery(event.target.value) }}
              />
              {clients.length > 0 && (
                <div className="mt-1 max-h-36 overflow-y-auto rounded-md border border-slate-200 bg-white shadow">
                  {clients.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      className="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                      onClick={() => {
                        onClientChange(item)
                        setClientQuery('')
                        setClients([])
                      }}
                    >
                      {item.label}
                    </button>
                  ))}
                </div>
              )}
            </>
            )}
      </div>
    </div>
  )
}

function ParticipantPicker ({
  users,
  selected,
  relatedClient,
  onChange
}: {
  users: UserOption[]
  selected: string[]
  relatedClient: RelatedClient | null
  onChange: (participants: string[]) => void
}) {
  const [query, setQuery] = useState('')
  const [matchedUsers, setMatchedUsers] = useState<UserOption[]>([])
  const [clients, setClients] = useState<RelatedClient[]>([])

  const normalizedSelected = useMemo(() => selected.map((email) => email.toLowerCase()), [selected])
  const addParticipant = useCallback((email: string) => {
    const cleanEmail = email.trim().replace(/,$/, '').toLowerCase()
    if (!emailPattern.test(cleanEmail) || normalizedSelected.includes(cleanEmail)) {
      return
    }

    onChange([...selected, cleanEmail])
    setQuery('')
    setClients([])
  }, [normalizedSelected, onChange, selected])

  const removeParticipant = useCallback((email: string) => {
    onChange(selected.filter((item) => item.toLowerCase() !== email.toLowerCase()))
  }, [onChange, selected])

  useEffect(() => {
    if (query.trim().length < 2) {
      setClients([])
      setMatchedUsers([])
      return
    }

    let alive = true
    searchJson<UserOption>(route('activities.users.search', { q: query }))
      .then((data) => {
        if (alive) setMatchedUsers(data)
      })
      .catch(() => {
        if (alive) setMatchedUsers([])
      })

    searchJson<RelatedClient>(route('client.search', { q: query }))
      .then((data) => {
        if (alive) {
          setClients(data.map((item: any) => ({
            id: item.id,
            name: item.name,
            phone: item.phone,
            email: item.email,
            label: [item.name, item.email ?? item.phone].filter(Boolean).join(' - ')
          })))
        }
      })
      .catch(() => {
        if (alive) setClients([])
      })

    return () => {
      alive = false
    }
  }, [query])

  const options = useMemo<ParticipantOption[]>(() => {
    const search = query.trim().toLowerCase()
    const userOptions = [...users, ...matchedUsers]
      .filter((user) => user.email)
      .filter((user) => !search || user.name.toLowerCase().includes(search) || (user.email ?? '').toLowerCase().includes(search))
      .map((user) => ({
        email: user.email ?? '',
        label: `${user.name} - ${user.email}`,
        source: 'user' as const
      }))

    const clientOptions = [
      relatedClient,
      ...clients
    ]
      .filter((client): client is RelatedClient => Boolean(client?.email))
      .filter((client) => !search || client.name.toLowerCase().includes(search) || (client.email ?? '').toLowerCase().includes(search))
      .map((client) => ({
        email: client.email ?? '',
        label: `${client.name} - ${client.email}`,
        source: 'contact' as const
      }))

    const externalOption = emailPattern.test(query.trim()) && !normalizedSelected.includes(query.trim().toLowerCase())
      ? [{ email: query.trim(), label: `Add ${query.trim()}`, source: 'external' as const }]
      : []

    return [...externalOption, ...userOptions, ...clientOptions]
      .filter((option) => !normalizedSelected.includes(option.email.toLowerCase()))
      .filter((option, index, list) => list.findIndex((item) => item.email.toLowerCase() === option.email.toLowerCase()) === index)
      .slice(0, 8)
  }, [clients, matchedUsers, normalizedSelected, query, relatedClient, users])

  return (
    <div>
      <label className="mb-1 block text-xs font-semibold text-slate-600">Participants</label>
      <div className="rounded-md border border-slate-300 bg-white px-2 py-2">
        <div className="flex flex-wrap gap-2">
          {selected.map((email) => (
            <span key={email} className="inline-flex items-center gap-2 rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
              {email}
              <button type="button" className="text-sky-500 hover:text-danger" onClick={() => { removeParticipant(email) }}>x</button>
            </span>
          ))}
          <input
            className="min-w-[220px] flex-1 border-0 px-1 py-1 text-sm outline-none focus:ring-0"
            value={query}
            placeholder="Search users/contacts or type an email"
            onChange={(event) => { setQuery(event.target.value) }}
            onKeyDown={(event) => {
              if ((event.key === 'Enter' || event.key === ',') && emailPattern.test(query.trim())) {
                event.preventDefault()
                addParticipant(query)
              }
            }}
          />
        </div>
      </div>
      {options.length > 0 && (
        <div className="mt-1 max-h-44 overflow-y-auto rounded-md border border-slate-200 bg-white shadow">
          {options.map((option) => (
            <button
              key={`${option.source}-${option.email}`}
              type="button"
              className="flex w-full items-center justify-between px-3 py-2 text-left text-sm hover:bg-slate-50"
              onClick={() => { addParticipant(option.email) }}
            >
              <span className="truncate">{option.label}</span>
              <span className="ml-2 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase text-slate-500">{option.source}</span>
            </button>
          ))}
        </div>
      )}
    </div>
  )
}

function EventModal ({
  show,
  users,
  currentUser,
  canManageAll,
  initialOrder,
  initialClient,
  initialDate,
  editingEvent,
  onClose,
  onSaved
}: {
  show: boolean
  users: UserOption[]
  currentUser: UserOption
  canManageAll: boolean
  initialOrder: RelatedOrder | null
  initialClient: RelatedClient | null
  initialDate: Date | null
  editingEvent: ActivityEventRow | null
  onClose: () => void
  onSaved: (event: ActivityEventRow) => void
}) {
  const defaultStart = localInputValue()
  const [hostId, setHostId] = useState(currentUser.id?.toString() ?? '')
  const [title, setTitle] = useState('New Event')
  const [startsAt, setStartsAt] = useState(defaultStart)
  const [endsAt, setEndsAt] = useState(plusHours(defaultStart, 1))
  const [eventStatus, setEventStatus] = useState('Scheduled')
  const [reminder, setReminder] = useState(true)
  const [reminderMinutes, setReminderMinutes] = useState('15')
  const [location, setLocation] = useState('')
  const [onlineMeeting, setOnlineMeeting] = useState(false)
  const [meetingLink, setMeetingLink] = useState('')
  const [participants, setParticipants] = useState<string[]>([])
  const [description, setDescription] = useState('')
  const [sendInvitation, setSendInvitation] = useState(false)
  const sendInvitationRef = useRef(false)
  const [order, setOrder] = useState<RelatedOrder | null>(null)
  const [client, setClient] = useState<RelatedClient | null>(null)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [activeModalTab, setActiveModalTab] = useState<ActivityModalTab>('details')
  const hostOptions = useMemo(() => {
    return users.some((user) => user.id === currentUser.id) ? users : [currentUser, ...users]
  }, [currentUser, users])
  const canEditEvent = !editingEvent || canManageAll || editingEvent.host_id === currentUser.id
  const readOnly = Boolean(editingEvent && !canEditEvent)

  useEffect(() => {
    if (show) {
      const nextStart = localInputValue(initialDate ? dateAtDefaultActivityTime(initialDate) : new Date())
      const eventOrder = editingEvent?.order ?? initialOrder
      const eventClient = editingEvent?.client ?? initialClient
      setHostId((editingEvent?.host_id ?? currentUser.id)?.toString() ?? '')
      setTitle(editingEvent?.title ?? 'New Event')
      setStartsAt(editingEvent ? localInputFromValue(editingEvent.starts_at) : nextStart)
      setEndsAt(editingEvent ? localInputFromValue(editingEvent.ends_at) : plusHours(nextStart, 1))
      setEventStatus(editingEvent?.status_value ?? 'Scheduled')
      setReminder(editingEvent?.reminder_enabled ?? true)
      setReminderMinutes((editingEvent?.reminder_minutes_before ?? 15).toString())
      setLocation(editingEvent?.location ?? '')
      setOnlineMeeting(editingEvent?.online_meeting ?? false)
      setMeetingLink(editingEvent?.meeting_link ?? '')
      setParticipants(participantEmails(editingEvent?.participants))
      setDescription(editingEvent?.description ?? '')
      setSendInvitation(false)
      sendInvitationRef.current = false
      setOrder(eventOrder)
      setClient(eventClient)
      setActiveModalTab('details')
      setError(null)
    }
  }, [show, currentUser.id, initialOrder, initialClient, initialDate, editingEvent])

  const submit = async () => {
    if (readOnly) return

    setSaving(true)
    setError(null)
    const shouldSendInvitation = participants.length > 0 && (sendInvitation || sendInvitationRef.current)

    try {
      const json = await requestJson(editingEvent
        ? route('activities.events.update', { event: editingEvent.id })
        : route('activities.events.store'), {
        host_id: hostId ? Number(hostId) : null,
        order_id: order?.id ?? null,
        client_id: client?.id ?? null,
        title,
        starts_at: startsAt,
        ends_at: endsAt,
        status: eventStatus,
        is_repeating: false,
        reminder_enabled: reminder,
        reminder_minutes_before: reminder ? Number(reminderMinutes) : null,
        location,
        online_meeting: onlineMeeting,
        meeting_link: onlineMeeting ? meetingLink : null,
        participants,
        send_invitation: shouldSendInvitation,
        description
      }, editingEvent ? 'PUT' : 'POST')
      onSaved(json.event)
      onClose()
    } catch (err: any) {
      setError(err?.message ?? 'Unable to save event.')
    } finally {
      setSaving(false)
    }
  }

  const handleParticipantsChange = useCallback((nextParticipants: string[]) => {
    setParticipants(nextParticipants)

    if (!editingEvent && nextParticipants.length > 0) {
      setSendInvitation(true)
      sendInvitationRef.current = true
    }

    if (nextParticipants.length === 0) {
      setSendInvitation(false)
      sendInvitationRef.current = false
    }
  }, [editingEvent])

  useEffect(() => {
    if (!editingEvent && participants.length > 0 && !sendInvitation) {
      setSendInvitation(true)
      sendInvitationRef.current = true
    }
  }, [editingEvent, participants.length, sendInvitation])

  return (
    <Modal show={show} maxWidth="3xl" onClose={onClose}>
      <div className="flex max-h-[90vh] flex-col">
      <div className="border-b border-slate-200 px-6 pt-4">
        <h3 className="text-lg font-semibold text-slate-900">{readOnly ? 'View Event' : (editingEvent ? 'Edit Event' : 'Create Event')}</h3>
        <div className="mt-4 flex gap-2">
          {(['details', 'notes'] as ActivityModalTab[]).map((tab) => (
            <button
              key={tab}
              type="button"
              className={`border-b-2 px-3 py-2 text-sm font-semibold capitalize ${activeModalTab === tab ? 'border-success text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
              onClick={() => { setActiveModalTab(tab) }}
            >
              {tab}
            </button>
          ))}
        </div>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto px-6 py-5">
        {error && <div className="rounded-md bg-danger-light px-3 py-2 text-sm text-danger">{error}</div>}
        {activeModalTab === 'details' && (
        <div className="space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_180px]">
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">Title</label>
            <input className="form-input" value={title} disabled={readOnly} onChange={(event) => { setTitle(event.target.value) }} />
          </div>
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">Host</label>
            <select className="form-select" value={hostId} disabled={readOnly || !canManageAll} onChange={(event) => { setHostId(event.target.value) }}>
              {hostOptions.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
            </select>
          </div>
        </div>
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">From</label>
            <input className="form-input" type="datetime-local" value={startsAt} disabled={readOnly} onChange={(event) => { setStartsAt(event.target.value) }} />
          </div>
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">To</label>
            <input className="form-input" type="datetime-local" value={endsAt} disabled={readOnly} onChange={(event) => { setEndsAt(event.target.value) }} />
          </div>
        </div>
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Status</label>
          <select className="form-select w-44" value={eventStatus} disabled={readOnly} onChange={(event) => { setEventStatus(event.target.value) }}>
            <option value="Scheduled">Scheduled</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="form-checkbox" checked={reminder} disabled={readOnly} onChange={(event) => { setReminder(event.target.checked) }} />
          Reminder
        </label>
        {reminder && (
          <select className="form-select w-44" value={reminderMinutes} disabled={readOnly} onChange={(event) => { setReminderMinutes(event.target.value) }}>
            <option value="15">15 mins</option>
            <option value="30">30 mins</option>
            <option value="60">1 hour</option>
            <option value="1440">1 day</option>
          </select>
        )}
        <input className="form-input" placeholder="Location" value={location} disabled={readOnly} onChange={(event) => { setLocation(event.target.value) }} />
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="form-checkbox" checked={onlineMeeting} disabled={readOnly} onChange={(event) => { setOnlineMeeting(event.target.checked) }} />
          Online Meeting
        </label>
        {onlineMeeting && (
          <input className="form-input" placeholder="Meeting link (Google Meet, Zoom, Teams...)" value={meetingLink} disabled={readOnly} onChange={(event) => { setMeetingLink(event.target.value) }} />
        )}
        {readOnly
          ? (
            <>
              <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-600">Related Order</label>
                  <div className="flex min-h-[38px] items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">{order?.label ?? 'No related order'}</div>
                </div>
                <div>
                  <label className="mb-1 block text-xs font-semibold text-slate-600">Contact</label>
                  <div className="flex min-h-[38px] items-center rounded-md border border-slate-200 bg-slate-50 px-3 text-sm text-slate-700">{client?.label ?? 'No contact'}</div>
                </div>
              </div>
              <div>
                <label className="mb-1 block text-xs font-semibold text-slate-600">Participants</label>
                <div className="flex min-h-[38px] flex-wrap items-center gap-2 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700">
                  {participants.length > 0
                    ? participants.map((email) => <span key={email} className="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">{email}</span>)
                    : 'No participants'}
                </div>
              </div>
            </>
            )
          : (
            <>
              <RelatedPicker order={order} client={client} onOrderChange={setOrder} onClientChange={setClient} />
              <ParticipantPicker users={users} selected={participants} relatedClient={client} onChange={handleParticipantsChange} />
              <label className="flex items-center gap-2 text-sm">
                <input
                  type="checkbox"
                  className="form-checkbox"
                  checked={sendInvitation}
                  disabled={participants.length === 0}
                  onChange={(event) => {
                    setSendInvitation(event.target.checked)
                    sendInvitationRef.current = event.target.checked
                  }}
                />
                Send invitation email to participants
              </label>
            </>
            )}
        <textarea className="form-textarea min-h-[90px]" placeholder="A few words about this event" value={description} disabled={readOnly} onChange={(event) => { setDescription(event.target.value) }} />
        </div>
        )}
        {activeModalTab === 'notes' && (
        <div>
          <OrderNotesForOrder
            endpointBase={editingEvent ? route('activities.events.notes.index', { event: editingEvent.id }) : null}
            canCreate={Boolean(editingEvent) && !readOnly}
            noteType="event_note"
            listTitle="Event Notes"
            emptyMessage="No notes for this event."
            disabledMessage="Save the event first to add and view event notes."
          />
        </div>
        )}
      </div>
      <div className="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
        {readOnly
          ? (
            <button type="button" className="btn btn-outline-primary" onClick={onClose}>Close</button>
            )
          : activeModalTab === 'notes'
          ? (
            <button type="button" className="btn btn-outline-primary" onClick={onClose}>Close</button>
            )
          : (
            <>
              <button type="button" className="btn btn-outline-primary" onClick={onClose}>Cancel</button>
              <button type="button" className="btn btn-primary" disabled={saving} onClick={() => { void submit() }}>{saving ? 'Saving...' : 'Save'}</button>
            </>
            )}
      </div>
      </div>
    </Modal>
  )
}

function CallModal ({
  show,
  users,
  currentUser,
  canManageAll,
  initialOrder,
  initialClient,
  initialDate,
  editingCall,
  onClose,
  onSaved
}: {
  show: boolean
  users: UserOption[]
  currentUser: UserOption
  canManageAll: boolean
  initialOrder: RelatedOrder | null
  initialClient: RelatedClient | null
  initialDate: Date | null
  editingCall: ActivityCallRow | null
  onClose: () => void
  onSaved: (call: ActivityCallRow) => void
}) {
  const [ownerId, setOwnerId] = useState(currentUser.id?.toString() ?? '')
  const [toFrom, setToFrom] = useState('')
  const [startTime, setStartTime] = useState(localInputValue())
  const [reminder, setReminder] = useState(false)
  const [reminderMinutes, setReminderMinutes] = useState('15')
  const [callType, setCallType] = useState('Outbound')
  const [status, setStatus] = useState('Scheduled')
  const [purpose, setPurpose] = useState('-None-')
  const [agenda, setAgenda] = useState('')
  const [order, setOrder] = useState<RelatedOrder | null>(null)
  const [client, setClient] = useState<RelatedClient | null>(null)
  const [contactOptions, setContactOptions] = useState<RelatedClient[]>([])
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [activeModalTab, setActiveModalTab] = useState<ActivityModalTab>('details')
  const ownerOptions = useMemo(() => {
    return users.some((user) => user.id === currentUser.id) ? users : [currentUser, ...users]
  }, [currentUser, users])

  useEffect(() => {
    if (show) {
      const callOrder = editingCall?.order ?? initialOrder
      const callClient = editingCall?.client ?? initialClient
      setOwnerId((editingCall?.owner_id ?? currentUser.id)?.toString() ?? '')
      setToFrom(editingCall?.to_from ?? callClient?.name ?? '')
      setStartTime(editingCall ? localInputFromValue(editingCall.call_start_at) : localInputValue(initialDate ? dateAtDefaultActivityTime(initialDate) : new Date()))
      setReminder(editingCall?.reminder_enabled ?? false)
      setReminderMinutes((editingCall?.reminder_minutes_before ?? 15).toString())
      setCallType(editingCall?.call_type ?? 'Outbound')
      setStatus(editingCall?.outgoing_call_status ?? 'Scheduled')
      setPurpose(editingCall?.call_purpose ?? '-None-')
      setAgenda(editingCall?.call_agenda ?? '')
      setOrder(callOrder)
      setClient(callClient)
      setContactOptions([])
      setActiveModalTab('details')
      setError(null)
    }
  }, [show, currentUser.id, initialOrder, initialClient, initialDate, editingCall])

  useEffect(() => {
    if (client && !toFrom) {
      setToFrom(client.name)
    }
  }, [client, toFrom])

  useEffect(() => {
    const query = toFrom.trim()

    if (!show || order || query.length < 2 || query === client?.name) {
      setContactOptions([])
      return
    }

    let alive = true
    searchJson<RelatedClient>(route('activities.clients.search', { q: query }))
      .then((data) => {
        if (alive) {
          setContactOptions(data.map((item: any) => ({
            id: item.id,
            name: item.name,
            phone: item.phone,
            email: item.email,
            label: [item.name, item.phone].filter(Boolean).join(' - ')
          })))
        }
      })
      .catch(() => {
        if (alive) setContactOptions([])
      })

    return () => {
      alive = false
    }
  }, [client?.name, order, show, toFrom])

  const submit = async () => {
    setSaving(true)
    setError(null)

    try {
      const json = await requestJson(editingCall
        ? route('activities.calls.update', { call: editingCall.id })
        : route('activities.calls.store'), {
        owner_id: ownerId ? Number(ownerId) : null,
        order_id: order?.id ?? null,
        client_id: client?.id ?? null,
        to_from: toFrom,
        call_start_time: startTime,
        call_duration_minutes: null,
        reminder_enabled: reminder,
        reminder_minutes_before: reminder ? Number(reminderMinutes) : null,
        call_type: callType,
        outgoing_call_status: status,
        call_purpose: purpose === '-None-' ? null : purpose,
        call_agenda: agenda
      }, editingCall ? 'PUT' : 'POST')
      onSaved(json.call)
      onClose()
    } catch (err: any) {
      setError(err?.message ?? 'Unable to save call.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <Modal show={show} maxWidth="3xl" onClose={onClose}>
      <div className="flex max-h-[90vh] flex-col">
      <div className="border-b border-slate-200 px-6 pt-4">
        <h3 className="text-lg font-semibold text-slate-900">{editingCall ? 'Edit Call' : 'Create Call'}</h3>
        <div className="mt-4 flex gap-2">
          {(['details', 'notes'] as ActivityModalTab[]).map((tab) => (
            <button
              key={tab}
              type="button"
              className={`border-b-2 px-3 py-2 text-sm font-semibold capitalize ${activeModalTab === tab ? 'border-success text-slate-900' : 'border-transparent text-slate-500 hover:text-slate-800'}`}
              onClick={() => { setActiveModalTab(tab) }}
            >
              {tab}
            </button>
          ))}
        </div>
      </div>
      <div className="min-h-0 flex-1 overflow-y-auto px-6 py-5">
        {error && <div className="rounded-md bg-danger-light px-3 py-2 text-sm text-danger">{error}</div>}
        {activeModalTab === 'details' && (
        <div className="space-y-4">
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-[1fr_180px]">
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">To/From</label>
            <div className="relative">
              <input
                className="form-input"
                value={toFrom}
                placeholder={order ? 'Contact from related order' : 'Search contacts or type a name'}
                onChange={(event) => {
                  const nextValue = event.target.value
                  setToFrom(nextValue)
                  if (!order && client && nextValue !== client.name) {
                    setClient(null)
                  }
                }}
              />
              {!order && contactOptions.length > 0 && (
                <div className="absolute z-20 mt-1 max-h-44 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow">
                  {contactOptions.map((item) => (
                    <button
                      key={item.id}
                      type="button"
                      className="block w-full px-3 py-2 text-left text-sm hover:bg-slate-50"
                      onClick={() => {
                        setClient(item)
                        setToFrom(item.name)
                        setContactOptions([])
                      }}
                    >
                      <span className="block font-medium text-slate-800">{item.name}</span>
                      <span className="block text-xs text-slate-500">{[item.phone, item.email].filter(Boolean).join(' - ')}</span>
                    </button>
                  ))}
                </div>
              )}
            </div>
            {!order && client && (
              <div className="mt-2 flex min-h-[30px] items-center justify-between rounded-md border border-slate-200 bg-slate-50 px-3 text-xs text-slate-600">
                <span className="truncate">{client.label}</span>
                <button
                  type="button"
                  className="ml-2 text-slate-500 hover:text-danger"
                  onClick={() => {
                    setClient(null)
                    setToFrom('')
                    setContactOptions([])
                  }}
                >
                  x
                </button>
              </div>
            )}
          </div>
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">Owner</label>
            <select className="form-select" value={ownerId} disabled={!canManageAll} onChange={(event) => { setOwnerId(event.target.value) }}>
              {ownerOptions.map((user) => <option key={user.id} value={user.id}>{user.name}</option>)}
            </select>
          </div>
        </div>
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Call Start Time</label>
          <input className="form-input" type="datetime-local" value={startTime} onChange={(event) => { setStartTime(event.target.value) }} />
        </div>
        <label className="flex items-center gap-2 text-sm">
          <input type="checkbox" className="form-checkbox" checked={reminder} onChange={(event) => { setReminder(event.target.checked) }} />
          Reminder
        </label>
        {reminder && (
          <select className="form-select w-44" value={reminderMinutes} onChange={(event) => { setReminderMinutes(event.target.value) }}>
            <option value="15">15 mins</option>
            <option value="30">30 mins</option>
            <option value="60">1 hour</option>
            <option value="1440">1 day</option>
          </select>
        )}
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">Call Type</label>
            <select className="form-select" value={callType} onChange={(event) => { setCallType(event.target.value) }}>
              <option value="Outbound">Outbound</option>
              <option value="Inbound">Inbound</option>
            </select>
          </div>
          <div>
            <label className="mb-1 block text-xs font-semibold text-slate-600">Outgoing Call Status</label>
            <select className="form-select" value={status} onChange={(event) => { setStatus(event.target.value) }}>
              <option value="Scheduled">Scheduled</option>
              <option value="Completed">Completed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>
        <RelatedPicker order={order} client={client} onOrderChange={setOrder} onClientChange={setClient} />
        <div>
          <label className="mb-1 block text-xs font-semibold text-slate-600">Call Purpose</label>
          <select className="form-select" value={purpose} onChange={(event) => { setPurpose(event.target.value) }}>
            <option value="-None-">-None-</option>
            <option value="Follow up">Follow up</option>
            <option value="Estimate">Estimate</option>
            <option value="Delivery">Delivery</option>
            <option value="Payment">Payment</option>
          </select>
        </div>
        <textarea className="form-textarea min-h-[90px]" placeholder="Call Agenda" value={agenda} onChange={(event) => { setAgenda(event.target.value) }} />
        </div>
        )}
        {activeModalTab === 'notes' && (
        <div>
          <OrderNotesForOrder
            endpointBase={editingCall ? route('activities.calls.notes.index', { call: editingCall.id }) : null}
            canCreate={Boolean(editingCall)}
            noteType="call_note"
            listTitle="Call Notes"
            emptyMessage="No notes for this call."
            disabledMessage="Save the call first to add and view call notes."
          />
        </div>
        )}
      </div>
      <div className="flex justify-end gap-3 border-t border-slate-200 px-6 py-4">
        {activeModalTab === 'notes'
          ? (
            <button type="button" className="btn btn-outline-primary" onClick={onClose}>Close</button>
            )
          : (
            <>
              <button type="button" className="btn btn-outline-primary" onClick={onClose}>Cancel</button>
              <button type="button" className="btn btn-primary" disabled={saving} onClick={() => { void submit() }}>{saving ? 'Saving...' : 'Save'}</button>
            </>
            )}
      </div>
      </div>
    </Modal>
  )
}

export default function ActivitiesIndex ({ auth, events: initialEvents, calls: initialCalls, users, canManageAll }: ActivitiesProps) {
  const [activeTab, setActiveTab] = useState<TabKey>('calendar')
  const [viewMode, setViewMode] = useState<ViewMode>('month')
  const [calendarEvents, setCalendarEvents] = useState<any[]>(() => buildInitialCalendarItems(initialEvents, initialCalls))
  const [currentDate, setCurrentDate] = useState(() => firstActivityDate(initialEvents, initialCalls))
  const [events, setEvents] = useState(initialEvents)
  const [calls, setCalls] = useState(initialCalls)
  const [eventModalOpen, setEventModalOpen] = useState(false)
  const [callModalOpen, setCallModalOpen] = useState(false)
  const [editingEvent, setEditingEvent] = useState<ActivityEventRow | null>(null)
  const [editingCall, setEditingCall] = useState<ActivityCallRow | null>(null)
  const [initialOrder, setInitialOrder] = useState<RelatedOrder | null>(null)
  const [initialClient, setInitialClient] = useState<RelatedClient | null>(null)
  const [initialActivityDate, setInitialActivityDate] = useState<Date | null>(null)
  const [quickAction, setQuickAction] = useState<CalendarQuickAction>(null)
  const [activityTypes, setActivityTypes] = useState<ActivityTypeFilters>({ events: true, calls: true, tasks: false })
  const [ownershipFilter, setOwnershipFilter] = useState<OwnershipFilter>('all')
  const [statusFilter, setStatusFilter] = useState<StatusFilter>('open')

  const view = useMemo<MbscEventcalendarView>(() => {
    if (viewMode === 'month') {
      return { calendar: { type: 'month', labels: 10 } }
    }

    return {
      schedule: {
        type: viewMode,
        startTime: viewMode === 'day' ? '00:00' : undefined,
        endTime: viewMode === 'day' ? '24:00' : undefined
      }
    } as MbscEventcalendarView
  }, [viewMode])

  const loadCalendarEvents = useCallback((date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const selectedTypes = [
      activityTypes.events ? 'events' : null,
      activityTypes.calls ? 'calls' : null,
      activityTypes.tasks ? 'tasks' : null
    ].filter(Boolean).join(',')
    const fallbackItems = filteredInitialCalendarItems(events, calls, activityTypes)

    if (!selectedTypes) {
      setCalendarEvents([])
      return
    }

    const url = route('activities.calendar.events', {
      year,
      month,
      types: selectedTypes,
      ownership: ownershipFilter,
      status: statusFilter
    })

    fetch(url, {
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(async (response) => await response.json())
      .then((data) => {
        if (Array.isArray(data)) {
          setCalendarEvents(data)
          return
        }

        setCalendarEvents(fallbackItems)
      })
      .catch(() => {
        setCalendarEvents(fallbackItems)
      })
  }, [activityTypes, calls, events, ownershipFilter, statusFilter])

  useEffect(() => {
    loadCalendarEvents(currentDate)
  }, [currentDate, loadCalendarEvents, events, calls])

  const openEventForDate = useCallback((date: Date) => {
    setEditingEvent(null)
    setEditingCall(null)
    setInitialActivityDate(date)
    setInitialOrder(null)
    setInitialClient(null)
    setQuickAction(null)
    setEventModalOpen(true)
  }, [])

  const openCallForDate = useCallback((date: Date) => {
    setEditingEvent(null)
    setEditingCall(null)
    setInitialActivityDate(date)
    setInitialOrder(null)
    setInitialClient(null)
    setQuickAction(null)
    setCallModalOpen(true)
  }, [])

  const openEventEditor = useCallback((event: ActivityEventRow) => {
    setEditingEvent(event)
    setEditingCall(null)
    setInitialOrder(event.order ?? null)
    setInitialClient(event.client ?? null)
    setInitialActivityDate(null)
    setEventModalOpen(true)
  }, [])

  const openCallEditor = useCallback((call: ActivityCallRow) => {
    setEditingEvent(null)
    setEditingCall(call)
    setInitialOrder(call.order ?? null)
    setInitialClient(call.client ?? null)
    setInitialActivityDate(null)
    setCallModalOpen(true)
  }, [])

  const renderDay = useCallback((day: any) => {
    const date = dateFromCalendarArgs(day)
    const dayNumber = date?.getDate() ?? ''

    return (
      <div className="flex min-h-[28px] items-start justify-between pl-1 pr-1 pt-1">
        <span className="min-w-[18px] text-left text-xs font-semibold leading-5 text-slate-700">{dayNumber}</span>
        {date && (
          <button
            type="button"
            className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-transparent text-base font-bold leading-none text-sky-600 hover:bg-sky-50 hover:text-sky-700"
            title="Add activity"
            onClick={(event) => {
              event.preventDefault()
              event.stopPropagation()
              const position = positionFromElement(event.currentTarget)
              setQuickAction({
                date,
                x: position.x,
                y: position.y
              })
            }}
          >
            +
          </button>
        )}
      </div>
    )
  }, [])

  const renderLabelContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}
    const title = (originalEvent.title ?? originalEvent.text ?? eventData.title ?? '') as string
    const status = (originalEvent.activity_status ?? originalEvent.status ?? '') as string
    const inactive = originalEvent.type === 'event'
      ? eventIsInactive(status)
      : originalEvent.type === 'call' && callIsInactive(status)

    return (
      <div className="flex min-w-0 items-center leading-tight" title={originalEvent.tooltip ?? title}>
        <span className={`truncate text-xs font-semibold ${inactive ? 'line-through' : ''}`}>{title}</span>
      </div>
    )
  }, [])

  const renderScheduleEventContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}
    const title = (originalEvent.title ?? originalEvent.text ?? eventData.title ?? '') as string
    const status = (originalEvent.activity_status ?? originalEvent.status ?? '') as string
    const inactive = originalEvent.type === 'event'
      ? eventIsInactive(status)
      : originalEvent.type === 'call' && callIsInactive(status)

    return (
      <div className="flex min-w-0 flex-col leading-tight" title={originalEvent.tooltip ?? title}>
        <span className={`truncate text-xs font-semibold ${inactive ? 'line-through' : ''}`}>{title}</span>
        {status && ['event', 'call'].includes(originalEvent.type) && (
          <span className={`truncate text-[10px] ${inactive ? 'line-through' : ''}`}>{status}</span>
        )}
      </div>
    )
  }, [])

  const handleCalendarEventClick = useCallback((args: any) => {
    const calendarEvent = args?.event ?? {}
    const rawId = String(calendarEvent.original?.id ?? calendarEvent.id ?? '')
    const [type, id] = rawId.split('-')
    const numericId = Number(id)

    if (!numericId) return

    if (type === 'event') {
      fetchJson<{ event: ActivityEventRow }>(route('activities.events.show', { event: numericId }))
        .then((json) => {
          setEvents((prev) => prev.some((event) => event.id === json.event.id)
            ? prev.map((event) => event.id === json.event.id ? json.event : event)
            : [json.event, ...prev])
          openEventEditor(json.event)
        })
        .catch(() => {})
    }

    if (type === 'call') {
      fetchJson<{ call: ActivityCallRow }>(route('activities.calls.show', { call: numericId }))
        .then((json) => {
          setCalls((prev) => prev.some((call) => call.id === json.call.id)
            ? prev.map((call) => call.id === json.call.id ? json.call : call)
            : [json.call, ...prev])
          openCallEditor(json.call)
        })
        .catch(() => {})
    }
  }, [openCallEditor, openEventEditor])

  useEffect(() => {
    const params = new URLSearchParams(window.location.search)
    const mode = params.get('mode')
    const orderId = params.get('order_id')
    const clientId = params.get('client_id')

    if (!mode || (!orderId && !clientId)) return

    let alive = true
    const contextUrl = route('activities.context', {
      ...(orderId ? { order_id: orderId } : {}),
      ...(clientId ? { client_id: clientId } : {})
    })

    fetch(contextUrl, {
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
      .then(async (response) => await response.json())
      .then((json) => {
        if (!alive) return
        setInitialOrder(json?.order ?? null)
        setInitialClient(json?.client ?? json?.order?.client ?? null)
        if (mode === 'event') setEventModalOpen(true)
        if (mode === 'call') setCallModalOpen(true)
      })
      .catch(() => {})

    return () => {
      alive = false
    }
  }, [])

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      pageTitle="Activities"
      actions={
        <div className="flex flex-wrap items-center gap-2">
          <button type="button" className="btn btn-primary gap-2" onClick={() => { setEditingEvent(null); setEditingCall(null); setInitialOrder(null); setInitialClient(null); setInitialActivityDate(null); setEventModalOpen(true) }}>
            <PlusIcon />
            Event
          </button>
          <button type="button" className="btn btn-primary gap-2" onClick={() => { setEditingEvent(null); setEditingCall(null); setInitialOrder(null); setInitialClient(null); setInitialActivityDate(null); setCallModalOpen(true) }}>
            <PlusIcon />
            Call
          </button>
        </div>
      }
    >
      <Head title="Activities" />
      <div className="mb-4 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200">
        <div className="flex">
          {[
            { key: 'calendar', label: 'Calendar', icon: <CalendarIcon /> },
            { key: 'events', label: 'Events', icon: <CalendarIcon /> },
            { key: 'calls', label: 'Calls', icon: <PhoneIcon /> }
          ].map((tab) => (
            <button
              key={tab.key}
              type="button"
              className={`flex min-w-[120px] items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold ${activeTab === tab.key ? 'border-success text-black' : 'border-transparent text-slate-600 hover:text-black'}`}
              onClick={() => { setActiveTab(tab.key as TabKey) }}
            >
              {tab.icon}
              {tab.label}
            </button>
          ))}
        </div>
        {activeTab === 'calendar' && (
          <div className="flex rounded-full border border-slate-200 bg-white p-1">
            {(['month', 'week', 'day'] as ViewMode[]).map((mode) => (
              <button
                key={mode}
                type="button"
                className={`rounded-full px-3 py-1 text-xs font-semibold capitalize ${viewMode === mode ? 'bg-primary text-white' : 'text-slate-600 hover:bg-slate-50'}`}
                onClick={() => { setViewMode(mode) }}
              >
                {mode}
              </button>
            ))}
          </div>
        )}
      </div>

      {activeTab === 'calendar' && (
        <div className="mb-4 flex flex-wrap items-center gap-4 rounded-md border border-slate-200 bg-slate-50/70 px-4 py-3">
          <div className="flex flex-wrap items-center gap-3">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Activity Types</span>
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                className="form-checkbox"
                checked={activityTypes.events}
                onChange={(event) => { setActivityTypes((prev) => ({ ...prev, events: event.target.checked })) }}
              />
              Events
            </label>
            <label className="flex items-center gap-2 text-sm text-slate-700">
              <input
                type="checkbox"
                className="form-checkbox"
                checked={activityTypes.calls}
                onChange={(event) => { setActivityTypes((prev) => ({ ...prev, calls: event.target.checked })) }}
              />
              Calls
            </label>
          </div>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Ownership</span>
            <select
              className="form-select min-w-[150px]"
              value={ownershipFilter}
              onChange={(event) => { setOwnershipFilter(event.target.value as OwnershipFilter) }}
            >
              <option value="mine">My Activities</option>
              <option value="all">All Activities</option>
            </select>
          </label>
          <label className="flex items-center gap-2 text-sm text-slate-700">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</span>
            <select
              className="form-select min-w-[170px]"
              value={statusFilter}
              onChange={(event) => { setStatusFilter(event.target.value as StatusFilter) }}
            >
              <option value="open">Open Activities</option>
              <option value="closed">Closed Activities</option>
              <option value="all">All</option>
            </select>
          </label>
        </div>
      )}

      {activeTab === 'calendar' && (
        <div className="relative h-[calc(100vh-230px)]">
          <Eventcalendar
            key={`${viewMode}-${calendarEvents.length}-${currentDate.toISOString().slice(0, 10)}`}
            data={calendarEvents}
            view={view}
            selectedDate={currentDate}
            dataTimezone="local"
            displayTimezone="local"
            clickToCreate={false}
            dragToCreate={false}
            dragToMove={false}
            dragToResize={false}
            eventDelete={false}
            renderDay={renderDay}
            renderLabelContent={renderLabelContent}
            renderScheduleEventContent={renderScheduleEventContent}
            onEventClick={handleCalendarEventClick}
            onPageChange={(event) => {
              if (event.month) setCurrentDate(event.month)
            }}
          />
          {quickAction && (
            <>
              <button
                type="button"
                className="fixed inset-0 z-40 cursor-default bg-transparent"
                aria-label="Close activity actions"
                onClick={() => { setQuickAction(null) }}
              />
              <div
                className="fixed z-50 w-[180px] overflow-hidden rounded-md border border-slate-200 bg-white py-1 shadow-xl"
                style={{ left: quickAction.x, top: quickAction.y }}
              >
                <button
                  type="button"
                  className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-sky-50 hover:text-sky-700"
                  onClick={() => { openEventForDate(quickAction.date) }}
                >
                  <CalendarIcon />
                  Create Event
                </button>
                <button
                  type="button"
                  className="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-emerald-50 hover:text-emerald-700"
                  onClick={() => { openCallForDate(quickAction.date) }}
                >
                  <PhoneIcon />
                  Log Call
                </button>
              </div>
            </>
          )}
        </div>
      )}

      {activeTab === 'events' && (
        <div className="table-responsive">
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="border-b text-left text-xs font-bold">
                <th className="px-4 py-3">Title</th>
                <th className="px-4 py-3">From</th>
                <th className="px-4 py-3">To</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Related To</th>
                <th className="px-4 py-3">Host</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {events.map((event) => (
                <tr key={event.id} className="hover:bg-slate-50">
                  <td className={`border-b px-4 py-3 font-semibold ${event.is_inactive ? 'text-slate-500 line-through' : ''}`}>{event.title}</td>
                  <td className="border-b px-4 py-3">{event.from}</td>
                  <td className="border-b px-4 py-3">{event.to}</td>
                  <td className="border-b px-4 py-3">
                    <span
                      className="inline-flex rounded-full px-2 py-1 text-xs font-semibold text-white"
                      style={{ backgroundColor: event.status_color ?? eventColorForStatus(event.status) }}
                    >
                      {event.status ?? 'Open'}
                    </span>
                  </td>
                  <td className="border-b px-4 py-3">{event.related_to ?? ''}</td>
                  <td className="border-b px-4 py-3">{event.host ?? ''}</td>
                  <td className="border-b px-4 py-3 text-right">
                    <button
                      type="button"
                      className="inline-flex items-center justify-center rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-primary"
                      title="Edit event"
                      onClick={() => { openEventEditor(event) }}
                    >
                      <EditIcon className="m-0" />
                    </button>
                  </td>
                </tr>
              ))}
              {events.length === 0 && (
                <tr>
                  <td className="px-4 py-24 text-center text-slate-500" colSpan={7}>No records in this view.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      {activeTab === 'calls' && (
        <div className="table-responsive">
          <table className="w-full whitespace-nowrap">
            <thead>
              <tr className="border-b text-left text-xs font-bold">
                <th className="px-4 py-3">To/From</th>
                <th className="px-4 py-3">Call Type</th>
                <th className="px-4 py-3">Call Start Time</th>
                <th className="px-4 py-3">Call Duration</th>
                <th className="px-4 py-3">Related To</th>
                <th className="px-4 py-3">Call Owner</th>
                <th className="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody>
              {calls.map((call) => (
                <tr key={call.id} className="hover:bg-slate-50">
                  <td className="border-b px-4 py-3 font-semibold">{call.to_from}</td>
                  <td className="border-b px-4 py-3">{call.call_type}</td>
                  <td className="border-b px-4 py-3 text-danger">{call.call_start_time}</td>
                  <td className="border-b px-4 py-3">{call.call_duration ?? ''}</td>
                  <td className="border-b px-4 py-3">{call.related_to ?? ''}</td>
                  <td className="border-b px-4 py-3">{call.owner ?? ''}</td>
                  <td className="border-b px-4 py-3 text-right">
                    <button
                      type="button"
                      className="inline-flex items-center justify-center rounded-md p-1 text-slate-500 hover:bg-slate-100 hover:text-primary"
                      title="Edit call"
                      onClick={() => { openCallEditor(call) }}
                    >
                      <EditIcon className="m-0" />
                    </button>
                  </td>
                </tr>
              ))}
              {calls.length === 0 && (
                <tr>
                  <td className="px-4 py-24 text-center text-slate-500" colSpan={7}>No records in this view.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}

      <EventModal
        show={eventModalOpen}
        users={users}
        currentUser={auth.user}
        canManageAll={canManageAll}
        initialOrder={initialOrder}
        initialClient={initialClient}
        initialDate={initialActivityDate}
        editingEvent={editingEvent}
        onClose={() => { setEventModalOpen(false); setEditingEvent(null) }}
        onSaved={(event) => {
          setEvents((prev) => editingEvent ? prev.map((item) => item.id === event.id ? event : item) : [event, ...prev])
          setCalendarEvents((prev) => {
            const item = calendarItemFromEvent(event)
            return editingEvent ? prev.map((calendarItem) => calendarItem.id === item.id ? item : calendarItem) : [item, ...prev]
          })
          setEditingEvent(null)
          if (event.starts_at) {
            const nextDate = new Date(event.starts_at)
            if (!Number.isNaN(nextDate.getTime())) setCurrentDate(nextDate)
          }
          setActiveTab('calendar')
        }}
      />
      <CallModal
        show={callModalOpen}
        users={users}
        currentUser={auth.user}
        canManageAll={canManageAll}
        initialOrder={initialOrder}
        initialClient={initialClient}
        initialDate={initialActivityDate}
        editingCall={editingCall}
        onClose={() => { setCallModalOpen(false); setEditingCall(null) }}
        onSaved={(call) => {
          setCalls((prev) => editingCall ? prev.map((item) => item.id === call.id ? call : item) : [call, ...prev])
          setCalendarEvents((prev) => {
            const item = calendarItemFromCall(call)
            return editingCall ? prev.map((calendarItem) => calendarItem.id === item.id ? item : calendarItem) : [item, ...prev]
          })
          setEditingCall(null)
          if (call.call_start_at) {
            const nextDate = new Date(call.call_start_at)
            if (!Number.isNaN(nextDate.getTime())) setCurrentDate(nextDate)
          }
          setActiveTab('calendar')
        }}
      />
    </AuthenticatedCalendarLayout>
  )
}
