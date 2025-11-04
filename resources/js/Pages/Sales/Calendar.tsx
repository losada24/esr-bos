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

interface SalesCalendarProps extends PageProps {
  statuses: string[]
  legend: LegendItem[]
}

export default function SalesCalendar ({ auth, statuses, legend }: SalesCalendarProps) {
  const [events, setEvents] = useState<any[]>([])
  const [currentDate, setCurrentDate] = useState<Date>(new Date())
  const [statusFilter, setStatusFilter] = useState<string>('all')
  const [eventsPerDay, setEventsPerDay] = useState<number | 'all'>(10)
  const [viewMode, setViewMode] = useState<'month' | 'week' | 'day'>('month')

  const loadEvents = useCallback((date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('sales.calendar.events', { year, month, status: statusFilter })

    getJson(getEventsRoute, (data) => {
      setEvents(Array.isArray(data) ? data : [])
    }, 'json')
  }, [statusFilter])

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
        type: viewMode
      }
    }
  }, [eventsPerDay, viewMode])

  const handlePageChange = useCallback((event: any) => {
    setCurrentDate(event.month)
  }, [])

  const handleEventClick = useCallback((args: any) => {
    const orderId = args?.event?.order_id
    if (!orderId) return
    router.visit(route('order.show', orderId))
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
    const ownerNames: string = originalEvent.owner_names ?? ''
    const detailLine = [appointmentTime, ownerNames].filter(Boolean).join(' • ')

    return (
      <div className="flex flex-col gap-[2px] leading-tight">
        <span className="text-sm font-semibold">{orderName}</span>
        {detailLine && (
          <span className="text-xs text-gray-700 dark:text-gray-200">{detailLine}</span>
        )}
      </div>
    )
  }, [])

  return (
    <AuthenticatedCalendarLayout auth={auth}>
      <Head title="Sales Calendar" />
      <div className="w-full h-[90vh] flex flex-col overflow-y-auto">
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
              <span>Status:</span>
              <select
                className="form-select min-w-[180px]"
                value={statusFilter}
                onChange={(e) => {
                  setStatusFilter(e.target.value)
                }}
              >
                <option value="all">All</option>
                {statuses.map((status) => (
                  <option key={status} value={status}>{status}</option>
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
          <div className="flex flex-wrap gap-3">
            {legend.map((item) => (
              <div key={item.label} className="flex items-center gap-2 text-xs">
                <span className="block w-4 h-4 rounded-sm border" style={{ backgroundColor: item.color }}></span>
                <span>{item.label}</span>
              </div>
            ))}
          </div>
        </div>
        <Eventcalendar
          data={events}
          view={view}
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
