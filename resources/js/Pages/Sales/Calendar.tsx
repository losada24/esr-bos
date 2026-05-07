import { useCallback, useEffect, useMemo, useState } from 'react'
import { Head, router } from '@inertiajs/react'
import '@mobiscroll/react/dist/css/mobiscroll.min.css'
import { Eventcalendar, getJson, setOptions } from '@mobiscroll/react'
import type { MbscEventcalendarView, MbscCalendarEventData } from '@mobiscroll/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { type PageProps } from '@/types'

setOptions({
  theme: 'ios',
  themeVariant: 'light'
})

interface LegendItem {
  color: string
  label: string
}

interface OwnerOption {
  id: number
  name: string
}

interface SalesCalendarProps extends PageProps {
  owners: OwnerOption[]
  legend: LegendItem[]
}

export default function SalesCalendar ({ auth, owners, legend }: SalesCalendarProps) {
  const [events, setEvents] = useState<any[]>([])
  const [currentDate, setCurrentDate] = useState<Date>(new Date())
  const [ownerFilter, setOwnerFilter] = useState<string>('all')
  const [eventsPerDay, setEventsPerDay] = useState<number | 'all'>(10)
  const [viewMode, setViewMode] = useState<'month' | 'week' | 'day'>('month')
  const [legendExpanded, setLegendExpanded] = useState(false)

  const loadEvents = useCallback((date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('sales.calendar.events', { year, month, owner: ownerFilter })

    getJson(getEventsRoute, (data) => {
      setEvents(Array.isArray(data) ? data : [])
    }, 'json')
  }, [ownerFilter])

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

    const scheduleConfig: any = {
      type: viewMode
    }

    if (viewMode === 'day') {
      scheduleConfig.startTime = '00:00'
      scheduleConfig.endTime = '24:00'
      scheduleConfig.timeCellStep = 60
      scheduleConfig.timeLabelStep = 60
      scheduleConfig.days = false
    }

    if (viewMode === 'week') {
      scheduleConfig.eventHeight = 'variable'
    }

    return {
      schedule: scheduleConfig
    } as MbscEventcalendarView
  }, [eventsPerDay, viewMode])

  const handlePageChange = useCallback((event: any) => {
    setCurrentDate(event.month)
  }, [])

  const handleEventClick = useCallback((args: any) => {
    const eventData = args?.event ?? null
    if (!eventData) return
    const orderId = eventData.order_id ?? eventData.original?.order_id
    if (!orderId) return
    router.visit(route('frontdesk.order_view', { id: orderId }))
  }, [])

  const renderEventLabelContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}
    const orderName: string = originalEvent.order_name ?? eventData.title ?? ''
    const secondaryLabel: string = originalEvent.secondary_label ?? ''

    return (
      <div className="flex items-center gap-[4px] leading-tight">
        <span className="text-xs font-semibold truncate">{orderName}</span>
        {secondaryLabel && (
          <span className="text-[10px] text-gray-700 dark:text-gray-200 truncate">
            {secondaryLabel}
          </span>
        )}
      </div>
    )
  }, [])
  const renderScheduleEventContent = useCallback((eventData: MbscCalendarEventData) => {
    const originalEvent = (eventData.original as Record<string, any>) ?? {}
    const orderName: string = originalEvent.order_name ?? eventData.title ?? ''
    const appointmentTime: string = originalEvent.appointment_time ?? ''
    const clientName: string = originalEvent.client_name ?? ''
    const city: string = originalEvent.city ?? ''
    const jobAddress: string = originalEvent.job_address ?? ''
    const jobCity: string = originalEvent.job_city ?? ''
    const jobState: string = originalEvent.job_state ?? ''
    const jobZip: string = originalEvent.job_zip ?? ''
    const ownerNames: string = originalEvent.owner_names ?? ''
    const ownerLabel = ownerNames.includes(',') ? 'Owners' : 'Owner'
    const isWeekView = viewMode === 'week'
    const isDayView = viewMode === 'day'
    const isDayOrWeek = isDayView || isWeekView
    const addressCity = jobCity || city
    const addressParts = jobAddress ? [jobAddress, addressCity, jobState, jobZip].filter(Boolean) : []
    const addressText = addressParts.join(', ')
    const mapsUrl = addressText
      ? `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(addressText)}`
      : ''
    const titleLine = isDayOrWeek ? orderName : [orderName, city].filter(Boolean).join(' • ')
    const detailItems: Array<React.ReactNode> = []

    if (isDayOrWeek) {
      if (appointmentTime) detailItems.push(appointmentTime)
      if (ownerNames) detailItems.push(`${ownerLabel}: ${ownerNames}`)
      if (addressText) {
        detailItems.push(
          <a
            key="address"
            href={mapsUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="text-blue-600 underline"
            onClick={(event) => event.stopPropagation()}
          >
            {addressText}
          </a>
        )
      }
    } else {
      if (appointmentTime) detailItems.push(appointmentTime)
      if (ownerNames) detailItems.push(`${ownerLabel}: ${ownerNames}`)
      if (clientName) detailItems.push(`Client: ${clientName}`)
      if (!isWeekView && city) detailItems.push(`City: ${city}`)
    }

    const titleText = isDayOrWeek
      ? [orderName, ownerNames && `${ownerLabel}: ${ownerNames}`, addressText].filter(Boolean).join(' • ')
      : [orderName, city, appointmentTime, ownerNames && `${ownerLabel}: ${ownerNames}`, clientName && `Client: ${clientName}`].filter(Boolean).join(' • ')

    return (
      <div className="sales-calendar-event flex min-w-0 flex-col gap-[2px] leading-tight" title={titleText}>
        <span className="sales-calendar-event-title text-[11px] font-semibold">{titleLine}</span>
        {detailItems.length > 0 && (
          <span className="sales-calendar-event-detail text-[10px] text-gray-700 dark:text-gray-200">
            {detailItems.map((item, index) => (
              <span key={index}>
                {item}
                {index < detailItems.length - 1 ? ' • ' : ''}
              </span>
            ))}
          </span>
        )}
      </div>
    )
  }, [viewMode])

  return (
    <AuthenticatedCalendarLayout auth={auth}>
      <Head title="Sales Calendar" />
      <div className={`sales-calendar w-full h-[90vh] flex flex-col overflow-y-auto ${viewMode === 'week' ? 'sales-calendar-week' : ''} ${viewMode === 'day' ? 'sales-calendar-day' : ''}`}>
        <div className="flex flex-wrap items-center justify-between gap-4 mb-4">
          <div className="flex flex-wrap items-center gap-4">
            <label className="flex items-center gap-2">
              <span>View:</span>
              <select
                className="form-select"
                value={viewMode}
                onChange={(e) => {
                  setViewMode(e.target.value as 'month' | 'week' | 'day')
                }}
              >
                <option value="month">Month</option>
                <option value="week">Week</option>
                <option value="day">Day</option>
              </select>
            </label>
            <label className="flex items-center gap-2">
              <span>Owner:</span>
              <select
                className="form-select min-w-[200px]"
                value={ownerFilter}
                onChange={(e) => {
                  setOwnerFilter(e.target.value)
                }}
              >
                <option value="all">All</option>
                {owners.map((owner) => (
                  <option key={owner.id} value={owner.id}>{owner.name}</option>
                ))}
              </select>
            </label>
            {viewMode === 'month' && (
              <label className="flex items-center gap-2">
                <span>Events per day:</span>
                <select
                  className="form-select"
                  value={eventsPerDay}
                  onChange={(e) => {
                    const value = e.target.value === 'all' ? 'all' : Number(e.target.value)
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
              onClick={() => { setLegendExpanded((prev) => !prev) }}
            >
              {legendExpanded ? 'Hide Legend' : 'Legend'}
            </button>
            {legendExpanded && (
              <div className="sales-calendar-legend flex flex-wrap gap-2 max-h-40 overflow-y-auto">
                {legend.map((item) => (
                  <div
                    key={item.label}
                    className="flex items-center gap-2 rounded-full border border-gray-200 bg-white px-3 py-1 text-xs text-gray-700 shadow-sm"
                  >
                    <span className="block h-3 w-3 rounded-full border" style={{ backgroundColor: item.color }}></span>
                    <span className="truncate max-w-[160px]">{item.label}</span>
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
          renderLabelContent={renderEventLabelContent}
          renderScheduleEventContent={renderScheduleEventContent}
        />
      </div>
    </AuthenticatedCalendarLayout>
  )
}
