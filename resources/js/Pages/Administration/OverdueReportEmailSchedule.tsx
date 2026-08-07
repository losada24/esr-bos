import { type FormEvent, useMemo, useState } from 'react'
import { Head, useForm } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface ScheduleUser {
  id: number
  name: string
  email: string
}

interface ScheduleData {
  enabled: boolean
  weekdays: string[]
  send_time: string
  timezone: string
  recipient_user_ids: number[]
  manual_emails: string
  last_sent_at: string | null
  last_sent_date: string | null
}

type Props = PageProps & {
  schedule: ScheduleData
  users: ScheduleUser[]
  weekdays: string[]
}

const weekdayLabels: Record<string, string> = {
  monday: 'Monday',
  tuesday: 'Tuesday',
  wednesday: 'Wednesday',
  thursday: 'Thursday',
  friday: 'Friday',
  saturday: 'Saturday',
  sunday: 'Sunday'
}

export default function OverdueReportEmailSchedule ({
  auth,
  schedule,
  users,
  weekdays
}: Props) {
  const [userSearch, setUserSearch] = useState('')
  const { data, setData, put, processing, errors } = useForm({
    enabled: schedule.enabled,
    weekdays: schedule.weekdays,
    send_time: schedule.send_time,
    recipient_user_ids: schedule.recipient_user_ids,
    manual_emails: schedule.manual_emails
  })
  const filteredUsers = useMemo(() => {
    const search = userSearch.trim().toLowerCase()

    if (search === '') return users

    return users.filter((user) => {
      return `${user.name} ${user.email}`.toLowerCase().includes(search)
    })
  }, [userSearch, users])
  const selectedUsers = useMemo(() => {
    return users.filter((user) => data.recipient_user_ids.includes(user.id))
  }, [data.recipient_user_ids, users])
  const manualEmails = useMemo(() => {
    return data.manual_emails
      .split(/[\s,;]+/)
      .map((email) => email.trim())
      .filter((email, index, emails) => email !== '' && emails.findIndex((item) => item.toLowerCase() === email.toLowerCase()) === index)
  }, [data.manual_emails])
  const recipientCount = selectedUsers.length + manualEmails.length

  const toggleWeekday = (weekday: string) => {
    setData(
      'weekdays',
      data.weekdays.includes(weekday)
        ? data.weekdays.filter((value) => value !== weekday)
        : [...data.weekdays, weekday]
    )
  }

  const toggleUser = (userId: number) => {
    setData(
      'recipient_user_ids',
      data.recipient_user_ids.includes(userId)
        ? data.recipient_user_ids.filter((value) => value !== userId)
        : [...data.recipient_user_ids, userId]
    )
  }

  const submit = (event: FormEvent) => {
    event.preventDefault()
    put(route('overdue-report-email-schedule.update'))
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Overdue Report Email Schedule">
      <Head title="Overdue Report Email Schedule" />

      <form onSubmit={submit} className="space-y-6">
        <div className="grid gap-4 lg:grid-cols-3">
          <div className="rounded border border-slate-200 bg-white p-4">
            <div className="text-xs font-semibold uppercase text-slate-500">Status</div>
            <label className="mt-3 flex items-center gap-3">
              <input
                type="checkbox"
                className="form-checkbox"
                checked={data.enabled}
                onChange={(event) => { setData('enabled', event.target.checked) }}
              />
              <span className="font-semibold text-slate-800">Automatic email enabled</span>
            </label>
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <div className="text-xs font-semibold uppercase text-slate-500">Time</div>
            <div className="mt-3 flex items-center gap-3">
              <input
                type="time"
                className="form-input max-w-[160px]"
                value={data.send_time}
                onChange={(event) => { setData('send_time', event.target.value) }}
              />
              <span className="text-sm text-slate-500">{schedule.timezone}</span>
            </div>
            {errors.send_time && <div className="mt-2 text-sm text-danger">{errors.send_time}</div>}
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <div className="text-xs font-semibold uppercase text-slate-500">Last Send</div>
            <div className="mt-3 text-sm font-semibold text-slate-800">
              {schedule.last_sent_at ?? 'Never sent'}
            </div>
          </div>
        </div>

        <div className="rounded border border-slate-200 bg-white p-4">
          <div className="mb-3 text-sm font-semibold text-slate-800">Send Days</div>
          <div className="flex flex-wrap gap-2">
            {weekdays.map((weekday) => {
              const selected = data.weekdays.includes(weekday)

              return (
                <button
                  key={weekday}
                  type="button"
                  className={`rounded border px-3 py-2 text-sm font-semibold transition ${
                    selected
                      ? 'border-primary bg-primary text-white'
                      : 'border-slate-200 bg-white text-slate-700 hover:border-primary'
                  }`}
                  onClick={() => { toggleWeekday(weekday) }}
                >
                  {weekdayLabels[weekday] ?? weekday}
                </button>
              )
            })}
          </div>
          {errors.weekdays && <div className="mt-2 text-sm text-danger">{errors.weekdays}</div>}
        </div>

        <div className="grid gap-4 lg:grid-cols-2">
          <div className="rounded border border-slate-200 bg-white p-4">
            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <div className="text-sm font-semibold text-slate-800">System Users</div>
                <div className="text-xs text-slate-500">
                  {filteredUsers.length} of {users.length} users
                </div>
              </div>
              <input
                type="search"
                className="form-input sm:max-w-[260px]"
                placeholder="Search users..."
                value={userSearch}
                onChange={(event) => { setUserSearch(event.target.value) }}
              />
            </div>
            <div className="max-h-[340px] space-y-2 overflow-y-auto pr-2">
              {filteredUsers.map((user) => (
                <label key={user.id} className="flex items-start gap-3 rounded border border-slate-100 p-3 hover:bg-slate-50">
                  <input
                    type="checkbox"
                    className="form-checkbox mt-1"
                    checked={data.recipient_user_ids.includes(user.id)}
                    onChange={() => { toggleUser(user.id) }}
                  />
                  <span>
                    <span className="block font-semibold text-slate-800">{user.name}</span>
                    <span className="block text-xs text-slate-500">{user.email}</span>
                  </span>
                </label>
              ))}
              {filteredUsers.length === 0 && (
                <div className="rounded border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                  No users match this search.
                </div>
              )}
            </div>
            {errors.recipient_user_ids && <div className="mt-2 text-sm text-danger">{errors.recipient_user_ids}</div>}
          </div>

          <div className="rounded border border-slate-200 bg-white p-4">
            <label className="mb-3 block text-sm font-semibold text-slate-800" htmlFor="manual_emails">
              Manual Emails
            </label>
            <textarea
              id="manual_emails"
              className="form-textarea min-h-[220px]"
              value={data.manual_emails}
              onChange={(event) => { setData('manual_emails', event.target.value) }}
            />
            {errors.manual_emails && <div className="mt-2 text-sm text-danger">{errors.manual_emails}</div>}
          </div>
        </div>

        <div className="rounded border border-slate-200 bg-white p-4">
          <div className="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div className="text-sm font-semibold text-slate-800">Recipients</div>
            <div className="text-xs font-semibold uppercase text-slate-500">
              {recipientCount} total
            </div>
          </div>

          {recipientCount === 0
            ? (
              <div className="rounded border border-dashed border-slate-200 p-4 text-sm text-slate-500">
                No recipients selected.
              </div>
              )
            : (
              <div className="space-y-4">
                {selectedUsers.length > 0 && (
                  <div>
                    <div className="mb-2 text-xs font-semibold uppercase text-slate-500">System Users</div>
                    <div className="flex flex-wrap gap-2">
                      {selectedUsers.map((user) => (
                        <span key={user.id} className="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                          {user.name} &lt;{user.email}&gt;
                        </span>
                      ))}
                    </div>
                  </div>
                )}

                {manualEmails.length > 0 && (
                  <div>
                    <div className="mb-2 text-xs font-semibold uppercase text-slate-500">Manual Emails</div>
                    <div className="flex flex-wrap gap-2">
                      {manualEmails.map((email) => (
                        <span key={email} className="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                          {email}
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
              )}
        </div>

        <div className="flex justify-end">
          <button type="submit" className="btn btn-primary" disabled={processing}>
            Save Schedule
          </button>
        </div>
      </form>
    </AuthenticatedLayout>
  )
}
