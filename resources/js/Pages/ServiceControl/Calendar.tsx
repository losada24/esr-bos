import { useCallback, useEffect, useMemo, useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import '@mobiscroll/react/dist/css/mobiscroll.min.css'
import { Eventcalendar, getJson, setOptions } from '@mobiscroll/react'
import type { MbscCalendarEventData, MbscEventcalendarView } from '@mobiscroll/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import Modal from '@/Components/Modal'
import { type PageProps } from '@/types'

setOptions({
  theme: 'ios',
  themeVariant: 'light'
})

type LegendItem = {
  color: string
  label: string
  status: string
}

type StatusOption = {
  label: string
  value: string
}

type Props = PageProps & {
  legend: LegendItem[]
  statusOptions: StatusOption[]
}

const Detail = ({ label, value }: { label: string, value?: string | number | null }) => (
  <div className="rounded-md border border-gray-200 px-3 py-2">
    <div className="text-[11px] uppercase tracking-wide text-gray-500">{label}</div>
    <div className="text-sm font-medium text-gray-900">{value || '-'}</div>
  </div>
)

const ServiceEventModal = ({ event, show, canOpenService, onClose }: { event: Record<string, any> | null, show: boolean, canOpenService: boolean, onClose: () => void }) => (
  <Modal show={show} maxWidth="2xl" onClose={onClose}>
    <div className="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 px-6 py-4">
      <div>
        <h3 className="text-lg font-semibold text-gray-900">Service Details</h3>
        {event?.type_label && (
          <span className="mt-1 mr-2 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style={{ backgroundColor: event.color }}>
            {event.type_label}
          </span>
        )}
        {event?.status_label && (
          <span className="mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold text-white" style={{ backgroundColor: event.color }}>
            {event.status_label}
          </span>
        )}
      </div>
      <button type="button" onClick={onClose} className="rounded-md px-2 py-1 text-sm text-gray-500 hover:bg-gray-100">Close</button>
    </div>
    <div className="space-y-4 px-6 py-5">
      <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <Detail label="Service Name" value={event?.service_name} />
        <Detail label="Service ID" value={event?.service_id} />
        <Detail label="Order" value={event?.order_name} />
        <Detail label="Order #" value={event?.order_number} />
        <Detail label="Client" value={event?.client_name} />
        <Detail label="Client Phone" value={event?.client_phone} />
        <Detail label="Event Date" value={event?.event_date} />
        <Detail label="Production Output Date" value={event?.production_output_date} />
        <Detail label="Urgency Status" value={event?.urgency_status} />
        <Detail label="Owners" value={event?.owner_names} />
        <Detail label="Reception Date" value={event?.service_created_date} />
      </div>
      <div className="rounded-md border border-gray-200 px-3 py-2">
        <div className="text-[11px] uppercase tracking-wide text-gray-500">Description</div>
        <div className="text-sm font-medium text-gray-900">{event?.description || '-'}</div>
      </div>
      {canOpenService && event?.service_control_id && (
        <Link href={route('service-control.edit', event.service_control_id)} className="btn btn-primary">
          Open Service
        </Link>
      )}
    </div>
  </Modal>
)

export default function ServiceControlCalendar ({ auth, legend, statusOptions }: Props) {
  const [events, setEvents] = useState<any[]>([])
  const [currentDate, setCurrentDate] = useState<Date>(new Date())
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [search, setSearch] = useState<string>('')
  const [eventsPerDay, setEventsPerDay] = useState<number | 'all'>(10)
  const [viewMode, setViewMode] = useState<'month' | 'week' | 'day'>('month')
  const [legendExpanded, setLegendExpanded] = useState(true)
  const [selectedEvent, setSelectedEvent] = useState<Record<string, any> | null>(null)
  const [showEventModal, setShowEventModal] = useState(false)
  const roleNames = auth.user.roles.map((role) => String(role.name ?? '').trim().toLowerCase().replace(/[\s-]+/g, '_'))
  const canOpenService = roleNames.includes('admin') || roleNames.includes('account_manager') || roleNames.includes('service_manager')

  const loadEvents = useCallback((date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('service-control.calendar.events', { year, month, status: statusFilter, search })

    getJson(getEventsRoute, (data) => {
      setEvents(Array.isArray(data) ? data : [])
    }, 'json')
  }, [statusFilter, search])

  useEffect(() => {
    loadEvents(currentDate)
  }, [currentDate, loadEvents])

  const view = useMemo<MbscEventcalendarView>(() => {
    if (viewMode === 'month') {
      return {
        calendar: {
          type: 'month',
          labels: eventsPerDay
        }
      }
    }

    return {
      schedule: {
        type: viewMode,
        eventHeight: 'variable'
      }
    } as MbscEventcalendarView
  }, [eventsPerDay, viewMode])

  const handlePageChange = useCallback((event: any) => {
    setCurrentDate(event.month)
  }, [])

  const handleEventClick = useCallback((args: any) => {
    const eventData = args?.event ?? null
    if (!eventData) return
    setSelectedEvent(eventData)
    setShowEventModal(true)
  }, [])

  const renderLabelContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}

    return (
      <div className="flex min-w-0 items-center gap-[4px] leading-tight" title={originalEvent.tooltip ?? eventData.title}>
        <span className="truncate text-xs font-semibold">{originalEvent.service_name ?? eventData.title}</span>
        {originalEvent.type_label && (
          <span className="shrink-0 text-[10px] font-bold uppercase text-gray-800 dark:text-gray-100">- {originalEvent.type_label}</span>
        )}
        {originalEvent.assignee_name && (
          <span className="truncate text-[10px] text-gray-700 dark:text-gray-200">- {originalEvent.assignee_name}</span>
        )}
      </div>
    )
  }, [])

  const renderScheduleEventContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}
    const details = [
      originalEvent.type_label,
      originalEvent.status_label,
      originalEvent.assignee_name && `Assignee: ${originalEvent.assignee_name}`,
      originalEvent.service_id && `ID: ${originalEvent.service_id}`,
      originalEvent.client_name && `Client: ${originalEvent.client_name}`,
      originalEvent.supervisor_name && `Supervisor: ${originalEvent.supervisor_name}`,
    ].filter(Boolean)

    return (
      <div className="flex min-w-0 flex-col gap-[2px] leading-tight" title={originalEvent.tooltip ?? eventData.title}>
        <span className="text-[11px] font-semibold">
          {[originalEvent.service_name ?? eventData.title, originalEvent.type_label, originalEvent.assignee_name].filter(Boolean).join(' - ')}
        </span>
        <span className="text-[10px] text-gray-700 dark:text-gray-200">{details.join(' • ')}</span>
      </div>
    )
  }, [])

  return (
    <AuthenticatedCalendarLayout auth={auth}>
      <Head title="Service Calendar" />
      <div className="sales-calendar w-full h-[90vh] flex flex-col overflow-y-auto">
        <div className="mb-4 flex flex-wrap items-center justify-between gap-4">
          <div className="flex flex-wrap items-center gap-4">
            <label className="flex items-center gap-2">
              <span>View:</span>
              <select className="form-select" value={viewMode} onChange={(event) => { setViewMode(event.target.value as 'month' | 'week' | 'day') }}>
                <option value="month">Month</option>
                <option value="week">Week</option>
                <option value="day">Day</option>
              </select>
            </label>
            <label className="flex items-center gap-2">
              <span>Status:</span>
              <select className="form-select min-w-[210px]" value={statusFilter} onChange={(event) => { setStatusFilter(event.target.value) }}>
                <option value="all">All</option>
                {statusOptions.map((status) => (
                  <option key={status.value} value={status.value}>{status.label}</option>
                ))}
              </select>
            </label>
            <label className="flex items-center gap-2">
              <span>Search:</span>
              <input
                type="text"
                className="form-input min-w-[280px]"
                value={search}
                onChange={(event) => { setSearch(event.target.value) }}
                placeholder="Order, service, client, phone, supervisor..."
              />
            </label>
            {viewMode === 'month' && (
              <label className="flex items-center gap-2">
                <span>Events per day:</span>
                <select
                  className="form-select"
                  value={eventsPerDay}
                  onChange={(event) => {
                    const value = event.target.value === 'all' ? 'all' : Number(event.target.value)
                    setEventsPerDay(value)
                  }}
                >
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="15">15</option>
                  <option value="all">All</option>
                </select>
              </label>
            )}
          </div>
          <div className="flex flex-col items-start gap-2">
            <button
              type="button"
              className="rounded-md border border-gray-200 bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50"
              onClick={() => { setLegendExpanded((expanded) => !expanded) }}
            >
              {legendExpanded ? 'Hide Legend' : 'Legend'}
            </button>
            {legendExpanded && (
              <div className="sales-calendar-legend flex flex-wrap gap-2">
                {legend.map((item) => (
                  <div key={item.status} className="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs text-gray-700 shadow-sm">
                    <span className="block h-3 w-3 rounded-full border" style={{ backgroundColor: item.color }}></span>
                    <span>{item.label}</span>
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
        <Eventcalendar
          data={events}
          view={view}
          dataTimezone="local"
          displayTimezone="local"
          clickToCreate={false}
          dragToCreate={false}
          dragToMove={false}
          dragToResize={false}
          eventDelete={false}
          onPageChange={handlePageChange}
          onEventClick={handleEventClick}
          renderLabelContent={renderLabelContent}
          renderScheduleEventContent={renderScheduleEventContent}
        />
        <ServiceEventModal
          show={showEventModal}
          event={selectedEvent}
          canOpenService={canOpenService}
          onClose={() => {
            setShowEventModal(false)
            setSelectedEvent(null)
          }}
        />
      </div>
    </AuthenticatedCalendarLayout>
  )
}
