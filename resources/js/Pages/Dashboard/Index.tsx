import { useCallback, useEffect, useMemo, useState } from 'react'
import { Head } from '@inertiajs/react'
import { type Role, type PageProps } from '@/types'
import '@mobiscroll/react/dist/css/mobiscroll.min.css'
import { Eventcalendar, getJson, setOptions } from '@mobiscroll/react'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin } from '@/Utils/user'
import EventModal from './EventModal'

setOptions({
  theme: 'ios',
  themeVariant: 'light'
})

export default function Dashboard ({ auth }: PageProps) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const [myEvents, setEvents] = useState([])
  const [currentDate, setCurrentDate] = useState(new Date())
  // const [toastText, setToastText] = useState()
  const [eventId, setEventId] = useState(0)

  const [isModalOpen, setModalOpen] = useState(false)

  const handleEventClick = useCallback((args: any) => {
    // setToastText(args.event.title)
    // setToastOpen(true)
    setEventId(args.event.order_id)
    setModalOpen(true)
  }, [])

  const loadEvents = (date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('dashboard.get_events', { year, month })
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
      labels: 10
    }
  }), [])

  useEffect(() => {
    loadEvents(currentDate)
  }, [currentDate])

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      pageTitle='Calendar'
    >
      <Head title="Calendar" />
      <div
        className='w-full h-[85vh] flex flex-col'>
        <Eventcalendar
          clickToCreate={false}
          dragToCreate={false}
          dragToMove={IS_ADMIN || IS_ACCOUNT_MANAGER}
          dragToResize={IS_ADMIN || IS_ACCOUNT_MANAGER}
          swipeEnabled={true}
          scrollEnabled={true}
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
        onClose={setModalOpen}
        id={eventId}
      />
    </AuthenticatedCalendarLayout>
  )
}
