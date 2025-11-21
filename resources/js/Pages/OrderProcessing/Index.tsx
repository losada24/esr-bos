import { useCallback, useEffect, useState } from 'react'
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

  useEffect(() => {
    setPipelinesState(sortPipelinesByRecentActivity(data))
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
        return { ...pipeline, tasks: nextTasks }
      })
    )
  }

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  return (
    <AuthenticatedCalendarLayout auth={auth} printPanel={false}>
      <Head title="Order Processing" />
      <div className="w-full h-[calc(100vh-140px)]">
        <div className="h-full overflow-x-auto overflow-y-hidden">
          <div className="flex h-full min-w-max gap-4">
            {pipelines.map((pipeline) => {
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
                          <span>{pipeline.tasks.length}</span>
                          <span>{pipeline.tasks.length === 1 ? 'Order' : 'Orders'}</span>
                        </span>
                        <span className="inline-flex items-center gap-1 rounded-md bg-gradient-to-r from-sky-500/10 to-sky-600/10 px-2.5 py-0.5 text-[10px] font-semibold text-sky-700 dark:text-sky-200">
                          <span>Total</span>
                          <span>{formatCurrency(totalProjectAmount)}</span>
                        </span>
                      </div>
                    </div>
                  </div>
                  <div className="flex-1 overflow-y-auto pr-2 pt-2">
                    <ReactSortable<Tasks>
                      list={pipeline.tasks}
                      setList={(newState) => updatePipelineTasks(pipeline.id, newState as Tasks[])}
                      group="order-processing"
                      animation={200}
                      ghostClass="sortable-ghost"
                      dragClass="sortable-drag"
                      className="min-h-[1px] space-y-4 pt-2"
                    >
                      {pipeline.tasks.map((task) => (
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
                                        <li style={{ marginBottom: 6 }}>Owners: {task.owners?.length ? task.owners.map((owner: any) => owner.name).join(', ') : 'No owners assigned'}</li>
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
                      ))}
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
