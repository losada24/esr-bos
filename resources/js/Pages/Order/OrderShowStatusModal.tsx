import { useState, useEffect } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order, type OrderStatus } from '@/types'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { getInitials } from '@/Utils/string'

const OrderShowStatusModal = ({ showModal, onClose, order }: {
  showModal: boolean
  onClose: CallableFunction
  order: Order | null
}) => {
  const [orderStatus, setOrderStatus] = useState<OrderStatus[]>([])

  useEffect(() => {
    if (order === null) return
    fetch(route('order.history', { order: order?.id ?? 0 })).then(async (response) => { return await response.json() }).then((data) => {
      console.log(data)
      setOrderStatus(data)
    })
  }, [order])

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Order { createMarkWithLeadingZero(order?.id ?? 0, 6) } Status History</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className='h-96 overflow-y-auto'>
              {orderStatus.map((status) => {
                return (
                  <div key={status.id} className="sm:flex">
                    <div className="relative mx-auto mb-5 sm:mb-0 ltr:sm:mr-8 rtl:sm:ml-8 z-[2] before:absolute before:top-12 before:left-1/2 before:-bottom-[15px] before:-translate-x-1/2 before:border-l-2 before:border-[#ebedf2] before:w-0 before:h-auto before:-z-[1] dark:before:border-[#191e3a] before:hidden sm:before:block">
                        <div className='h-10 w-10 bg-primary rounded-md flex items-center justify-center text-white'>
                          <span>{getInitials(status?.user?.name ?? '')}</span>
                        </div>
                    </div>
                    <div className="flex-1">
                        <h4 className="text-primary text-xl font-bold text-center ltr:sm:text-left rtl:sm:text-right">{status?.user?.name}</h4>
                        <p className="text-center ltr:sm:text-left rtl:sm:text-right">{status.created_at}</p>
                        <div className="mt-4 sm:mt-7 mb-16">
                            <h6 className="inline-block font-bold mb-2 text-lg">{status.status.toUpperCase()}</h6>
                            <p className="ltr:pl-8 rtl:pr-8 text-white-dark font-semibold">{status.notes}</p>
                        </div>
                    </div>
                  </div>
                )
              })}
            </div>
        </div>
    </Modal>
  )
}

export default OrderShowStatusModal
