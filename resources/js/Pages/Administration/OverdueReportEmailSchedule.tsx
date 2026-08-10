import React, { useMemo, useState } from 'react'
import { Head, useForm } from '@inertiajs/react'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout'
import { type PageProps } from '@/types'

interface ScheduleUser {
  id: number
  name: string
  email: string
}

interface SchedulePayload {
  enabled: boolean
  send_time: string
  timezone: string
  days_of_week: string[]
  user_recipient_ids: number[]
  manual_recipients: string[]
  last_sent_at: string | null
}

type Props = PageProps & {
  schedule: SchedulePayload
  users: ScheduleUser[]
  days: string[]
}

const titleCase = (value: string): string => value.charAt(0).toUpperCase() + value.slice(1)

const parseManualEmails = (value: string): string[] => (
  value
    .split(/[\s,;]+/)
    .map((email) => email.trim())
    .filter(Boolean)
)

const toggleString = (values: string[], value: string): string[] => (
  values.includes(value) ? values.filter((item) => item !== value) : [...values, value]
)

const toggleNumber = (values: number[], value: number): number[] => (
  values.includes(value) ? values.filter((item) => item !== value) : [...values, value]
)

export default function OverdueReportEmailSchedule ({ auth, schedule, users, days }: Props) {
  const manualRecipientsText = schedule.manual_recipients.join('\n')
  const [search, setSearch] = useState('')
  const { data, setData, put, processing } = useForm({
    enabled: schedule.enabled,
    send_time: schedule.send_time,
    days_of_week: schedule.days_of_week,
    user_recipient_ids: schedule.user_recipient_ids,
    manual_recipients_text: manualRecipientsText
  })

  const filteredUsers = useMemo(() => {
    const term = search.trim().toLowerCase()
    if (term === '') return users

    return users.filter((user) => (
      user.name.toLowerCase().includes(term) ||
      user.email.toLowerCase().includes(term)
    ))
  }, [search, users])

  const selectedUsers = useMemo(() => (
    users.filter((user) => data.user_recipient_ids.includes(user.id))
  ), [users, data.user_recipient_ids])
  const manualRecipients = useMemo(() => parseManualEmails(data.manual_recipients_text), [data.manual_recipients_text])
  const totalRecipients = selectedUsers.length + manualRecipients.length

  const submit = (event: React.FormEvent) => {
    event.preventDefault()
    put(route('administration.overdue-report-email-schedule.update'))
  }

  return (
    <AuthenticatedLayout auth={auth} pageTitle="Overdue Report Email Schedule">
      <Head title="Overdue Report Email Schedule" />

      <form onSubmit={submit} className="space-y-6">
        <h1 className="text-xl font-semibold text-slate-800">Overdue Report Email Schedule</h1>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Status</p>
            <label className="mt-4 inline-flex items-center gap-3 text-sm font-semibold text-slate-700">
              <input
                type="checkbox"
                checked={data.enabled}
                onChange={(event) => { setData('enabled', event.target.checked) }}
                className="form-checkbox"
              />
              Automatic email enabled
            </label>
          </div>

          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Time</p>
            <div className="mt-4 flex items-center gap-3">
              <input
                type="time"
                value={data.send_time}
                onChange={(event) => { setData('send_time', event.target.value) }}
                className="form-input w-40"
              />
              <span className="text-sm font-medium text-slate-500">{schedule.timezone}</span>
            </div>
          </div>

          <div className="panel">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Last Send</p>
            <p className="mt-4 text-sm font-semibold text-slate-800">{schedule.last_sent_at ?? 'Never'}</p>
          </div>
        </div>

        <div className="panel">
          <p className="mb-4 text-sm font-semibold text-slate-800">Send Days</p>
          <div className="flex flex-wrap gap-2">
            {days.map((day) => (
              <button
                key={day}
                type="button"
                onClick={() => { setData('days_of_week', toggleString(data.days_of_week, day)) }}
                className={data.days_of_week.includes(day) ? 'btn btn-primary' : 'btn btn-outline-primary'}
              >
                {titleCase(day)}
              </button>
            ))}
          </div>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
          <div className="panel">
            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
              <div>
                <p className="text-sm font-semibold text-slate-800">System Users</p>
                <p className="text-xs font-medium text-slate-400">{users.length} of {users.length} users</p>
              </div>
              <input
                type="text"
                value={search}
                onChange={(event) => { setSearch(event.target.value) }}
                placeholder="Search users..."
                className="form-input sm:w-64"
              />
            </div>

            <div className="max-h-[360px] space-y-2 overflow-y-auto pr-2">
              {filteredUsers.map((user) => (
                <label key={user.id} className="flex cursor-pointer items-center gap-4 rounded-md border border-slate-100 px-3 py-3 hover:bg-slate-50">
                  <input
                    type="checkbox"
                    checked={data.user_recipient_ids.includes(user.id)}
                    onChange={() => { setData('user_recipient_ids', toggleNumber(data.user_recipient_ids, user.id)) }}
                    className="form-checkbox"
                  />
                  <span>
                    <span className="block text-sm font-semibold text-slate-700">{user.name}</span>
                    <span className="block text-xs font-medium text-slate-500">{user.email}</span>
                  </span>
                </label>
              ))}
            </div>
          </div>

          <div className="panel">
            <label htmlFor="manual_emails" className="mb-3 block text-sm font-semibold text-slate-800">Manual Emails</label>
            <textarea
              id="manual_emails"
              value={data.manual_recipients_text}
              onChange={(event) => { setData('manual_recipients_text', event.target.value) }}
              className="form-textarea min-h-[360px]"
              placeholder="email@example.com, another@example.com"
            />
          </div>
        </div>

        <div className="panel">
          <div className="mb-4 flex items-center justify-between">
            <p className="text-sm font-semibold text-slate-800">Recipients</p>
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-500">{totalRecipients} total</span>
          </div>

          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">System Users</p>
          <div className="mb-4 flex flex-wrap gap-2">
            {selectedUsers.map((user) => (
              <span key={user.id} className="rounded-full border border-sky-200 bg-sky-50 px-3 py-1 text-xs font-semibold text-primary">
                {user.name} &lt;{user.email}&gt;
              </span>
            ))}
            {selectedUsers.length === 0 && <span className="text-sm text-slate-400">No system users selected.</span>}
          </div>

          <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Manual Emails</p>
          <div className="flex flex-wrap gap-2">
            {manualRecipients.map((email) => (
              <span key={email} className="rounded-full border border-slate-200 bg-slate-50 px-3 py-1 text-xs font-semibold text-slate-600">
                {email}
              </span>
            ))}
            {manualRecipients.length === 0 && <span className="text-sm text-slate-400">No manual emails added.</span>}
          </div>
        </div>

        <div className="flex justify-end">
          <button type="submit" className="btn btn-primary" disabled={processing}>
            {processing ? 'Saving...' : 'Save Schedule'}
          </button>
        </div>
      </form>
    </AuthenticatedLayout>
  )
}
