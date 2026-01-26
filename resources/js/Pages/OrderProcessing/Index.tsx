import { useCallback, useEffect, useRef, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ReactSortable } from 'react-sortablejs'
import { type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { tagClasses, type TagColor } from '@/Utils/tags'
import EyeIcon from '@/Components/Icons/EyeIcon'
import EditIcon from '@/Components/Icons/EditIcon'
import InfoTooltip from '@/Components/InfoTooltip'

const MONTH_LABELS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'] as const

const MONTH_INDEX: Record<string, number> = MONTH_LABELS.reduce((acc, label, index) => {
  const lower = label.toLowerCase()
  acc[lower] = index
  if (lower === 'sep') {
    acc.sept = index
  }
  return acc
}, {} as Record<string, number>)

const parseDisplayDateToTimestamp = (value?: string | null): number => {
  if (!value) return 0
  const trimmedValue = value.trim()
  if (!trimmedValue) return 0

  const match = trimmedValue.match(/^([A-Za-z]+)\s+(\d{1,2}),\s*(\d{4})\s+(\d{1,2}):(\d{2})\s*(AM|PM)$/i)
  if (match) {
    const [, monthLabel, dayStr, yearStr, hourStr, minuteStr, periodRaw] = match
    const monthIndex = MONTH_INDEX[monthLabel.toLowerCase()]
    const day = Number(dayStr)
    const year = Number(yearStr)
    let hours = Number(hourStr)
    const minutes = Number(minuteStr)
    const period = periodRaw.toUpperCase()

    if (
      monthIndex != null &&
      !Number.isNaN(day) &&
      !Number.isNaN(year) &&
      !Number.isNaN(hours) &&
      !Number.isNaN(minutes)
    ) {
      hours = hours % 12
      if (period === 'PM') {
        hours += 12
      }

      const timestamp = Date.UTC(year, monthIndex, day, hours, minutes)
      return Number.isNaN(timestamp) ? 0 : timestamp
    }
  }

  const fallback = Date.parse(trimmedValue)
  return Number.isNaN(fallback) ? 0 : fallback
}

const sortTasksByRecentActivity = (tasks: Tasks[] = []): Tasks[] => {
  return [...tasks].sort((taskA, taskB) => {
    const timestampA = parseDisplayDateToTimestamp(taskA?.date_edited ?? taskA?.date)
    const timestampB = parseDisplayDateToTimestamp(taskB?.date_edited ?? taskB?.date)
    return timestampB - timestampA
  })
}

const sortPipelinesByRecentActivity = (pipelines: Pipelines[] = []): Pipelines[] => {
  return pipelines.map(pipeline => ({
    ...pipeline,
    tasks: sortTasksByRecentActivity(pipeline.tasks ?? [])
  }))
}

const clonePipelines = (pipelines: Pipelines[] = []): Pipelines[] => {
  return pipelines.map(pipeline => ({
    ...pipeline,
    tasks: (pipeline.tasks ?? []).map(task => ({ ...task }))
  }))
}

const INFINITE_SCROLL_STATUSES = new Set(['CLOSED WON'])
const TASKS_PAGE_SIZE = 20
const SCROLL_THRESHOLD_PX = 120
type StatusPaginationState = { nextPage: number, loading: boolean }

const buildPaginationState = (pipelines: Pipelines[] = []): Record<string, StatusPaginationState> => {
  return pipelines.reduce<Record<string, StatusPaginationState>>((acc, pipeline) => {
    if (!INFINITE_SCROLL_STATUSES.has(pipeline.title)) return acc
    const key = pipeline.id == null ? (pipeline.title ?? '') : pipeline.id.toString()
    if (!key) return acc
    const loadedPages = pipeline.tasks.length ? Math.ceil(pipeline.tasks.length / TASKS_PAGE_SIZE) : 0
    acc[key] = {
      nextPage: loadedPages + 1,
      loading: false
    }
    return acc
  }, {})
}

const formatDateForDisplay = (date: Date): string => {
  const monthLabel = MONTH_LABELS[date.getMonth()] ?? MONTH_LABELS[0]
  const day = date.getDate().toString().padStart(2, '0')
  const year = date.getFullYear()
  let hours = date.getHours()
  const minutes = date.getMinutes().toString().padStart(2, '0')
  const period = hours >= 12 ? 'PM' : 'AM'
  hours = hours % 12
  if (hours === 0) hours = 12
  const hourDisplay = hours.toString().padStart(2, '0')
  return `${monthLabel} ${day}, ${year} ${hourDisplay}:${minutes} ${period}`
}

const stampTaskAsUpdated = (task: Tasks): Tasks => ({
  ...task,
  date_edited: formatDateForDisplay(new Date())
})

const OrderProcessing = ({ auth, data }: PageProps & { data: Pipelines[] }) => {
  const [pipelines, setPipelinesState] = useState<Pipelines[]>(() => sortPipelinesByRecentActivity(data))
  const [statusPagination, setStatusPagination] = useState<Record<string, StatusPaginationState>>(() => buildPaginationState(data))
  const dragSnapshotRef = useRef<Pipelines[] | null>(null)
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''

  useEffect(() => {
    const sorted = sortPipelinesByRecentActivity(data)
    setPipelinesState(sorted)
    setStatusPagination(buildPaginationState(sorted))
  }, [data])

  const setPipelines = useCallback<Dispatch<SetStateAction<Pipelines[]>>>((value) => {
    setPipelinesState(prevState => {
      const nextState = typeof value === 'function'
        ? (value as (prev: Pipelines[]) => Pipelines[])(prevState)
        : value

      return sortPipelinesByRecentActivity(nextState)
    })
  }, [setPipelinesState])

  const updatePipelineTasks = (pipelineId: Pipelines['id'], tasks: Tasks[]) => {
    setPipelines((prev) =>
      prev.map((pipeline) => {
        if (pipeline.id !== pipelineId) {
          return pipeline
        }
        const previousTasks = pipeline.tasks ?? []
        const nextTasks = tasks.map((task) => {
          const existed = previousTasks.some(prevTask => prevTask.id === task.id)
          return existed ? task : stampTaskAsUpdated(task)
        })
        const baseTotal = pipeline.total_tasks ?? previousTasks.length
        const delta = nextTasks.length - previousTasks.length
        const nextTotal = Math.max(0, baseTotal + delta)
        return { ...pipeline, tasks: nextTasks, total_tasks: nextTotal }
      })
    )
  }

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  const updateOrderStatus = async (orderId: number, status: string) => {
    const response = await fetch(route('frontdesk.updateStatus', { order: orderId }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify({ status })
    })

    const payload = await response.json().catch(() => null)

    if (!response.ok || !payload?.order) {
      throw new Error(payload?.message ?? 'Unable to update status.')
    }
  }

  const loadMoreTasks = useCallback(async (statusKey: string, nextPage: number) => {
    setStatusPagination(prev => ({
      ...prev,
      [statusKey]: {
        nextPage,
        loading: true
      }
    }))

    try {
      const response = await fetch(route('order-processing.tasks', { status: statusKey, page: nextPage, per_page: TASKS_PAGE_SIZE }), {
        headers: { Accept: 'application/json' }
      })

      if (!response.ok) {
        throw new Error('Error loading tasks')
      }

      const payload = await response.json()
      const incomingTasks = Array.isArray(payload?.tasks) ? payload.tasks as Tasks[] : []
      const totalTasks = typeof payload?.total === 'number' ? payload.total : null

      if (incomingTasks.length) {
        setPipelines(prev =>
          prev.map(p => {
            const pipelineKey = p.id == null ? (p.title ?? '') : p.id.toString()
            if (pipelineKey !== statusKey) return p
            const existingIds = new Set(p.tasks.map(task => task.id))
            const mergedTasks = [...p.tasks]
            incomingTasks.forEach(task => {
              if (!existingIds.has(task.id)) {
                mergedTasks.push(task)
              }
            })
            return {
              ...p,
              tasks: mergedTasks,
              ...(totalTasks != null ? { total_tasks: totalTasks } : {})
            }
          })
        )
      } else if (totalTasks != null) {
        setPipelines(prev =>
          prev.map(p => {
            const pipelineKey = p.id == null ? (p.title ?? '') : p.id.toString()
            return pipelineKey === statusKey ? { ...p, total_tasks: totalTasks } : p
          })
        )
      }

      setStatusPagination(prev => ({
        ...prev,
        [statusKey]: {
          nextPage: nextPage + 1,
          loading: false
        }
      }))
    } catch (error) {
      console.error('❌ Error al cargar mas tareas:', error)
      setStatusPagination(prev => ({
        ...prev,
        [statusKey]: {
          nextPage,
          loading: false
        }
      }))
    }
  }, [setPipelines, setStatusPagination])

  return (
    <AuthenticatedCalendarLayout auth={auth} printPanel={false}>
      <Head title="Order Processing" />
      <div className="w-full h-[calc(100vh-140px)]">
        <div className="h-full overflow-x-auto overflow-y-hidden">
          <div className="flex h-full min-w-max gap-4">
            {pipelines.map((pipeline) => {
              const statusKey = pipeline.id?.toString() ?? pipeline.title ?? ''
              const totalTasks = pipeline.total_tasks ?? pipeline.tasks.length
              const isInfiniteStatus = INFINITE_SCROLL_STATUSES.has(pipeline.title)
              const hasMoreTasks = isInfiniteStatus && pipeline.tasks.length < totalTasks
              const pagination = statusPagination[statusKey]
              const totalProjectAmount = pipeline.tasks.reduce((sum, task) => {
                const raw = task.project_amount ?? 0
                const sanitized = typeof raw === 'string' ? raw.replace(/,/g, '') : raw
                const amount = Number(sanitized)
                return Number.isFinite(amount) ? sum + amount : sum
              }, 0)

              return (
                <div
                  key={pipeline.id}
                  className="panel w-96 min-w-[24rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden"
                  data-group={pipeline.id}
                >
                  <div className="sticky top-0 z-10 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                      <h4 className="flex-1 text-xs font-semibold uppercase tracking-wide text-slate-700 dark:text-white mb-0">
                        {pipeline.title}
                      </h4>
                      <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white">
                        <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                          <span>{totalTasks}</span>
                          <span>{totalTasks === 1 ? 'Order' : 'Orders'}</span>
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-gradient-to-r from-sky-500/10 to-sky-600/10 px-2.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-200">
                          <span>Total</span>
                          <span>{formatCurrency(totalProjectAmount)}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div
                    className="flex-1 overflow-y-auto pr-2 pt-2"
                    onScroll={(event) => {
                      if (!isInfiniteStatus || !statusKey) return
                      if (!hasMoreTasks) return
                      if (pagination?.loading) return
                      const target = event.currentTarget
                      if (target.scrollHeight - target.scrollTop - target.clientHeight > SCROLL_THRESHOLD_PX) return
                      const nextPage = pagination?.nextPage ?? Math.floor(pipeline.tasks.length / TASKS_PAGE_SIZE) + 1
                      loadMoreTasks(statusKey, nextPage)
                    }}
                  >
                    <ReactSortable<Tasks>
                      list={pipeline.tasks}
                      setList={(newState) => updatePipelineTasks(pipeline.id, newState as Tasks[])}
                      group="order-processing"
                      animation={200}
                      onStart={() => {
                        dragSnapshotRef.current = clonePipelines(pipelines)
                      }}
                      onEnd={(evt) => {
                        const run = async () => {
                          const { item, from, to } = evt
                          const movedTaskId = item.getAttribute('data-id')
                          const oldStatus = from.closest('[data-group]')?.getAttribute('data-group')?.trim() ?? ''
                          const newStatus = to.closest('[data-group]')?.getAttribute('data-group')?.trim() ?? ''

                          if (!movedTaskId || !oldStatus || !newStatus || oldStatus === newStatus) {
                            dragSnapshotRef.current = null
                            return
                          }

                          const orderId = Number(movedTaskId)
                          if (!Number.isFinite(orderId)) {
                            dragSnapshotRef.current = null
                            return
                          }

                          try {
                            await updateOrderStatus(orderId, newStatus)
                          } catch (error) {
                            console.error('❌ Error al actualizar estado:', error)
                            if (dragSnapshotRef.current) {
                              setPipelines(dragSnapshotRef.current)
                            }
                          } finally {
                            dragSnapshotRef.current = null
                          }
                        }

                        void run()
                      }}
                      ghostClass="sortable-ghost"
                      dragClass="sortable-drag"
                      className="min-h-[1px] space-y-4 pt-2"
                    >
                      {pipeline.tasks.map((task) => {
                        const ownerNames = (task.owners ?? [])
                          .map((owner: any) => typeof owner?.name === 'string' ? owner.name.trim() : '')
                          .filter((name: string) => Boolean(name))
                        const ownersDisplay = ownerNames.length ? ownerNames.join(', ') : 'No owners assigned'
                        const ownersLabel = ownerNames.length === 1 ? 'Owner' : 'Owners'

                        return (
                          <div className="sortable-list" key={task.id} data-id={task.id}>
                              <div className="shadow bg-[#f4f4f4] dark:bg-white-dark/20 p-3 pb-4 rounded-md mb-5 space-y-2 cursor-move text-xs text-slate-600">
                                <div className="flex items-center justify-between w-full">
                                  <p className="flex items-center gap-2 break-all text-sm font-semibold text-slate-700 dark:text-white">
                                    {task.title}
                                  </p>
                              <div className="flex items-center gap-2 text-[11px]">
                                  <Link
                                    href={route('frontdesk.order_view', task.id)}
                                    title='Order View'
                                    className='flex items-center gap-1 hover:text-success'
                                  >
                                    <EyeIcon />
                                  </Link>
                                  <button
                                    onClick={() => {}}
                                    type="button"
                                    className="flex items-center gap-1 hover:text-info"
                                  >
                                    <EditIcon />
                                  </button>
                                  <InfoTooltip
                                    side="left"
                                    width={220}
                                    content={
                                      <div>
                                        <div style={{ fontWeight: 700, marginBottom: 6, fontSize: '15px', color: '#0f172a' }}>Information</div>
                                        <ul style={{ margin: 0, paddingLeft: 16, fontSize: '13px', color: '#1e293b', lineHeight: '1.6' }}>
                                          <li style={{ marginBottom: 6 }}>Phone: {task.phone ?? '—'}</li>
                                          <li style={{ marginBottom: 6 }}>Appt Date: {task.schedule_appointment ?? 'No Appt Scheduled'}</li>
                                          <li style={{ marginBottom: 6 }}>Owners: {ownersDisplay}</li>
                                        </ul>
                                      </div>
                                    }
                                  />
                                </div>
                              </div>
                              <div className="flex gap-2 items-center flex-wrap">
                                {task.tags?.length
                                  ? (
                                      task.tags.map((tag, index) => (
                                    <span
                                      key={`${task.id}-tag-${index}`}
                                      className={tagClasses((tag.color as TagColor) || 'gray')}
                                      title={tag.name}
                                    >
                                      <span className="truncate">{tag.name}</span>
                                    </span>
                                      ))
                                    )
                                  : (
                                  <span className="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-medium ring-1 bg-slate-100 text-slate-600 ring-slate-200">
                                    No Tags
                                  </span>
                                    )}
                              </div>
                              <p className="break-all">{task.date}</p>
                              {task.date_edited !== task.date && (
                                <p className="break-all">{task.date_edited}</p>
                              )}
                              <div className="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-0.5 text-amber-900 ring-1 ring-amber-300/80 shadow-sm">
                                  <span className="text-[10px] uppercase tracking-wide text-amber-700">{ownersLabel}</span>
                                  <span className="font-semibold">{ownersDisplay}</span>
                                </span>
                              </div>
                              {(task.project_amount !== undefined && task.project_amount !== null)
                                ? (
                                  <div className="mt-1 flex items-center justify-between gap-2">
                                    <p className="break-all font-semibold text-slate-700">
                                      {formatCurrency(Number(task.project_amount))}
                                    </p>
                                    {task.is_supply && (
                                      <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                        SUPPLY
                                      </span>
                                    )}
                                  </div>
                                  )
                                : (
                                  task.is_supply && (
                                    <div className="mt-1 flex justify-end">
                                      <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                        SUPPLY
                                      </span>
                                    </div>
                                  )
                                  )}
                            </div>
                          </div>
                        )
                      })}
                    </ReactSortable>
                  </div>
                </div>
              )
            })}
          </div>
        </div>
      </div>
    </AuthenticatedCalendarLayout>
  )
}

export default OrderProcessing
