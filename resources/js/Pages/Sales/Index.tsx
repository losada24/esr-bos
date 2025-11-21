import { useCallback, useEffect, useState } from 'react'
import type { Dispatch, SetStateAction } from 'react'
import { Head, Link } from '@inertiajs/react'
import { ReactSortable } from 'react-sortablejs'
import { type Role, type PageProps, type Pipelines, type Tasks } from '@/types'
import AuthenticatedCalendarLayout from '@/Layouts/AuthenticatedCalendarLayout'
import { isAccountManager, isAdmin, isServiceManager, isSupervisor, isInstaller, isPaymentCoordinator, isOwner } from '@/Utils/user'

import EditIcon from '@/Components/Icons/EditIcon'
import { tagClasses, type TagColor } from '@/Utils/tags'
import { PAYMENT_METHODS } from '@/Utils/constants'
import EyeIcon from '@/Components/Icons/EyeIcon'
import InfoTooltip from '@/Components/InfoTooltip'
import EstimateScheduleModal from './EstimateScheduleModal'
import FollowUpModal from './FollowUpModal'
import StandByNoteModal from './StandByNoteModal'
import PreContractNoteModal from './PreContractNoteModal'
import ContractSignedModal from './ContractSignedModal'
import LostContractModal from './LostContractModal'

export interface OwnerOption { id: number, name: string }

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

export default function Sales ({ auth, data, lossReasonFrontdesk, sources, order_types, owners, methods_of_payment, type_of_financing }: PageProps & { data: Pipelines[], lossReasonFrontdesk: string [], sources: string[], order_types: string[], owners: OwnerOption[], methods_of_payment: string[], type_of_financing: string[] }) {
  const IS_ADMIN = isAdmin(auth.user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(auth.user.roles.map((role: Role) => role.name))
  const IS_SUPERVISOR = isSupervisor(auth.user.roles.map((role: Role) => role.name))
  const IS_SERVICE_MANAGER = isServiceManager(auth.user.roles.map((role: Role) => role.name))
  const IS_INSTALLER = isInstaller(auth.user.roles.map((role: Role) => role.name))
  const IS_PAYMENT_COORDINATOR = isPaymentCoordinator(auth.user.roles.map((role: Role) => role.name))
  const IS_OWNER = isOwner(auth.user.roles.map((role: Role) => role.name))

  const ESTIMATE_STATUS = 'ESTIMATE & APPT SCHEDULE'

  const [projectList, setProjectListState] = useState<Pipelines[]>(() => sortPipelinesByRecentActivity(data))
  const [showModal, setShowModal] = useState(false)
  const [lostTask, setLostTask] = useState<Tasks | null>(null)
  const [showQuantifiedModal, setShowQuantifiedModal] = useState(false)
  const [previousStatusId, setPreviousStatusId] = useState<string | null>(null)
  const [scheduleModalOpen, setScheduleModalOpen] = useState(false)
  const [pendingMove, setPendingMove] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [scheduleInitialValues, setScheduleInitialValues] = useState<{ scheduleDate: string, ownerIds: number[] }>({ scheduleDate: '', ownerIds: [] })
  const [scheduleSaving, setScheduleSaving] = useState(false)
  const [scheduleError, setScheduleError] = useState<string | null>(null)
  const [followUpModalOpen, setFollowUpModalOpen] = useState(false)
  const [followUpInitialValues, setFollowUpInitialValues] = useState<{ projectAmount: string, note: string }>({ projectAmount: '', note: '' })
  const [followUpSaving, setFollowUpSaving] = useState(false)
  const [followUpError, setFollowUpError] = useState<string | null>(null)
  const [pendingFollowUp, setPendingFollowUp] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [standByModalOpen, setStandByModalOpen] = useState(false)
  const [standByInitialNote, setStandByInitialNote] = useState('')
  const [standBySaving, setStandBySaving] = useState(false)
  const [standByError, setStandByError] = useState<string | null>(null)
  const [pendingStandBy, setPendingStandBy] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [preContractModalOpen, setPreContractModalOpen] = useState(false)
  const [preContractInitialNote, setPreContractInitialNote] = useState('')
  const [preContractSaving, setPreContractSaving] = useState(false)
  const [preContractError, setPreContractError] = useState<string | null>(null)
  const [pendingPreContract, setPendingPreContract] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [contractSignedModalOpen, setContractSignedModalOpen] = useState(false)
  const [contractSignedInitialValues, setContractSignedInitialValues] = useState<{ projectName: string, projectAmount: string, downPayment: string, jobAddress: string, city: string, jobState: string, jobZip: string, methodOfPayment: string, typeOfFinancing: string }>({ projectName: '', projectAmount: '', downPayment: '', jobAddress: '', city: '', jobState: '', jobZip: '', methodOfPayment: '', typeOfFinancing: '' })
  const [contractSignedSaving, setContractSignedSaving] = useState(false)
  const [contractSignedError, setContractSignedError] = useState<string | null>(null)
  const [pendingContractSigned, setPendingContractSigned] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)
  const [lostContractModalOpen, setLostContractModalOpen] = useState(false)
  const [lostContractSaving, setLostContractSaving] = useState(false)
  const [lostContractError, setLostContractError] = useState<string | null>(null)
  const [pendingLostContract, setPendingLostContract] = useState<{ task: Tasks, oldStatus: string, newStatus: string } | null>(null)

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

  const FOLLOW_UP_STATUSES = ['FOLLOW UP', 'FOLLOW UP PROJECTS']
  const ownerOptions = owners ?? []
  const paymentMethods = methods_of_payment ?? []
  const financingOptions = type_of_financing ?? []

  const formatCurrency = (value: number) =>
    new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value)

  const restoreTaskToStatus = (task: Tasks, status: string) => {
    setProjectList(prev => prev.map(p => {
      const filtered = p.tasks.filter(t => Number(t.id) !== Number(task.id))
      if (p.id.toString() === status) {
        return {
          ...p,
          tasks: [...filtered, task]
        }
      }
      return { ...p, tasks: filtered }
    }))
  }

  const closeScheduleModal = (restoreTask = false) => {
    if (restoreTask && pendingMove) {
      restoreTaskToStatus(pendingMove.task, pendingMove.oldStatus)
    }
    setScheduleModalOpen(false)
    setScheduleError(null)
    setPendingMove(null)
    setScheduleInitialValues({ scheduleDate: '', ownerIds: [] })
  }

  const handleScheduleSubmit = async (values: { scheduleDate: string, ownerIds: number[] }) => {
    if (!pendingMove) return
    setScheduleSaving(true)
    setScheduleError(null)

    try {
      const response = await fetch(route('sales.assign_estimate', pendingMove.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          schedule_appointment: values.scheduleDate || null,
          owner_ids: values.ownerIds
        }),
      })

      if (!response.ok) {
        if (response.status === 422) {
          const data = await response.json()
          const messages = Object.values(data.errors ?? {}).flat()
          setScheduleError(typeof messages[0] === 'string' ? messages[0] : 'Revisa los datos ingresados.')
          return
        }
        throw new Error('No se pudo guardar la asignación.')
      }

      const data = await response.json()
      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingMove.task,
        schedule_appointment: data.order.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso,
        owner_ids: data.order.owner_ids,
        owners: data.order.owners
      })

      setProjectList(prev =>
        prev.map(pipeline => {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          if (pipeline.id.toString() === pendingMove.newStatus) {
            return { ...pipeline, tasks: [...filtered, updatedTask] }
          }
          return { ...pipeline, tasks: filtered }
        })
      )

      closeScheduleModal()
    } catch (error: any) {
      console.error('assign-estimate error', error)
      setScheduleError(error?.message ?? 'No se pudo guardar la asignación.')
    } finally {
      setScheduleSaving(false)
    }
  }

  const closeFollowUpModal = (restoreTask = false) => {
    if (restoreTask && pendingFollowUp) {
      restoreTaskToStatus(pendingFollowUp.task, pendingFollowUp.oldStatus)
    }
    setFollowUpModalOpen(false)
    setFollowUpError(null)
    setFollowUpSaving(false)
    setFollowUpInitialValues({ projectAmount: '', note: '' })
    setPendingFollowUp(null)
  }

  const closeStandByModal = (restoreTask = false) => {
    if (restoreTask && pendingStandBy) {
      restoreTaskToStatus(pendingStandBy.task, pendingStandBy.oldStatus)
    }
    setStandByModalOpen(false)
    setStandByError(null)
    setStandBySaving(false)
    setStandByInitialNote('')
    setPendingStandBy(null)
  }

  const closePreContractModal = (restoreTask = false) => {
    if (restoreTask && pendingPreContract) {
      restoreTaskToStatus(pendingPreContract.task, pendingPreContract.oldStatus)
    }
    setPreContractModalOpen(false)
    setPreContractError(null)
    setPreContractSaving(false)
    setPreContractInitialNote('')
    setPendingPreContract(null)
  }

  const closeContractSignedModal = (restoreTask = false) => {
    if (restoreTask && pendingContractSigned) {
      restoreTaskToStatus(pendingContractSigned.task, pendingContractSigned.oldStatus)
    }
    setContractSignedModalOpen(false)
    setContractSignedError(null)
    setContractSignedSaving(false)
    setContractSignedInitialValues({ projectName: '', projectAmount: '', downPayment: '', jobAddress: '', city: '', jobState: '', jobZip: '', methodOfPayment: '', typeOfFinancing: '' })
    setPendingContractSigned(null)
  }

  const closeLostContractModal = (restoreTask = false) => {
    if (restoreTask && pendingLostContract) {
      restoreTaskToStatus(pendingLostContract.task, pendingLostContract.oldStatus)
    }
    setLostContractModalOpen(false)
    setLostContractSaving(false)
    setLostContractError(null)
    setPendingLostContract(null)
  }

  const handleFollowUpSubmit = async (values: { projectAmount: string, note: string, attachments: File[] }) => {
    if (!pendingFollowUp) return

    setFollowUpSaving(true)
    setFollowUpError(null)

    try {
      const formData = new FormData()
      formData.append('status', pendingFollowUp.newStatus)
      formData.append('project_amount', values.projectAmount)
      formData.append('note', values.note)

      values.attachments?.forEach((file) => {
        formData.append('attachments[]', file)
      })

      const response = await fetch(route('sales.assign_follow_up', pendingFollowUp.task.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: formData
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const projectAmountValue = data.order.project_amount ?? values.projectAmount
      const normalizedProjectAmount = Number(
        typeof projectAmountValue === 'string'
          ? projectAmountValue.replace(/,/g, '')
          : projectAmountValue
      )

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingFollowUp.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingFollowUp.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingFollowUp.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingFollowUp.task.owner_ids,
        owners: data.order.owners ?? pendingFollowUp.task.owners,
        project_amount: Number.isFinite(normalizedProjectAmount) ? normalizedProjectAmount : 0
      })

      setProjectList(prev => prev.map(pipeline => {
        if (pipeline.id.toString() === pendingFollowUp.oldStatus) {
          return { ...pipeline, tasks: pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id)) }
        }

        if (pipeline.id.toString() === pendingFollowUp.newStatus) {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          return { ...pipeline, tasks: [...filtered, updatedTask] }
        }

        return pipeline
      }))

      closeFollowUpModal(false)
    } catch (error: any) {
      console.error('follow-up submit error', error)
      setFollowUpError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setFollowUpSaving(false)
    }
  }

  const handleStandBySubmit = async (values: { note: string }) => {
    if (!pendingStandBy) return

    setStandBySaving(true)
    setStandByError(null)

    try {
      const response = await fetch(route('sales.assign_stand_by', pendingStandBy.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note,
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingStandBy.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingStandBy.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingStandBy.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingStandBy.task.owner_ids,
        owners: data.order.owners ?? pendingStandBy.task.owners
      })

      setProjectList(prev => prev.map(pipeline => {
        if (pipeline.id.toString() === pendingStandBy.oldStatus) {
          return { ...pipeline, tasks: pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id)) }
        }

        if (pipeline.id.toString() === pendingStandBy.newStatus) {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          return { ...pipeline, tasks: [...filtered, updatedTask] }
        }

        return pipeline
      }))

      closeStandByModal(false)
    } catch (error: any) {
      console.error('stand-by submit error', error)
      setStandByError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setStandBySaving(false)
    }
  }

  const handlePreContractSubmit = async (values: { note: string }) => {
    if (!pendingPreContract) return

    setPreContractSaving(true)
    setPreContractError(null)

    try {
      const response = await fetch(route('sales.assign_pre_contract', pendingPreContract.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          note: values.note
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingPreContract.task,
        schedule_appointment: data.order.schedule_appointment ?? pendingPreContract.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingPreContract.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingPreContract.task.owner_ids,
        owners: data.order.owners ?? pendingPreContract.task.owners
      })

      setProjectList(prev => prev.map(pipeline => {
        if (pipeline.id.toString() === pendingPreContract.oldStatus) {
          return { ...pipeline, tasks: pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id)) }
        }

        if (pipeline.id.toString() === pendingPreContract.newStatus) {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          return { ...pipeline, tasks: [...filtered, updatedTask] }
        }

        return pipeline
      }))

      closePreContractModal(false)
    } catch (error: any) {
      console.error('pre-contract submit error', error)
      setPreContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setPreContractSaving(false)
    }
  }

  const handleContractSignedSubmit = async (values: { projectName: string, projectAmount: string, downPayment: string, jobAddress: string, city: string, jobState: string, jobZip: string, methodOfPayment: string, typeOfFinancing: string, attachments: File[] }) => {
    if (!pendingContractSigned) return

    setContractSignedSaving(true)
    setContractSignedError(null)

    try {
      const normalizedMethod = values.methodOfPayment.trim()
      const financingRequired = [PAYMENT_METHODS.FINANCED, PAYMENT_METHODS.CASH_AND_FINANCE]
      const normalizedFinancing = financingRequired.includes(normalizedMethod) ? values.typeOfFinancing.trim() : ''
      const normalizedDownPayment = values.downPayment?.trim() ?? ''
      const normalizedCity = values.city?.trim() ?? ''
      const normalizedState = values.jobState?.trim() ?? ''
      const normalizedZip = values.jobZip?.trim() ?? ''

      const formData = new FormData()
      formData.append('project_name', values.projectName)
      formData.append('project_amount', values.projectAmount)
      formData.append('job_address', values.jobAddress.trim())
      formData.append('city', normalizedCity)
      formData.append('job_state', normalizedState)
      formData.append('job_zip', normalizedZip)
      formData.append('method_of_payment', normalizedMethod)
      formData.append('type_of_financing', normalizedFinancing)
      formData.append('down_payment', normalizedDownPayment)

      values.attachments?.forEach((file) => {
        formData.append('attachments[]', file)
      })

      const response = await fetch(route('sales.assign_contract_signed', pendingContractSigned.task.id), {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: formData
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const data = payload

      const projectAmountValue = data.order.project_amount ?? values.projectAmount
      const normalizedProjectAmount = Number(
        typeof projectAmountValue === 'string'
          ? projectAmountValue.replace(/,/g, '')
          : projectAmountValue
      )

      const downPaymentRaw = data.order.down_payment ?? normalizedDownPayment
      const normalizedDownPaymentNumber = downPaymentRaw === '' || downPaymentRaw === null || downPaymentRaw === undefined
        ? null
        : Number(
            typeof downPaymentRaw === 'string'
              ? downPaymentRaw.replace(/,/g, '')
              : downPaymentRaw
          )

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingContractSigned.task,
        title: data.order.name ?? values.projectName,
        project_amount: Number.isFinite(normalizedProjectAmount) ? normalizedProjectAmount : 0,
        down_payment: Number.isFinite(normalizedDownPaymentNumber ?? NaN) ? normalizedDownPaymentNumber : null,
        job_address: data.order.job_address ?? values.jobAddress.trim(),
        city: data.order.city ?? (normalizedCity !== '' ? normalizedCity : null),
        job_state: data.order.job_state ?? (normalizedState !== '' ? normalizedState : null),
        job_zip: data.order.job_zip ?? (normalizedZip !== '' ? normalizedZip : null),
        method_of_payment: data.order.method_of_payment ?? normalizedMethod,
        type_of_financing: data.order.type_of_financing ?? normalizedFinancing,
        schedule_appointment: data.order.schedule_appointment ?? pendingContractSigned.task.schedule_appointment,
        schedule_appointment_iso: data.order.schedule_appointment_iso ?? pendingContractSigned.task.schedule_appointment_iso,
        owner_ids: data.order.owner_ids ?? pendingContractSigned.task.owner_ids,
        owners: data.order.owners ?? pendingContractSigned.task.owners
      })

      setProjectList(prev => prev.map(pipeline => {
        if (pipeline.id.toString() === pendingContractSigned.oldStatus) {
          return { ...pipeline, tasks: pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id)) }
        }

        if (pipeline.id.toString() === pendingContractSigned.newStatus) {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          return { ...pipeline, tasks: [...filtered, updatedTask] }
        }

        return pipeline
      }))

      closeContractSignedModal(false)
    } catch (error: any) {
      console.error('contract-signed submit error', error)
      setContractSignedError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setContractSignedSaving(false)
    }
  }

  const handleLostContractSubmit = async (values: { lossReason: string, notes: string }) => {
    if (!pendingLostContract) return

    setLostContractSaving(true)
    setLostContractError(null)

    try {
      const response = await fetch(route('sales.assign_lost_contract', pendingLostContract.task.id), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? ''
        },
        body: JSON.stringify({
          loss_reason_frontdesk: values.lossReason,
          notes: values.notes,
        })
      })

      const payload = await response.json().catch(() => null)

      if (!response.ok) {
        if (response.status === 422 && payload?.errors) {
          const messages = Object.values(payload.errors).flat()
          throw new Error(typeof messages[0] === 'string' ? messages[0] : 'Unable to update status.')
        }

        throw new Error(payload?.message ?? 'Unable to update status.')
      }

      if (!payload) {
        throw new Error('Unexpected server response.')
      }

      const updatedTask: Tasks = stampTaskAsUpdated({
        ...pendingLostContract.task
      })

      setProjectList(prev => prev.map(pipeline => {
        if (pipeline.id.toString() === pendingLostContract.oldStatus) {
          return { ...pipeline, tasks: pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id)) }
        }

        if (pipeline.id.toString() === pendingLostContract.newStatus) {
          const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(updatedTask.id))
          return { ...pipeline, tasks: [...filtered, updatedTask] }
        }

        return pipeline
      }))

      closeLostContractModal(false)
    } catch (error: any) {
      console.error('lost-contract submit error', error)
      setLostContractError(error?.message ?? 'No se pudo actualizar el estado.')
    } finally {
      setLostContractSaving(false)
    }
  }

  /* async function updateOrderStatus (orderId: number, newStatus: string) {
    const url = route('frontdesk.updateStatus', { order: orderId })

    const response = await fetch(url, {
      met aders: {
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
  } */

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
            <Link
              className="btn btn-primary"
              href={route('frontdesk.create')}
            >
              <span>Create Request</span>
            </Link>
          }
    >
      <Head title="Sales" />
      <div className="w-full h-[calc(100vh-140px)]">
        <div className="overflow-x-auto overflow-y-hidden h-full">
          <div className="flex gap-4 min-w-max h-full">
            {projectList.map((project: any) => {
              const totalProjectAmount = project.tasks.reduce((sum: number, task: Tasks) => {
                const raw = task.project_amount ?? 0
                const sanitized = typeof raw === 'string' ? raw.replace(/,/g, '') : raw
                const amount = Number(sanitized)
                return Number.isFinite(amount) ? sum + amount : sum
              }, 0)
              return (
                <div
                  key={project.id}
                  className="panel w-96 min-w-[24rem] flex-none flex flex-col h-full overflow-y-auto overflow-x-hidden"
                  data-group={project.id}
                >
                  <div className="sticky top-0 z-20 bg-white dark:bg-[#0b1220] pt-3 pb-2 shadow-sm">
                    <div className="flex items-start justify-between gap-3">
                      <h4 className="flex-1 text-xs font-semibold leading-tight text-slate-700 dark:text-white mb-0">
                        {project.title}
                      </h4>
                      <div className="flex flex-col items-end gap-1 text-[11px] font-semibold text-slate-600 dark:text-white">
                        <span className="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide shadow-sm dark:border-white-dark/30 dark:bg-white-dark/10">
                          <span className="text-[11px]">{project.tasks.length}</span>
                          <span>{project.tasks.length === 1 ? 'Order' : 'Orders'}</span>
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
                      list={project.tasks}
                      setList={() => {}} // Desactivado para manejarlo manualmente
                      group="shared"
                      animation={200}
                      onEnd={(evt) => {
                        const { item, from, to } = evt
                        const movedTaskId = item.getAttribute('data-id')
                        const oldStatus = from.closest('[data-group]')?.getAttribute('data-group') ?? ''
                        const newStatus = to.closest('[data-group]')?.getAttribute('data-group') ?? ''

                        if (oldStatus === newStatus) return

                        let movedTask!: Tasks

                        if (newStatus === ESTIMATE_STATUS) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setProjectList(prev =>
                            prev.map(pipeline => ({ ...pipeline, tasks: [...pipeline.tasks] }))
                          )

                          setPendingMove({ task: foundTask, oldStatus, newStatus })
                          setScheduleInitialValues({
                            scheduleDate: foundTask.schedule_appointment_iso ?? '',
                            ownerIds: (foundTask.owner_ids ?? []).map(Number)
                          })
                          setScheduleModalOpen(true)
                          return
                        }

                        if (FOLLOW_UP_STATUSES.includes(newStatus)) {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setFollowUpInitialValues({
                            projectAmount: foundTask.project_amount !== undefined && foundTask.project_amount !== null
                              ? String(foundTask.project_amount)
                              : '',
                            note: ''
                          })
                          setPendingFollowUp({ task: foundTask, oldStatus, newStatus })
                          setFollowUpModalOpen(true)
                          return
                        }

                        if (newStatus === 'STAND BY') {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setStandByInitialNote('')
                          setPendingStandBy({ task: foundTask, oldStatus, newStatus })
                          setStandByModalOpen(true)
                          return
                        }

                        if (newStatus === 'PRE CONTRACT APPOINTMENT') {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setPreContractInitialNote('')
                          setPendingPreContract({ task: foundTask, oldStatus, newStatus })
                          setPreContractModalOpen(true)
                          return
                        }

                        if (newStatus === 'CONTRACT SIGNED BY CLIENT') {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setContractSignedInitialValues({
                            projectName: foundTask.title ?? '',
                            projectAmount: foundTask.project_amount !== undefined && foundTask.project_amount !== null
                              ? String(foundTask.project_amount)
                              : '',
                            downPayment: foundTask.down_payment !== undefined && foundTask.down_payment !== null
                              ? String(foundTask.down_payment)
                              : '',
                            jobAddress: foundTask.job_address ?? '',
                            city: foundTask.city ?? '',
                            jobState: foundTask.job_state ?? '',
                            jobZip: foundTask.job_zip ?? '',
                            methodOfPayment: foundTask.method_of_payment ?? '',
                            typeOfFinancing: foundTask.type_of_financing ?? '',
                          })
                          setPendingContractSigned({ task: foundTask, oldStatus, newStatus })
                          setContractSignedModalOpen(true)
                          return
                        }

                        if (newStatus === 'LOST CONTRACT') {
                          const foundTask = projectList
                            .flatMap((pipeline) => pipeline.tasks)
                            .find((t) => Number(t.id) === Number(movedTaskId))

                          if (!foundTask) {
                            return
                          }

                          setPendingLostContract({ task: foundTask, oldStatus, newStatus })
                          setLostContractModalOpen(true)
                          return
                        }

                        setProjectList((prev) => {
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
                        })

                        if (!movedTask) return

                        if (newStatus === 'QUALIFIED') {
                          restoreTaskToStatus(movedTask, oldStatus)
                          setLostTask(movedTask)
                          setPreviousStatusId(oldStatus)
                          setShowQuantifiedModal(true)
                          return
                        }

                        if (newStatus === 'LOST REQUEST') {
                          restoreTaskToStatus(movedTask, oldStatus)
                          setLostTask(movedTask)
                          setPreviousStatusId(oldStatus)
                          setShowModal(true)
                          return
                        }

                        setProjectList(prev =>
                          prev.map(pipeline => {
                            const filtered = pipeline.tasks.filter(task => Number(task.id) !== Number(movedTask.id))
                            if (pipeline.id.toString() === newStatus) {
                              const stampedTask = stampTaskAsUpdated(movedTask)
                              return { ...pipeline, tasks: [...filtered, stampedTask] }
                            }
                            return { ...pipeline, tasks: filtered }
                          })
                        )

                        // TODO: update status for other columns when backend endpoint is ready
                      }}
                      ghostClass="sortable-ghost"
                      dragClass="sortable-drag"
                      className="min-h-[1px] space-y-4 pt-2"
                    >
                      {project.tasks.map((task: any) => {
                        return (
                          <div className="sortable-list " key={task.id} data-id={task.id}>
                            <div className="shadow bg-[#f4f4f4] dark:bg-white-dark/20 p-3 pb-4 rounded-md mb-5 space-y-2 cursor-move text-xs text-slate-600">
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
                                      task.tags.map((tag: any, i: number) => (
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

      <EstimateScheduleModal
        open={scheduleModalOpen && !!pendingMove}
        taskTitle={pendingMove?.task.title ?? ''}
        initialScheduleDate={scheduleInitialValues.scheduleDate}
        initialOwnerIds={scheduleInitialValues.ownerIds}
        ownerOptions={ownerOptions}
        error={scheduleError}
        saving={scheduleSaving}
        onClose={closeScheduleModal}
        onSubmit={handleScheduleSubmit}
      />

      <FollowUpModal
        open={followUpModalOpen && !!pendingFollowUp}
        taskTitle={pendingFollowUp?.task.title ?? ''}
        targetStatus={pendingFollowUp?.newStatus ?? ''}
        initialProjectAmount={followUpInitialValues.projectAmount}
        initialNote={followUpInitialValues.note}
        loading={followUpSaving}
        error={followUpError}
        onCancel={() => { closeFollowUpModal(true) }}
        onSubmit={handleFollowUpSubmit}
      />

      <StandByNoteModal
        open={standByModalOpen && !!pendingStandBy}
        taskTitle={pendingStandBy?.task.title ?? ''}
        initialNote={standByInitialNote}
        loading={standBySaving}
        error={standByError}
        onCancel={() => { closeStandByModal(true) }}
        onSubmit={handleStandBySubmit}
      />

      <PreContractNoteModal
        open={preContractModalOpen && !!pendingPreContract}
        taskTitle={pendingPreContract?.task.title ?? ''}
        initialNote={preContractInitialNote}
        loading={preContractSaving}
        error={preContractError}
        onCancel={() => { closePreContractModal(true) }}
        onSubmit={handlePreContractSubmit}
      />

      <ContractSignedModal
        open={contractSignedModalOpen && !!pendingContractSigned}
        taskTitle={pendingContractSigned?.task.title ?? ''}
        initialProjectName={contractSignedInitialValues.projectName}
        initialProjectAmount={contractSignedInitialValues.projectAmount}
        initialDownPayment={contractSignedInitialValues.downPayment}
        initialJobAddress={contractSignedInitialValues.jobAddress}
        initialCity={contractSignedInitialValues.city}
        initialJobState={contractSignedInitialValues.jobState}
        initialJobZip={contractSignedInitialValues.jobZip}
        initialMethodOfPayment={contractSignedInitialValues.methodOfPayment}
        initialTypeOfFinancing={contractSignedInitialValues.typeOfFinancing}
        paymentMethods={paymentMethods}
        financingOptions={financingOptions}
        loading={contractSignedSaving}
        error={contractSignedError}
        onCancel={() => { closeContractSignedModal(true) }}
        onSubmit={handleContractSignedSubmit}
      />

      <LostContractModal
        open={lostContractModalOpen && !!pendingLostContract}
        lossReasons={lossReasonFrontdesk}
        loading={lostContractSaving}
        error={lostContractError}
        onCancel={() => { closeLostContractModal(true) }}
        onSubmit={handleLostContractSubmit}
      />

      {/* <LostRequestModal
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
      /> */}
   {/* <QuantifiedModal
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
      /> */}
    </AuthenticatedCalendarLayout>
  )
}
