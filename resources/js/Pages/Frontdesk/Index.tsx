import { useCallback, useEffect, useMemo, useRef, useState } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ReactSortable } from 'react-sortablejs'
import { type Role, type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner } from '@/Utils/user'

import EditIcon from '@/Components/Icons/EditIcon'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import LostRequestModal from './LostRequestModal'
import QuantifiedModal from './QuantifiedModal'
import EyeIcon from '@/Components/Icons/EyeIcon'
import { tagClasses, TagColor } from '@/Utils/tags'

export default function Frontdesk ({ auth, data, lossReasonFrontdesk, sources, order_types }: PageProps & { data: Pipelines[], lossReasonFrontdesk: string [], sources: string[], order_types: string[] }) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))

  const [projectList, setProjectList] = useState<Pipelines[]>(data)
  const [showModal, setShowModal] = useState(false)
  const [lostTask, setLostTask] = useState<Tasks | null>(null)
  const [showQuantifiedModal, setShowQuantifiedModal] = useState(false)
  const [previousStatusId, setPreviousStatusId] = useState<string | null>(null)

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
      <div className="'w-full h-[90vh] flex flex-col overflow-y-scroll">
        <div className="relative pt-5">
          <div className="overflow-x-auto w-full h-full">
              <div className="flex gap-4 min-w-max">
                  {projectList.map((project: any) => {
                    return (
                      <div key={project.id} className="panel w-80 flex-none" data-group={project.id}><div className="flex flex-col mb-5">
                        <h4 className="text-base font-semibold mb-2">{project.title} <span className="mt-1 inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] text-slate-600">
                        {project.tasks.length} {project.tasks.length === 1 ? 'Order' : 'Orders'}
                        </span></h4>
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
                                  /* if (movedTask) {
                                    setTimeout(() => {
                                      setLostTask(movedTask)
                                      setPreviousStatusId(oldStatus)

                                      if (newStatus === 'QUALIFIED') {
                                        setShowQuantifiedModal(true)
                                      }

                                      if (newStatus === 'LOST REQUEST') {
                                        setShowModal(true)
                                      }
                                    }, 0)

                                    return
                                  } */
                                  if (newStatus === 'QUALIFIED' || newStatus === 'LOST REQUEST') {
                                    setLostTask(movedTask)
                                    setPreviousStatusId(oldStatus)
                                    if (newStatus === 'QUALIFIED') setShowQuantifiedModal(true)
                                    if (newStatus === 'LOST REQUEST') setShowModal(true)
                                    return
                                  }

                                  /* if (movedTask) {
                                    setProjectList((prev) =>
                                      prev.map((pipeline) => {
                                        if (pipeline.id.toString() === newStatus) {
                                          return { ...pipeline, tasks: [...pipeline.tasks, movedTask] }
                                        }
                                        return pipeline
                                      })
                                    )

                                    updateOrderStatus(Number(movedTaskId), newStatus)
                                      .then(() => { console.log('✅ Estado actualizado en backend') })
                                      .catch((err) => { console.error('❌ Error al actualizar el estado:', err) })
                                  } */
                                  setProjectList(prev =>
                                    prev.map(p =>
                                      p.id.toString() === newStatus
                                        ? { ...p, tasks: [...p.tasks, movedTask] }
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
                                className="connect-sorting-content min-h-[150px]"
>
                                {project.tasks.map((task: any) => {
                                  console.log('tags →', task.tags)
                                  return (
                                        <div className="sortable-list " key={task.id} data-id={task.id}>
                                            <div className="shadow bg-[#f4f4f4] dark:bg-white-dark/20 p-3 pb-5 rounded-md mb-5 space-y-3 cursor-move">
                                                {task.image ? <img src="/assets/images/carousel1.jpeg" alt="images" className="h-32 w-full object-cover rounded-md" /> : ''}
                                                <div className="flex items-center justify-between w-full">
                                                  {/* Nombre + ícono */}
                                                  <p className="flex items-center gap-2 break-all text-base font-medium">
                                                  {task.title}
                                                  </p>

                                                  {/* Botones a la derecha */}
                                                  <div className="flex items-center gap-2">
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
                                            <span className="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ring-1 bg-slate-100 text-slate-600 ring-slate-200">
                                              No Tags
                                            </span>
                                              )
                                        })()}
                                      </div>
                                                <p className="break-all">{task.date}</p>
                                                {task.date_edited !== task.date && (
                                                  <p className="break-all">{task.date_edited}</p>
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
        // errors={FormikErrors<OrderFormValues>}
      />
    </AuthenticatedCalendarLayout>
  )
}
