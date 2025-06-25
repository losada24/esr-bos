import React, { useState, useEffect } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order, type InstallationTeam, type User, type Role, Attachment } from '@/types'
import { formatPrice } from '@/Utils/price'
import { type OrdenEvent } from './DashboardCommon'
import InputError from '@/Components/InputError'
import Select, { type SingleValue } from 'react-select'
import Flatpickr from 'react-flatpickr'
import 'flatpickr/dist/flatpickr.css'
import PrimaryButton from '@/Components/PrimaryButton'
import { router } from '@inertiajs/react'

import { Dialog } from '@headlessui/react'
import InputLabel from '@/Components/InputLabel'
import { get } from 'http'
import DeleteIcon from '@/Components/Icons/DeleteIcon'
import ExportIcon from '@/Components/Icons/ExportIcon'
import { Field } from 'formik'
import { inspect } from 'util'


const EventModal = ({
  showModal,
  onClose,
  id,
  isAdminOrAccountManager,
  isSupervisor,
  isInstaller,
  isServiceManager,
  isPaymentCoordinator,
  installation_teams,
  supervisors,
  isOwner,
  status,
  attachments
  // auth

}: {
  showModal: boolean
  isSupervisor: boolean
  isInstaller: boolean
  isOwner: boolean
  onClose: CallableFunction
  id: number
  isPaymentCoordinator: boolean
  isAdminOrAccountManager: boolean
  isServiceManager: boolean
  installation_teams: InstallationTeam[]
  supervisors: User[]
  status: string[]
  attachments?: File[]
}) => {
  const defaultState = {
    client_id: 0,
    entry_date: null,
    installation_date: null,
    delivery_date: null,
    payment_factory_date: null,
    contract_signing_date: null,
    eta_date: null,
    installation_end_date: null,
    inspection_date: null,
    finish_date: null,
    service_date: null,
    final_inspection_date: null,
    pending_collect: null,
    complete_date: null,
    material_received_date: null,
    installation_team_id: [],
    additional_travel_costs: 0,
    type_of_work_id: 0,
    type_of_housing_id: 0,
    city_permits: false,
    association_permits: false,
    equipment_rental: false,
    notes: '',
    work_team_notes: '',
    order_colors: [],
    installation_teams: [],
    supervisor_id: 0,
    travel_cost_id: 0,
    duration_of_work_id: 0,
    method_of_payment: '',
    type_of_financing: '',
    cost_delivery: 0,
    cost_city_fee: 0,
    project_amount: 0,
    service: '',
    owners: [],
    order_products: [],
    attachments: [],
    status: '',
    hide_on_weekends: false,
    pre_inspection: false,
    inspection: false,
    walk_trough: false,
    partial_payment_installation: false,
    final_payment_installation: false,
    pre_inspection_attach: [],
    inspection_attach: [],
    walk_trough_attach: []
  }

  /* interface PageProps {
    success?: boolean
    message?: string | null // Define el tipo del mensaje
  } */
  const [event, setEvent] = useState<Order | null>(null)
  const [isVipClient, setIsVipClient] = useState<boolean>(false)
  const [editableData, setEditableData] = useState<any>(defaultState)
  const [isLoading, setIsLoading] = useState(false)
  const [attachmentsArray, setAttachmentsArray] = useState<File[]>(attachments ?? [])
  const [attachmentPreInspection, SetAttachmentPreInspection] = useState<File []>([])
  const [attachmentInspection, SetAttachmentInspection] = useState<File []>([])
  const [attachmentIWalkTrough, SetAttachmentWalkTrough] = useState<File []>([])
  const [attachmentsList, setAttachmentsList] = useState<any[]>([])
  const [message, setMessage] = useState<string | null>(null)

  const removeAttachmentProduct = (index: number) => {
    if (confirm('Are you sure you want to delete this attachment?')) {
      router.delete(route('order.drop_attachment', { id: attachmentsList[index].id }), {
        onSuccess: (page: any) => {
          if (page.props.flash.success != null) {
            const aux = attachmentsList.filter((_, i) => i !== index)
            setAttachmentsList(aux)
          } else {
            setMessage(page.props.flash.error)
          }
        },
        onError: (error: any) => {
          const errorMessage = error.response?.data?.message || 'An unexpected error occurred.'
          // showErrorModal(errorMessage) // Llama a una función para mostrar el modal con el mensaje
          alert(errorMessage)
        }
      })
    }
  }

  useEffect(() => {
    if (id === 0) {
      setEditableData(defaultState)
      setEvent(null)
    } else if (id !== 0 && showModal) {
      // setIsLoading(true)
      const url = route('dashboard.get_event', { id })
      fetch(url)
        .then(async (response) => await response.json())
        .then((data: Order) => {
          setEvent(data)
          // console.log(data.attachments)
          setAttachmentsList(data.attachments ?? [])
          // const installationDate = new Date(data.installation_date ?? new Date())
          // const duration = data?.duration_of_work?.number_of_day ?? 0
          // const endDate = new Date(installationDate.setDate(installationDate.getDate() + duration - 1))
          setEditableData({
            entry_date: data.entry_date ?? null,
            contract_signing_date: data.contract_signing_date ?? null,
            payment_factory_date: data.payment_factory_date ?? null,
            eta_date: data.eta_date ?? null,
            delivery_date: data.delivery_date ?? null,
            installation_date: data.installation_date ?? null,
            inspection_date: data.inspection_date ?? null,
            finish_date: data.finish_date ?? null,
            service_date: data.service_date ?? null,
            pending_collect: data.pending_collect ?? null,
            complete_date: data.complete_date ?? null,
            final_inspection_date: data.final_inspection_date ?? null,
            material_received_date: data.material_received_date ?? null,
            installation_end_date: data.installation_end_date ?? null,
            installation_teams: data.installation_teams.map((item) => { return { label: item.user?.name, value: item.id } }) ?? [],
            supervisor_id: data.supervisor?.id ?? 0, // Asumimos que `data.supervisor` es un objeto con los datos del superviso
            status: { label: data.status, value: data.status },
            hide_on_weekends: data.hide_on_weekends ?? null,
            notes: data.notes ?? '',
            pre_inspection: data.pre_inspection ?? false,
            inspection: data.inspection ?? false,
            walk_trough: data.walk_trough ?? false,
            partial_payment_installation: data.partial_payment_installation ?? false,
            final_payment_installation: data.final_payment_installation ?? false
          })
          const isVipClient = parseInt(data.client?.vip_clients?.toString() ?? '0') !== 0
          setIsVipClient(isVipClient)
        })
    } else {
      setEditableData(defaultState)
    }
  }, [showModal])
  // console.log(editableData)
  console.log(event)

  /* useEffect(() => {
    if (editableData.installation_date && event?.duration_of_work?.number_of_day) {
      const installationDate = new Date(editableData.installation_date)
      const duration = event?.duration_of_work?.number_of_day ?? 0
      const endDate = new Date(installationDate.setDate(installationDate.getDate() + duration - 1))
      // Actualizamos el estado de installation_end_date
      setEditableData((prevData: any) => ({
        ...prevData,
        installation_end_date: endDate.toISOString().slice(0, 10) // Formateamos la fecha como YYYY-MM-DD
      }))
    }
  }, [editableData.installation_date, event?.duration_of_work?.number_of_day]) */// Este efecto se ejecutará cuando cambie installation_date

  const handleInputChange = (field: keyof Order, value: string) => {
    setEditableData((prev: any) => ({ ...prev, [field]: value }))
  }

  const [showValidationErrors, setShowValidationErrors] = useState(false)

  const handle = () => {
    // Actualizamos editableData con los nuevos valores
    if (event?.service === 'DELIVERY AND INSTALLATION' && (editableData.installation_teams.length === 0 || editableData.supervisor_id === 0) && (editableData.status.value !== 'PLANNED' && editableData.status.value !== 'DELIVERY CONFIRMED' && editableData.status.value !== 'MATERIALS RECEIVED')) {
      setShowValidationErrors(true)
      return
    }

    let completeDate = editableData.complete_date
    if (editableData.status.value === 'COMPLETE') {
      if (!completeDate) {
        const today = new Date().toLocaleDateString('en-CA') // 'en-CA' devuelve 'YYYY-MM-DD'
        completeDate = today
      }
    }

    let pendingCollect = editableData.pending_collect
    if (editableData.status.value === 'PENDING COLLECT') {
      if (!pendingCollect) {
        const today = new Date().toLocaleDateString('en-CA') // 'en-CA' devuelve 'YYYY-MM-DD'
        pendingCollect = today
      }
    }
    const data = {
      ...editableData,
      installation_teams: editableData.installation_teams.map((team: any) => team.value),
      supervisor_id: editableData.supervisor_id || null,
      status: editableData.status.value,
      attachments: attachmentsArray,
      walk_trough_attach: attachmentIWalkTrough,
      inspection_attach: attachmentInspection,
      pre_inspection_attach: attachmentPreInspection,
      complete_date: completeDate,
      pending_collect: pendingCollect,
      order_id: id
    }

    router.post(route('update.order.from.modal', id), data, {
      forceFormData: true,
      onSuccess: (response) => {
        setEditableData(defaultState)
        onClose(false)
      },
      onError: (errors: any) => {
        console.log(errors)
      }
    })
    setShowValidationErrors(false)
    SetAttachmentWalkTrough([])
    SetAttachmentInspection([])
    SetAttachmentPreInspection([])
    setAttachmentsArray([])
    setMessage(null)
  }
  function setFieldValue(arg0: string, checked: boolean) {
    throw new Error('Function not implemented.')
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => {
        // setEditableData(defaultState)
        setEvent(null)
        onClose(false)
      }
      }
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Order Number: {`#${event?.order_number}`}</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false); setShowValidationErrors(false); setMessage(null) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
              {isVipClient && (
                <div className='flex flex-row gap-2'>
                  <div className='w-1/3'>
                    <strong>Client:</strong>
                    <div className='flex flex-row justify-start'>
                      {event?.client?.name}
                    </div>
                  </div>
                  <div className='w-1/3'>
                    <strong>Is VIP:</strong>
                    <div className='flex flex-row justify-start'>
                      VIP
                    </div>
                  </div>
                  <div className="w-1/3">
                    <strong>VIP Notes:</strong>
                    <div className="flex flex-row justify-start">
                        {event?.client?.vip_notes
                          ? event?.client?.vip_notes
                          : 'No VIP notes available'}
                    </div>
                  </div>
                </div>
              )}
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Order number:</strong> {`#${event?.order_number}`}
              </div>
              <div className='w-1/3'>
                <strong>Name:</strong> {event?.name}
              </div>
              <div className='w-1/3'>
              <strong>Address:</strong>{' '}
                      <a
                        href={`https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(`${event?.job_address ?? ''}, ${event?.city ?? ''}, ${event?.job_state ?? ''}, ${event?.job_zip ?? ''}`)}`} 
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-blue-500 underline"
                      >
                        {`${event?.job_address ?? ''}${event?.city ? `, ${event.city}` : ''}${event?.job_state ? `, ${event.job_state}` : ''}${event?.job_zip ? `, ${event.job_zip}` : ''}`}
                      </a>
              </div>
              <div className='w-1/3'>
                <strong>Client Phone:</strong>
                <a href={`tel:${event?.client?.phone}`} className="text-blue-500 underline">
                {event?.client?.phone}
                </a>
              </div>
            </div>
            <div className='flex flex-row gap-2 mt-3'>
              <div className='w-1/3'>
                <strong>Owner:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.owners.map((owner) => {
                    return <div key={owner.id} className='badge badge-outline-dark'>{owner.name}</div>
                  })}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Service:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.service}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Frame Color:</strong>
                <div className='flex flex-row justify-start'>
                {event?.order_colors && event?.order_colors.length > 0
                  ? event.order_colors.map(c => c.name).join(', ')
                  : ''}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2 mt-3'>
            {(event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='w-1/3'>
                <strong>Type of Housing:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.type_of_housing?.name}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Type of Work:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.type_of_work?.name}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>County:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.travel_cost?.name}
                </div>
              </div>
              </>
            )}
            </div>
            <div className='flex flex-row gap-2 mt-3'>
            {(event?.service === 'DELIVERY AND INSTALLATION' && !isInstaller) && (
              <>
              <div className='w-1/3'>
                <strong>Duration of Work:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.duration_of_work?.name}
                </div>
              </div>
              </>
            )}
             {!isInstaller && (
              <>
              <div className='w-1/3'>
                <strong>Payment Method:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.method_of_payment}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Other Cost:</strong>
                <div className='flex flex-row justify-start'>
                  {formatPrice(event?.additional_travel_costs ?? 0)}
                </div>
              </div>
              </>)}
            </div>
            {(event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
            <div className='flex flex-row gap-2 mt-3'>
              <div className='w-1/3'>
                <strong>City Permits:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.city_permits ?? true ? 'Yes' : 'No'}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Association Permits:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.association_permits ?? true ? 'Yes' : 'No'}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Rental Equipment:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.equipment_rental ?? true ? 'Yes' : 'No'}
                </div>
              </div>
            </div>
            </>
            )}

            {!isInstaller && (
            <>
            <div className='flex flex-row gap-2 mt-3'>
              <div className='w-1/3'>
              <label htmlFor="entry_date"><strong>Entry Date:</strong></label>
                <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="entry_date"
                value={editableData.entry_date ?? ''}
                className="form-input"
                disabled={!isAdminOrAccountManager}
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('entry_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
              <div className='w-1/3'>
              <label htmlFor="contract_signing_date"><strong>Contract Signing Date:</strong></label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                disabled={!isAdminOrAccountManager}
                name="contract_signing_date"
                value={editableData.contract_signing_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('contract_signing_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
              <div className='w-1/3'>
                <label htmlFor="payment_factory_date"><strong>Payment Factory Date:</strong></label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="payment_factory_date"
                disabled={!isAdminOrAccountManager}
                value={editableData.payment_factory_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('payment_factory_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
            </div>
            </>
            )}
            <div className='flex flex-row gap-2 mt-3'>
            {!isInstaller && (
              <div className='w-1/3'>
              <label htmlFor="eta_date"><strong>Eta Date: </strong></label>
              <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="eta_date"
                value={editableData.eta_date ?? ''}
                disabled={!isAdminOrAccountManager}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('eta_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>)}
              <div className='w-1/3'>
                <label htmlFor="delivery_date"><strong>Delivery/Pickup Date:</strong></label>
                <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="delivery_date"
                disabled={!isAdminOrAccountManager}
                value={editableData.delivery_date ?? ''}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('delivery_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
              {(event?.service === 'DELIVERY AND INSTALLATION') && (
              <div className='w-1/3'>
              <label htmlFor="installation_date"><strong>Installation Date:</strong></label>
                <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="installation_date"
                value={editableData.installation_date ?? ''}
                disabled={!isAdminOrAccountManager && !isSupervisor}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('installation_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
              )}
            </div>
            <div className='flex flex-row gap-2 mt-3'>
            {(event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='w-1/3'>
                <label htmlFor="installation_end_date"><strong>Installation End Date:</strong></label>
                <Flatpickr
                options={{
                  mode: 'single',
                  dateFormat: 'Y-m-d',
                  position: 'auto right'
                }}
                // disabled={values.supervisor_id === ''}
                name="installation_end_date"
                value={editableData.installation_end_date ?? ''}
                disabled={!isAdminOrAccountManager}
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('installation_end_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
              <div className='w-1/3'>
                  <label htmlFor="installationTeams"><strong>Installation Team: </strong></label>
                  <Select
                    id='installation_teams'
                    placeholder="Installation Team"
                    name='installation_teams'
                    isDisabled={!isAdminOrAccountManager}
                    value={editableData.installation_teams}
                    isMulti={true}
                    onChange={(value) => {
                      setEditableData({ ...editableData, installation_teams: value })
                    }}
                    options={installation_teams.map((installation_team) => { return { label: installation_team.user?.name, value: installation_team.id } })}
                  />
                </div>
                <div className='w-1/3'>
                  <label htmlFor="installationTeams" className='font-bold block'>Supervisor:</label>
                    <Select
                      id='supervisor'
                      placeholder="supervisor"
                      name='supervisor'
                      isDisabled={!isAdminOrAccountManager}
                      value ={{ label: supervisors.find((s) => s.id === editableData.supervisor_id)?.name, value: editableData.supervisor_id }}
                      isMulti={false}
                      onChange={(value) => { setEditableData({ ...editableData, supervisor_id: value?.value }) }}
                      options={supervisors.map((supervisor) => { return { label: supervisor.name, value: supervisor.id } })}
                    />
              </div>
              </>
            )}
            </div>

            {!(isInstaller || isOwner) && (
            <>
            <div className='flex flex-row gap-5 mt-3'>
                    <fieldset className='p-3 border rounded-xl mt-3'>
                    <legend className='text-lg font-semibold'>Collected Payments</legend>
                     <div className ='flex mt-8'>
                      <input
                      id="partial_payment_installation"
                      name="partial_payment_installation"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.partial_payment_installation} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, partial_payment_installation: e.target.checked }) // Actualiza el estado
                      }}
                    />
                        <label htmlFor="partial_payment_installation" className='font-bold inline-flex'>Partial Payment Installation</label>
                      </div>

                      <div className ='flex mt-8'>
                      <input
                      id="final_payment_installation"
                      name="final_payment_installation"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.final_payment_installation} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, final_payment_installation: e.target.checked }) // Actualiza el estado
                      }}
                    />
                        <label htmlFor="inspection" className='font-bold inline-flex'>Final Payment Installation</label>
                      </div>
                  </fieldset>
            </div>
            </>
            )}
            <div className='flex flex-row gap-2 mt-3'>
              <div className='w-1/3'>
                <label htmlFor="status" className='font-bold'>Status:</label>
                  <Select
                    id='status'
                    placeholder="status"
                    name='status'
                    value={editableData.status}
                    isDisabled={isInstaller || isOwner}
                    isMulti={false}
                    onChange={(value) => { setEditableData({ ...editableData, status: value }) }}
                    options={(() => {
                      let options = status.map((status) => { return { label: status, value: status } })
                      if (event?.service === 'PICKUP' || event?.service === 'DELIVERY ONLY') {
                        options = status.filter((status) =>
                          status === 'PLANNED' ||
                          status === 'COMPLETE' ||
                          status === 'CONFIRMED' ||
                          status === 'DELIVERY CONFIRMED'
                        ).map((status) => { return { label: status, value: status } })
                      }

                      return options
                    })()}
                  />
              </div>
              {(isAdminOrAccountManager) && (
              <div className='w-1/3  mt-8'>
                   <input
                      id="hide_on_weekends"
                      name="hide_on_weekends"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.hide_on_weekends} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, hide_on_weekends: e.target.checked }) // Actualiza el estado
                      }}
                    />
                    <label htmlFor="hide_on_weekends" className='font-bold inline-flex'>Hide On Weekends</label>
                  </div>
              )}
            </div>
            {((editableData.status.value === 'INSPECTION' || editableData.inspection_date != null) && !isInstaller) && (
                    <div className='w-1/3  mt-8'>
                    <label htmlFor="inspection_date"><strong>Inspection Date:</strong></label>
                    <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}

                    name="inspection_date"
                    value={editableData.inspection_date ?? ''}
                    // disabled={!isAdminOrAccountManager && isSupervisor}
                    className="form-input"
                    disabled={isInstaller || isOwner}
                    onChange={([date]) => {
                      if (date) {
                        // Manejar la fecha seleccionada
                        handleInputChange('inspection_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                      }
                    }}
                  />
                  </div>
            )}
                    {(editableData.status.value === 'FINISH' || editableData.finish_date != null) && (
                    <div className='w-1/3  mt-8'>
                    <label htmlFor="finish_date"><strong>Finish Date:</strong></label>
                    <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}

                    name="finish_date"
                    value={editableData.finish_date ?? ''}
                    disabled={isInstaller || isOwner}
                    className="form-input"
                    onChange={([date]) => {
                      if (date) {
                        // Manejar la fecha seleccionada
                        handleInputChange('finish_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                      }
                    }}
                  />
                  </div>
                    )}

                  {((editableData.status.value === 'SERVICE' || editableData.service_date != null) && !isInstaller) && (
                    <div className='w-1/3  mt-8'>
                    <label htmlFor="service_date"><strong>Service Date:</strong></label>
                    <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}

                    name="service_date"
                    value={editableData.service_date ?? ''}
                    disabled={isInstaller || isOwner}
                    className="form-input"
                    onChange={([date]) => {
                      if (date) {
                        // Manejar la fecha seleccionada
                        handleInputChange('service_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                      }
                    }}
                  />
                  </div>
                  )}
                 {((editableData.status.value === 'FINAL INSPECTION' || editableData.final_inspection_date != null) && !isInstaller) && (
                    <div className='w-1/3  mt-8'>
                    <label htmlFor="final_inspection_date"><strong>Final Inspection Date:</strong></label>
                    <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}

                    name="final_inspection_date"
                    value={editableData.final_inspection_date ?? ''}
                    disabled={isInstaller || isOwner}
                    className="form-input"
                    onChange={([date]) => {
                      if (date) {
                        // Manejar la fecha seleccionada
                        handleInputChange('final_inspection_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                      }
                    }}
                  />
                  </div>
                 )}
                  {((editableData.status.value === 'MATERIALS RECEIVED' || editableData.material_received_date != null) && isAdminOrAccountManager) && (
                    <div className='w-1/3  mt-8'>
                    <label htmlFor="material_received_date"><strong>Materials Received Date:</strong></label>
                    <Flatpickr
                    options={{
                      mode: 'single',
                      dateFormat: 'Y-m-d',
                      position: 'auto right'
                    }}

                    name="material_received_date"
                    value={editableData.material_received_date ?? ''}
                    // disabled={!isAdminOrAccountManager && isSupervisor}
                    className="form-input"
                    disabled={isInstaller || isOwner}
                    onChange={([date]) => {
                      if (date) {
                        // Manejar la fecha seleccionada
                        handleInputChange('material_received_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                      }
                    }}
                  />
                  </div>
            )}
            {event?.work_team_notes && (
              <div className='flex flex-col gap-2'>
                  <strong>Work Team Notes:</strong>
                  <div className='flex flex-row justify-start'>
                    {event?.work_team_notes ?? ''}
                  </div>
              </div>
            )}
            <div className='col-span-4 mt-4'>
              <label htmlFor="notes"><strong>Notes </strong></label>
              <textarea
                id="notes"
                name="notes"
                rows = {6}
                value={editableData.notes ?? ''}
                disabled={isInstaller }
                className="form-textarea resize-none placeholder:text-white-dark"
                placeholder='Notes'
                onChange={(e) => { setEditableData({ ...editableData, notes: e.target.value }) }}
              />
            </div>
            {!(isInstaller || isOwner) && (
            <>
            <div className='col-span-4'>
            <fieldset className='p-3 border rounded-xl mt-3'>
                    <legend className='text-lg font-semibold'>Delivered Documents</legend>
                    <div className='grid gap-3 grid-cols-3'>
                    {(Number(event?.city_permits) === 1 && (
                     <>
                      <div className ='flex mt-8'>
                      <input
                      id="pre_inspection"
                      name="pre_inspection"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.pre_inspection} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, pre_inspection: e.target.checked }) // Actualiza el estado
                      }}
                    />
                        <label htmlFor="pre_inspection" className='font-bold inline-flex'>PI</label>
                      </div>

                      <div className ='flex mt-8'>
                      <input
                      id="inspection"
                      name="inspection"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.inspection} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, inspection: e.target.checked }) // Actualiza el estado
                      }}
                    />
                        <label htmlFor="inspection" className='font-bold inline-flex'>IN</label>
                      </div>
                      </>
                    ))}
                      <div className ='flex mt-8'>
                      <input
                      id="walk_trough"
                      name="walk_trough"
                      className="form-checkbox"
                      type="checkbox"
                      checked={editableData.walk_trough} // Controlado por el estado
                      onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                        setEditableData({ ...editableData, walk_trough: e.target.checked }) // Actualiza el estado
                      }}
                    />
                        <label htmlFor="walk_trough" className='font-bold inline-flex'>WT</label>
                      </div>
                    </div>
                  </fieldset>
                  </div>
                   </>
            )}
            {((editableData.pre_inspection === true || editableData.pre_inspection === 1) && !(isInstaller || isOwner)) && (
            <div className='flex flex-col gap-2  mt-3'>
            <label htmlFor="pre_inspection_attach">Pre Inspection File</label>
                    <input
                      id="pre_inspection_attach"
                      name="pre_inspection_attach"
                      type="file"
                      accept="*"
                      className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                      onChange={(event: any) => {
                        SetAttachmentPreInspection(event.currentTarget.files[0])
                      }}
              />
            </div>)}
            {((editableData.inspection === true || editableData.inspection === 1) && !isInstaller && !(isInstaller || isOwner)) && (
            <div className='flex flex-col gap-2  mt-3'>
            <label htmlFor="inspection_attach">Inspection File</label>
                    <input
                      id="inspection_attach"
                      name="inspection_attach"
                      type="file"
                      accept="*"
                      className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"

                      onChange={(event: any) => {
                        SetAttachmentInspection(event.currentTarget.files[0])
                      }}
              />
            </div>)}
            {((editableData.walk_trough === true || editableData.walk_trough === 1) && !(isInstaller || isOwner)) && (
            <div className='flex flex-col gap-2  mt-3'>
            <label htmlFor="walk_trough_attach">Walk Trough File</label>
                    <input
                      id="walk_trough_attach"
                      name="walk_trough_attach"
                      type="file"
                      accept="*"
                      className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                      /* onChange={(event: any) => {
                        setFieldValue('walk_trough_attach', event.currentTarget.files[0])
                      }} */
                        onChange={(event: any) => {
                          SetAttachmentWalkTrough(event.currentTarget.files[0])
                        }}
              />
            </div>)}
            {attachmentsList && (event?.service === 'DELIVERY AND INSTALLATION') && (
                <>
               <div className='flex flex-col gap-2  mt-3'>
               {!isInstaller && (
                <>
               <label htmlFor="attachments" className='font-bold'>Attachments:</label>
               <input
                 id="attachments"
                 name="attachments"
                 type="file"
                 accept="*"
                 className="form-input file:py-2 file:px-4 file:border-0 file:font-semibold p-0 file:bg-primary/90 ltr:file:mr-5 rtl:file:ml-5 file:text-white file:hover:bg-primary"
                 placeholder="Qty"
                 multiple={true}
                 onChange={(event: any) => {
                   setAttachmentsArray(event.currentTarget.files)
                 }}
               />
               </>)}
                 <div className="flex flex-col rounded-md border border-[#e0e6ed] dark:border-[#1b2e4b] mt-3">
                  {message !== '' && (
                    <div className='flex items-center p-3.5 rounded text-danger dark:bg-danger-dark-light'>{message}</div>
                  )}
                  <table className='w-full whitespace-nowrap'>
                  <tbody>
                   {attachmentsList.map((attachment, index) => {
                     return (
                       <tr key={index} className='hover:bg-gray-100 focus-within:bg-gray-100'>

                         <td className='border-t px-6 py-4 align-top'>{attachment.filename}</td>
                         <td className='border-t px-6 py-4 align-top'>{attachment.file_type}</td>
                         <td className='border-t px-6 py-4 align-top'>
                          <div className='flex flex-row gap-2 justify-end'>
                            <a key={attachment.id} href={`storage/${attachment.file_path}`} target='_blank' rel="noreferrer">
                              <ExportIcon />
                            </a>
                            <button
                              onClick={(e) => {
                                e.preventDefault()
                                removeAttachmentProduct(index)
                              }}

                              title='Delete Attachment'
                            >
                              <DeleteIcon />
                            </button>
                          </div>
                         </td>
                       </tr>
                     )
                   })}
                   </tbody>
                  </table>
                 </div>
             </div>
             </>
            )}
            {(isAdminOrAccountManager || isInstaller || isPaymentCoordinator) && (event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='flex flex-col gap-2  mt-3'>
                <strong>Payment List:</strong>
                <div className='flex flex-col justify-start'>
                  <a href={route('order.get_payment_list', { id: event?.id ?? 0 })} target='_blank' className='badge badge-outline-dark' rel="noreferrer">Download Payment List</a>
                </div>
              </div>
             {/* <ProductTable
                  orderProducts={orderProducts}
                  type_of_products={type_of_products}
                  product_category={product_category}
                  products_config={products_config}
                  service={values.service}
                  values= {values}
                  travel_costs={travel_costs}
                  removeOrderProduct={(index: number) => { removeOrderProduct(index) }}
                  updateOrderProduct={(index: number) => { updateOrderProduct(index) }}
                /> */}
              </>
            )}
              {(isSupervisor) && (event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='flex flex-col gap-2  mt-3'>
                <strong>Payment List:</strong>
                <div className='flex flex-col justify-start'>
                  <a href={route('order.get_supervisor_list', { id: event?.id ?? 0 })} target='_blank' className='badge badge-outline-dark' rel="noreferrer">Download Payment List</a>
                </div>
              </div>
             {/* <ProductTable
                  orderProducts={orderProducts}
                  type_of_products={type_of_products}
                  product_category={product_category}
                  products_config={products_config}
                  service={values.service}
                  values= {values}
                  travel_costs={travel_costs}
                  removeOrderProduct={(index: number) => { removeOrderProduct(index) }}
                  updateOrderProduct={(index: number) => { updateOrderProduct(index) }}
                /> */}
              </>
            )}
          </div>
          {showValidationErrors && (
            <div className='flex flex-row gap-2'>
              <InputError message='Please select an installation team and a supervisor' />
            </div>
          )}
          {((isAdminOrAccountManager) || (isSupervisor) || (isServiceManager) || (isOwner)) && (
            <div className="flex items-center justify-between mt-4">
              <button className='btn btn-danger uppercase' onClick={() => { onClose(); setShowValidationErrors(false); setMessage(null) }}>Cancel</button>
              <PrimaryButton className="btn btn-primary" type='button' onClick={() => { handle() }}>
                Save
              </PrimaryButton>
            </div>
          )}
        </div>
    </Modal>
  )
}

export default EventModal
