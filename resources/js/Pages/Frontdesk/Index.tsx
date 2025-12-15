import { useCallback, useEffect, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ReactSortable } from 'react-sortablejs'
import { type Role, type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner } from '@/Utils/user'

import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import LostRequestModal from './LostRequestModal'
import QuantifiedModal from './QuantifiedModal'
import RequestStandByModal from './RequestStandByModal'
import EyeIcon from '@/Components/Icons/EyeIcon'
import { tagClasses, type TagColor } from '@/Utils/tags'
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

const TWENTY_FOUR_HOURS_IN_MS = 24 * 60 * 60 * 1000
const SEVENTY_TWO_HOURS_IN_MS = 72 * 60 * 60 * 1000

const normalizePipelineIdentifier = (pipeline: Pick<Pipelines, 'id' | 'title'>): string => {
  const normalizedTitle = pipeline.title?.toUpperCase() ?? ''
  const normalizedId = (pipeline.id == null ? '' : pipeline.id.toString()).toUpperCase()
  return normalizedTitle || normalizedId
}

const STALE_STATUS_RULES: Record<string, { threshold: number, className: string }> = {
  'NEW REQUEST': {
    threshold: TWENTY_FOUR_HOURS_IN_MS,
    className: 'bg-red-200 dark:bg-red-500/40'
  },
  'REQUEST FOLLOW UP': {
    threshold: SEVENTY_TWO_HOURS_IN_MS,
    className: 'bg-red-200 dark:bg-red-500/40'
  }
}

const getStatusTimestamp = (task: Tasks): number => {
  const isoTimestamp = task.status_created_at_iso != null ? Date.parse(task.status_created_at_iso) : Number.NaN
  if (!Number.isNaN(isoTimestamp)) return isoTimestamp
  return parseDisplayDateToTimestamp(task?.date)
}

const getStaleStatusClass = (pipeline: Pick<Pipelines, 'id' | 'title'>, task: Tasks): string | null => {
  const pipelineKey = normalizePipelineIdentifier(pipeline)
  if (!pipelineKey) return null
  const rule = STALE_STATUS_RULES[pipelineKey]
  if (!rule) return null
  const createdTimestamp = getStatusTimestamp(task)
  if (!createdTimestamp) return null
  return (Date.now() - createdTimestamp) >= rule.threshold ? rule.className : null
}

export default function Frontdesk ({
  auth,
  data,
  lossReasonFrontdesk,
  sources,
  order_types,
  frame_colors,
  glass_colors,
  glass_types,
  glass_coatings,
  languages
}: PageProps & {
  data: Pipelines[]
  lossReasonFrontdesk: string[]
  sources: string[]
  order_types: string[]
  frame_colors: string[]
  glass_colors: string[]
  glass_types: string[]
  glass_coatings: string[]
  languages: string[]
}) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))

  const [projectList, setProjectListState] = useState<Pipelines[]>(() => sortPipelinesByRecentActivity(data))
  const [showModal, setShowModal] = useState(false)
  const [lostTask, setLostTask] = useState<Tasks | null>(null)
  const [showQuantifiedModal, setShowQuantifiedModal] = useState(false)
  const [showRequestStandByModal, setShowRequestStandByModal] = useState(false)
  const [previousStatusId, setPreviousStatusId] = useState<string | null>(null)

  useEffect(() => {
    setProjectListState(sortPipelinesByRecentActivity(data))
  }, [data])

  const setProjectList = useCallback<Dispatch<SetStateAction<Pipelines[]>>>((value) => {
    setProjectListState(prevState => {
      const nextState = typeof value === 'function'
        ? (value as (prev: Pipelines[]) => Pipelines[])(prevState)
        : value

      return sortPipelinesByRecentActivity(nextState)
    })
  }, [setProjectListState])

  async function updateOrderStatus (orderId: number, newStatus: string) {
    const url = route('frontdesk.updateStatus', { order: orderId })

    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        Accept: 'application/json'
      },
      body: JSON.stringify({ status: newStatus })
    })

    if (!response.ok) {
      const errorData = await response.json()
      throw new Error(errorData.message || 'Error updating status')
    }
    return await response.json()
  }

  /* const loadEvents = (date: Date) => {
    const year = date.getFullYear()
    const month = date.getMonth() + 1
    const getEventsRoute = route('dashboard.get_events', { year, month, service: calendarFilter.service, status: calendarFilter.status, ...(calendarFilter.name !== 'all' && { name: calendarFilter.name }) })
    getJson(getEventsRoute, (events) => {
      setEvents(events)
    }, 'json')
  } */

  return (
    <AuthenticatedCalendarLayout
      auth={auth}
      printPanel={false}
      actions={
         <div className="flex gap-2">
            <Link
              className="btn btn-primary"
              href={route('frontdesk.create')}
            >
              <span>Create Request</span>
            </Link>

            <Link
              className="btn btn-primary"
              href={route('frontdesk.create-qualified')}
            >
              <span>Create Order</span>
            </Link>
            </div>
          }
    >
      <Head title="Frontdesk" />
      <div className="w-full h-[calc(100vh-140px)]">
          <div className="overflow-x-auto  overflow-y-hidden h-full">
              <div className="flex gap-4 min-w-max h-full">
                  {projectList.map((project: any) => {
                    return (
                      <div key={project.id} className="panel w-80 min-w-[20rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden" data-group={project.id}>
                        <div className="sticky top-0 z-20 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                          <div className="flex items-start justify-between gap-3">
                            <h4 className="flex-1 text-xs font-semibold leading-tight text-slate-700 dark:text-white mb-0">
                              {project.title}
                            </h4>
                            <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white shrink-0">
                              <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                                <span className="text-[11px]">{project.tasks.length}</span>
                                <span>{project.tasks.length === 1 ? 'Order' : 'Orders'}</span>
                              </span>
                            </div>
                          </div>
                        </div>
                        <div className="flex-1 overflow-y-auto pr-2 pt-2">
                          <ReactSortable<Tasks>
                                list={project.tasks}
                                setList={() => {}} // Desactivado para manejarlo manualmente
                                group="shared"
                                animation={200}
                                onEnd={(evt) => {
                                  const { item, from, to } = evt
                                  // const movedTaskId = item.getAttribute('data-id')
                                  const movedTaskIdAttr = item.getAttribute('data-id')
                                  if (!movedTaskIdAttr) return// No hay ID, no seguimos
                                  const movedTaskId = Number(movedTaskIdAttr)
                                  const oldStatus = from.closest('[data-group]')?.getAttribute('data-group') ?? ''
                                  const newStatus = to.closest('[data-group]')?.getAttribute('data-group') ?? ''

                                  if (oldStatus === newStatus) return

                                  let movedTask!: Tasks

                                  /* setProjectList((prev) => {
                                    const updatedList = prev.map((pipeline) => {
                                      if (pipeline.id.toString() === oldStatus) {
                                        const newTasks = pipeline.tasks.filter((t) => {
                                          if (Number(t.id) === Number(movedTaskId)) {
                                            movedTask = t
                                            return false
                                          }
                                          return true
                                        })
                                        return { ...pipeline, tasks: newTasks }
                                      }
                                      return pipeline
                                    })
                                    return updatedList
                                  }) */
                                  setProjectList(prev =>
                                    prev.map(p => {
                                      if (p.id.toString() === oldStatus) {
                                        const remaining = p.tasks.filter(t => {
                                          if (Number(t.id) === movedTaskId) {
                                            movedTask = t
                                            return false
                                          }
                                          return true
                                        })
                                        return { ...p, tasks: remaining }
                                      }
                                      return p
                                    })
                                  )

                                  /* if (newStatus === 'LOST REQUEST' && movedTask) {
                                    setLostTask(movedTask)
                                    setShowModal(true)
                                    return // detenemos aquí, no movemos aún
                                  } */

                                  if (!movedTask) return
                                  if (newStatus === 'QUALIFIED') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowQuantifiedModal(true)
                                    return
                                  }
                                  if (newStatus === 'LOST REQUEST') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowModal(true)
                                    return
                                  }
                                  if (newStatus === 'REQUEST STAND BY') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    setShowRequestStandByModal(true)
                                    return
                                  }
                                  const movedTaskWithUpdatedDate = {
                                    ...movedTask,
                                    date_edited: formatDateForDisplay(new Date())
                                  }
                                  setProjectList(prev =>
                                    prev.map(p =>
                                      p.id.toString() === newStatus
                                        ? { ...p, tasks: [...p.tasks, movedTaskWithUpdatedDate] }
                                        : p
                                    )
                                  )

                                  // 4) Actualizar backend y revertir si falla
                                  updateOrderStatus(movedTaskId, newStatus)
                                    .then(() => { console.log('✅ Estado actualizado en backend') })
                                    .catch(err => {
                                      console.error('❌ Error al actualizar el estado:', err)
                                      // revertir
                                      setProjectList(prev =>
                                        prev.map(p => {
                                          if (p.id.toString() === newStatus) {
                                            return { ...p, tasks: p.tasks.filter(t => Number(t.id) !== movedTaskId) }
                                          }
                                          if (p.id.toString() === oldStatus) {
                                            return { ...p, tasks: [...p.tasks, movedTask] }
                                          }
                                          return p
                                        })
                                      )
                                    })
                                }}
                                ghostClass="sortable-ghost"
                                dragClass="sortable-drag"
                                className="min-h-[1px] space-y-4  pt-2"
                                >
                                {project.tasks.map((task: Tasks) => {
                                  console.log('tags →', task.tags)
                                  const staleStatusClass = getStaleStatusClass(project, task)
                                  const cardBackgroundClass = staleStatusClass ?? 'bg-[#f4f4f4] dark:bg-white-dark/20'
                                  return (
                                        <div className="sortable-list " key={task.id} data-id={task.id}>
                                            <div className={`shadow ${cardBackgroundClass} p-3 pb-4 rounded-md space-y-2 cursor-move text-xs text-slate-600`}>
                                                {task.image ? <img src="/assets/images/carousel1.jpeg" alt="images" className="h-32 w-full object-cover rounded-md" /> : ''}
                                                <div className="flex items-center justify-between w-full">
                                                  <p className="flex items-center gap-2 break-all text-sm font-semibold text-slate-700">
                                                    {task.title}
                                                  </p>

                                                  {/* Botones a la derecha */}
                                                <div className="flex items-center gap-2 text-[11px]">
                                                <Link
                                                  href={route('frontdesk.order_view', task.id)}
                                                  title='Order View'
                                                  className='flex items-center gap-1 hover:text-success'
                                                >
                                                  <EyeIcon />
                                                </Link>
                                                    <button
                                                      onClick={() => ' '}
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
                                                            <li style={{ marginBottom: 6 }}>Created By: {task.created_by ?? 'Unknown'}</li>

                                                          </ul>
                                                        </div>
                                                      }
                                                    />
                                                  </div>
                                                </div>
                                         <div className="flex gap-2 items-center flex-wrap">
                                        {(() => {
                                          const tagsArr = Array.isArray(task.tags)
                                            ? task.tags
                                            : Object.values(task.tags ?? {}) // ← si viene como objeto, conviértelo en array

                                          return tagsArr.length
                                            ? (
                                                tagsArr.map((tag: any, i: number) => (
                                              <span
                                                key={i}
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
                                              )
                                        })()}
                                      </div>
                                                <p className="break-all">{task.date}</p>
                                                {task.date_edited !== task.date && (
                                                  <p className="break-all">{task.date_edited}</p>
                                                )}
                                                {task.is_supply && (
                                                  <div className="mt-1 flex justify-end">
                                                    <span className="text-xs font-bold uppercase tracking-wide text-sky-600">
                                                      SUPPLY
                                                    </span>
                                                  </div>
                                                )}
                                               {/* <p className="break-all">{formatPrice(Number(task.precio))}</p> */}
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
      <LostRequestModal
        lostTask={lostTask}
        showModal={showModal}
        onClose={() => {
          setShowModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
        setProjectList={setProjectList}
        updateOrderStatus={updateOrderStatus}
        lostStatusId="LOST REQUEST"
        lossReasonFrontdesk={lossReasonFrontdesk}
         previousStatusId={previousStatusId}
      />
    <QuantifiedModal
        showModal={showQuantifiedModal}
        onClose={() => {
          setShowQuantifiedModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
        task={lostTask}
        setProjectList={setProjectList}
        updateOrderStatus={updateOrderStatus}
        lostStatusId="QUALIFIED"
        lossReasonFrontdesk={lossReasonFrontdesk}
        sources={sources ?? []}
        previousStatusId={previousStatusId}
        order_types={order_types ?? []}
        frame_colors={frame_colors ?? []}
        glass_colors={glass_colors ?? []}
        glass_types={glass_types ?? []}
        glass_coatings={glass_coatings ?? []}
        languages={languages ?? []}
        // errors={FormikErrors<OrderFormValues>}
      />
      <RequestStandByModal
        task={lostTask}
        showModal={showRequestStandByModal}
        previousStatusId={previousStatusId}
        setProjectList={setProjectList}
        onClose={() => {
          setShowRequestStandByModal(false)
          setLostTask(null)
          setPreviousStatusId(null)
        }}
      />
    </AuthenticatedCalendarLayout>
  )
}
