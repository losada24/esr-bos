import { useEffect, useRef, useState } from 'react'

type NotificationType = 'feeds' | 'reminders' | 'system'

interface CrmNotificationRow {
  id: number
  title: string
  body?: string | null
  actor?: string | null
  read_at?: string | null
  created_at_label?: string | null
  due_at?: string | null
  url?: string | null
}

interface NotificationPayload {
  unread_count: number
  feeds: CrmNotificationRow[]
  reminders: CrmNotificationRow[]
  system: CrmNotificationRow[]
}

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

const emptyPayload: NotificationPayload = {
  unread_count: 0,
  feeds: [],
  reminders: [],
  system: []
}

const tabLabels: Record<NotificationType, string> = {
  feeds: 'Feeds',
  reminders: 'Reminders',
  system: 'System Notifications'
}

export default function CrmNotificationsDropdown () {
  const [open, setOpen] = useState(false)
  const [activeTab, setActiveTab] = useState<NotificationType>('feeds')
  const [payload, setPayload] = useState<NotificationPayload>(emptyPayload)
  const [loading, setLoading] = useState(false)
  const wrapperRef = useRef<HTMLDivElement | null>(null)

  const loadNotifications = async () => {
    setLoading(true)
    try {
      const response = await fetch(route('crm-notifications.index'), {
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      const json = await response.json().catch(() => emptyPayload)
      if (response.ok) setPayload(json)
    } finally {
      setLoading(false)
    }
  }

  useEffect(() => {
    void loadNotifications()
  }, [])

  useEffect(() => {
    if (!open) return
    void loadNotifications()
  }, [open])

  useEffect(() => {
    const handleClick = (event: MouseEvent) => {
      if (!wrapperRef.current?.contains(event.target as Node)) {
        setOpen(false)
      }
    }

    document.addEventListener('mousedown', handleClick)
    return () => { document.removeEventListener('mousedown', handleClick) }
  }, [])

  const markRead = async (notification: CrmNotificationRow) => {
    if (!notification.read_at) {
      await fetch(route('crm-notifications.read', { notification: notification.id }), {
        method: 'POST',
        credentials: 'include',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      await loadNotifications()
    }

    if (notification.url) {
      window.location.href = notification.url
    }
  }

  const markAllRead = async () => {
    await fetch(route('crm-notifications.read-all'), {
      method: 'POST',
      credentials: 'include',
      headers: {
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken(),
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    await loadNotifications()
  }

  const rows = payload[activeTab] ?? []

  return (
    <div className="relative shrink-0" ref={wrapperRef}>
      <button
        type="button"
        className="relative flex h-9 w-9 items-center justify-center rounded-full bg-white-light/40 text-slate-600 hover:bg-white-light/90 hover:text-primary dark:bg-dark/40 dark:text-[#d0d2d6]"
        onClick={() => { setOpen((value) => !value) }}
        title="Notifications"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M15 17H9m9-2v-4a6 6 0 0 0-12 0v4l-2 2h16l-2-2Z" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" />
          <path d="M10 20a2 2 0 0 0 4 0" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
        </svg>
        {payload.unread_count > 0 && (
          <span className="absolute -right-1 -top-1 flex h-5 min-w-[20px] items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
            {payload.unread_count > 99 ? '99+' : payload.unread_count}
          </span>
        )}
      </button>

      {open && (
        <div className="absolute right-0 z-50 mt-2 w-[420px] max-w-[calc(100vw-24px)] overflow-hidden rounded-md border border-slate-200 bg-white shadow-xl">
          <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3">
            <h3 className="text-sm font-bold text-slate-900">Notifications</h3>
            <button type="button" className="text-xs font-semibold text-primary hover:underline" onClick={() => { void markAllRead() }}>
              Mark all read
            </button>
          </div>
          <div className="flex border-b border-slate-100 text-xs font-semibold">
            {(Object.keys(tabLabels) as NotificationType[]).map((tab) => (
              <button
                key={tab}
                type="button"
                className={`flex-1 px-3 py-3 text-center ${activeTab === tab ? 'border-b-2 border-success text-slate-900' : 'text-slate-500 hover:text-slate-800'}`}
                onClick={() => { setActiveTab(tab) }}
              >
                {tabLabels[tab]}
              </button>
            ))}
          </div>
          <div className="max-h-[420px] overflow-y-auto">
            {loading && <div className="px-4 py-8 text-center text-sm text-slate-500">Loading...</div>}
            {!loading && rows.length === 0 && (
              <div className="px-4 py-10 text-center text-sm text-slate-500">No notifications in this view.</div>
            )}
            {!loading && rows.map((item) => (
              <button
                key={item.id}
                type="button"
                className={`flex w-full gap-3 border-b border-slate-100 px-4 py-3 text-left hover:bg-slate-50 ${item.read_at ? 'bg-white' : 'bg-sky-50/60'}`}
                onClick={() => { void markRead(item) }}
              >
                <span className="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-100 text-xs font-bold text-sky-700">
                  {item.actor?.slice(0, 2).toUpperCase() ?? 'RM'}
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block text-sm font-semibold text-slate-900">{item.title}</span>
                  {item.body && <span className="mt-0.5 block text-xs text-slate-600">{item.body}</span>}
                  <span className="mt-1 block text-[11px] text-slate-400">{item.created_at_label}</span>
                </span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}
