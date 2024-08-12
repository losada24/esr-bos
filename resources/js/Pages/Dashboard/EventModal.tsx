import React, { useState, useEffect } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order } from '@/types'

const EventModal = ({
  showModal,
  onClose,
  id
}: {
  showModal: boolean
  onClose: CallableFunction
  id: number
}) => {
  const [event, setEvent] = useState<Order | null>(null)

  useEffect(() => {
    if (id !== 0) {
      const url = route('dashboard.get_event', { id })
      fetch(url)
        .then(async (response) => await response.json())
        .then((data) => {
          console.log(data)
          setEvent(data)
        })
    }
  }, [id])

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Order Number: {`#${event?.order_number}`}</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className="h-[550px] overflow-y-scroll">
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Order number:</strong> {`#${event?.order_number}`}
              </div>
              <div className='w-1/3'>
                <strong>Name:</strong> {event?.name}
              </div>
              <div className='w-1/3'>
                <strong>Address:</strong> {event?.job_address}
              </div>
            </div>
            <div className='flex flex-row  gap-2'>
              <div className='w-1/3'>
                <strong>Owner:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.owners.map((owner) => {
                    return <div key={owner.id} className='badge badge-outline-dark'>{owner.name}</div>
                  })}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Installation Team:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.installation_teams.map((installation_team) => {
                    return <div key={installation_team.id} className='badge badge-outline-dark'>{installation_team.user?.name}</div>
                  })}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Supervisor:</strong>
                <div className='flex flex-row justify-start'>
                  {event?.supervisor?.name}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Type of Housing:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.type_of_housing?.name}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Type of Work:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.type_of_work?.name}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>County:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.travel_cost?.name}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Duration of Work:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.duration_of_work?.name}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Payment Method:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.method_of_payment}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Service:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.service}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Entry Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.entry_date ? new Date(event?.entry_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Contract Signing Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.contract_signing_date ? new Date(event?.contract_signing_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Payment Factory Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.payment_factory_date ? new Date(event?.payment_factory_date).toLocaleDateString() : ''}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>ETA Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.eta_date ? new Date(event?.eta_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Delivery/Pickup Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.delivery_date ? new Date(event?.delivery_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Frame Color:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.frame_color}
                </div>
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>Installation Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.installation_date ? new Date(event?.installation_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Installation End Date:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.installation_end_date ? new Date(event?.installation_end_date).toLocaleDateString() : ''}
                </div>
              </div>
              <div className='w-1/3'>
                  &nbsp;
              </div>
            </div>
            <div className='flex flex-row gap-2'>
              <div className='w-1/3'>
                <strong>City Permits:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.city_permits}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Association Permits:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.association_permits}
                </div>
              </div>
              <div className='w-1/3'>
                <strong>Rental Equipment:</strong>
                <div className='felx flex-row justify-start'>
                  {event?.equipment_rental}
                </div>
              </div>
            </div>
          </div>
        </div>
    </Modal>
  )
}

export default EventModal
