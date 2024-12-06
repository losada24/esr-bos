import React, { useState, useEffect } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order, type InstallationTeam, type User } from '@/types'
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

const EventModal = ({
  showModal,
  onClose,
  id,
  isAdminOrAccountManager,
  installation_teams,
  supervisors,
  status

}: {
  showModal: boolean
  onClose: CallableFunction
  id: number
  isAdminOrAccountManager: boolean
  installation_teams: InstallationTeam[]
  supervisors: User[]
  status: string[]
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
    installation_team_id: [],
    additional_travel_costs: 0,
    type_of_work_id: 0,
    type_of_housing_id: 0,
    city_permits: false,
    association_permits: false,
    equipment_rental: false,
    notes: '',
    work_team_notes: '',
    frame_color: '',
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
    status: ''
  }
  const [event, setEvent] = useState<Order | null>(null)
  const [isVipClient, setIsVipClient] = useState<boolean>(false)
  const [editableData, setEditableData] = useState<any>(defaultState)
  const [isLoading, setIsLoading] = useState(false)

  // console.log(id)
  useEffect(() => {
    if (id !== 0 && showModal) {
      // setIsLoading(true)
      const url = route('dashboard.get_event', { id })
      fetch(url)
        .then(async (response) => await response.json())
        .then((data: Order) => {
          setEvent(data)
          // console.log(data.status)
          setEditableData({
            entry_date: data.entry_date ?? null,
            contract_signing_date: data.contract_signing_date ?? null,
            payment_factory_date: data.payment_factory_date ?? null,
            eta_date: data.eta_date ?? null,
            delivery_date: data.delivery_date ?? null,
            installation_date: data.installation_date ?? null,
            installation_end_date: data.installation_end_date ?? null,
            installation_teams: data.installation_teams.map((item) => { return { label: item.user?.name, value: item.id }}) ?? [],
            supervisor_id: data.supervisor?.id ?? 0, // Asumimos que `data.supervisor` es un objeto con los datos del superviso
            status: { label: data.status, value: data.status }
          })
          const isVipClient = parseInt(data.client?.vip_clients?.toString() ?? '0') !== 0
          setIsVipClient(isVipClient)
        })
    }
  }, [showModal])

  useEffect(() => {
    if (id === 0) {
      setEditableData(defaultState)
      setEvent(null)
    }
  }, [showModal])

  const handleInputChange = (field: keyof Order, value: string) => {
    setEditableData((prev: any) => ({ ...prev, [field]: value }))
  }

  const handle = () => {
    console.log(editableData)
    // Actualizamos editableData con los nuevos valores
    const data = {
      ...editableData,
      installation_teams: editableData.installation_teams.map((team: any) => team.value),
      supervisor_id: editableData.supervisor_id || null,
      status: editableData.status.value
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
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
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
                <strong>Address:</strong> {`${event?.job_address ?? ''}${event?.city ? `, ${event.city}` : ''}${event?.job_state ? `, ${event.job_state}` : ''}${event?.job_zip ? `, ${event.job_zip}` : ''}`}
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
                  {event?.frame_color}
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
            {(event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='w-1/3'>
                <strong>Duration of Work:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.duration_of_work?.name}
                </div>
              </div>
              </>
            )}
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
            <div className='flex flex-row gap-2 mt-3'>
              <div className='w-1/3'>
              <label htmlFor="contract_signing_date"><strong>Entry Date:</strong></label>
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
                // disabled={values.supervisor_id === ''}
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
            <div className='flex flex-row gap-2 mt-3'>
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
                className="form-input"
                onChange={([date]) => {
                  if (date) {
                    // Manejar la fecha seleccionada
                    handleInputChange('eta_date', date.toISOString().slice(0, 10)) // Guardar en formato 'YYYY-MM-DD'
                  }
                }}
              />
              </div>
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
                      value ={{ label: supervisors.find((s) => s.id === editableData.supervisor_id)?.name, value: editableData.supervisor_id }}
                      isMulti={false}
                      onChange={(value) => { setEditableData({ ...editableData, supervisor_id: value?.value }) }}
                      options={supervisors.map((supervisor) => { return { label: supervisor.name, value: supervisor.id } })}
                    />
              </div>
              </>
            )}
            </div>
            <div className='flex flex-row'>
              <div className='w-1/3'>
                <label htmlFor="status" className='font-bold'>Status:</label>
                  <Select
                    id='status'
                    placeholder="status"
                    name='status'
                    value={editableData.status}
                    isMulti={false}
                    onChange={(value) => { setEditableData({ ...editableData, status: value }) }}
                    options={status.map((status) => { return { label: status, value: status } })}
                  />
              </div>
            </div>
            {event?.notes && (
              <div className='flex flex-col gap-2  mt-3'>
                  <strong>Notes:</strong>
                  <div className='flex flex-row justify-start'>
                    {event?.notes ?? ''}
                  </div>
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
            {event?.attachments && (
              <div className='flex flex-row gap-2 mt-3'>
                <strong>Attachments:</strong>
                <div className='flex flex-col justify-start'>
                  {event?.attachments.map((attachment) => {
                    return <a key={attachment.id} href={`storage/${attachment.file_path}`} target='_blank' className='badge badge-outline-dark' rel="noreferrer">{attachment.filename}</a>
                  })}
                </div>
              </div>
            )}
            {isAdminOrAccountManager && (event?.service === 'DELIVERY AND INSTALLATION') && (
              <>
              <div className='flex flex-row gap-2'>
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
          </div>
          {isAdminOrAccountManager && (
            <div className="flex items-center justify-between mt-4">
              <button className='btn btn-danger uppercase' onClick={() => onClose()}>Cancel</button>
              <PrimaryButton className="btn btn-primary" type='button' onClick={() => handle()}>
                Save
              </PrimaryButton>
            </div>
          )}
        </div>
    </Modal>
  )
}

export default EventModal
