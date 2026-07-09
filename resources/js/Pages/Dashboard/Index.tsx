import { useCallback, useEffect, useMemo, useState } from 'react'
import { Head } from '@inertiajs/react'
import { type Role, type PageProps, type InstallationTeam, type User } from '@/types'
import '@mobiscroll/react/dist/css/mobiscroll.min.css'
import { Eventcalendar, getJson, setOptions } from '@mobiscroll/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner } from '@/Utils/user'
import EventModal from './EventModal'

setOptions({
  theme: 'ios',
  themeVariant: 'light'
})

interface Legend {
  color: string
  label: string
}
interface CalendarFilter {
  service: string
  status: string
  name: string
}

export default function Dashboard ({ auth, services, statusmodal, legend, installation_teams, supervisors, calendarStatusOptions }: PageProps & { services: string[], statusmodal: [], legend: Legend[], installation_teams: InstallationTeam[], supervisors: User[], calendarStatusOptions: string[] }) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))

  const [myEvents, setEvents] = useState([])
  const [currentDate, setCurrentDate] = useState(new Date())
  const [calendarFilter, setCalendarFilter] = useState<CalendarFilter>({ service: IS_ADMIN || IS_ACCOUNT_MANAGER ? 'all' : services[0] ?? 'INSTALLATION', status: 'all', name: '' })
  const [eventId, setEventId] = useState(0)

  const [isModalOpen, setModalOpen] = useState(false)
  const [eventsPerDay, setEventsPerDay] = useState<number | 'all' >(10)

  const handleEventClick = useCallback((args: any) => {
    setEventId(args.event.order_id)
    setModalOpen(true)
  }, [])

  const loadEvents = (date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('dashboard.get_events', { year, month, service: calendarFilter.service, status: calendarFilter.status, ...(calendarFilter.name !== 'all' && { name: calendarFilter.name }) })
    getJson(getEventsRoute, (events) => {
      setEvents(events)
    }, 'json')
  }

  const handlePageChange = (event: any) => {
    const newDate = event.month
    setCurrentDate(newDate)
  }

  const handleEventUpdate = (args: any) => {
    const updateEventRoute = route('dashboard.update_event', { id: args.event.order_id })
    const data = {
      type_of_event: args.event.type_of_event,
      start: args.event.start.toISOString().slice(0, 10),
      end: args.event.end.toISOString().slice(0, 10)
    }

    fetch(updateEventRoute, {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(data)
    })
      .then(async (response) => await response.json())
      .then((data) => {
        console.log(data)
      })
      .catch((error) => {
        console.error('Error:', error)
      })
  }

  const myView = useMemo(() => ({
    calendar: {
      labels: eventsPerDay
    }
  }), [eventsPerDay])

  useEffect(() => {
    loadEvents(currentDate)
  }, [currentDate, calendarFilter])

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      // pageTitle='Calendar'
    >
      <Head title="Calendar" />
      <div
        className='w-full h-[90vh] flex flex-col overflow-y-scroll'>
        <div className='flex justify-between items-center mb-3'>
          <div className='flex gap-3'>
            <div className='flex items-center gap-2'>
              <label htmlFor="">Service:</label>
              <select
                className='form-select'
                onChange={(e) => {
                  setCalendarFilter({ ...calendarFilter, service: e.target.value })
                }}
              >
                {IS_ADMIN || IS_ACCOUNT_MANAGER ? <option value="all">All</option> : null}
                {services.map((service) => (
                  <option key={service}>{service}</option>
                ))}
              </select>
            </div>
            <div className='flex items-center gap-2'>
              <label htmlFor="">Status:</label>
              <select
                className='form-select'
                onChange={(e) => {
                  setCalendarFilter({ ...calendarFilter, status: e.target.value })
                }}
              >
                <option value="all">All</option>
                {calendarStatusOptions.map((status) => (
                  <option key={status}>{status}</option>
                ))}
              </select>
            </div>
            <div className='flex items-center gap-2'>
              <label className='w-48' htmlFor="">Event per day:</label>
              <select
                className='form-select'
                onChange={(e) => {
                  const value = e.target.value === 'all' ? e.target.value : parseInt(e.target.value)
                  setEventsPerDay(value)
                }}
                value={eventsPerDay}
              >
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="all">All</option>
              </select>
            </div>
          </div>
          <div className='flex gap-3'>
            {legend.map((item, index) => {
              return (
                <div key={`legend${index}`} className='flex items-center gap-1'>
                  <div className='border w-5 h-5 rounded-sm cursor-pointer' title={item.label} style={{ backgroundColor: item.color }}></div>
                </div>
              )
            })}
          </div>
        </div>
        <div className='flex justify-between items-center mb-4'>
        <div className='flex gap-3'>
        <div className='flex items-center gap-2'>
            <label className='w-57' htmlFor="">Order: </label>
            <input
              id="name"
              type="text"
              className="form-input w-96"
              placeholder="Enter Order Name, Intallator Name or Supervisor Name"
              value={calendarFilter.name}
              onChange={(e) => {
                setCalendarFilter({ ...calendarFilter, name: e.target.value })
              }}
            />
          </div>
          </div>
          </div>
          <Eventcalendar
            clickToCreate={false}
            dragToCreate={false}
            dragToMove={IS_ADMIN || IS_ACCOUNT_MANAGER}
            dragToResize={IS_ADMIN || IS_ACCOUNT_MANAGER}
            swipeEnabled={true}
            scrollEnabled={false}
            eventDelete={false}
            data={myEvents}
            view={myView}
            onEventClick={handleEventClick}
            onPageChange={handlePageChange}
            onEventUpdate={handleEventUpdate}
          />
      </div>
      <EventModal
        showModal={isModalOpen}
        onClose={() => {
          setModalOpen(false)
          loadEvents(currentDate)
        }}
        isAdminOrAccountManager={IS_ADMIN || IS_ACCOUNT_MANAGER}
        isSupervisor={IS_SUPERVISOR}
        isServiceManager={IS_SERVICE_MANAGER}
        isInstaller={IS_INSTALLER}
        isOwner={IS_OWNER}
        isPaymentCoordinator ={IS_PAYMENT_COORDINATOR}
        id={eventId}
        installation_teams ={installation_teams}
        supervisors={supervisors}
        status = {statusmodal}
      />
    </AuthenticatedCalendarLayout>
  )
}
