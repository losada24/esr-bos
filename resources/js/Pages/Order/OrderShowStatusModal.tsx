import { useState, useEffect } from 'react'
import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type User, type Order, type OrderStatus, type Role } from '@/types'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { getInitials } from '@/Utils/string'
import { isAccountManager, isAdmin } from '@/Utils/user'
import EditIcon from '@/Components/Icons/EditIcon'
import EditNote from './EditNote'

const OrderShowStatusModal = ({ showModal, onClose, order, user }: {
  showModal: boolean
  onClose: CallableFunction
  order: Order | null
  user: User
}) => {
  const [orderStatus, setOrderStatus] = useState<OrderStatus[]>([])
  const IS_ADMIN = isAdmin(user.roles.map((role: Role) => role.name))
  const IS_ACCOUNT_MANAGER = isAccountManager(user.roles.map((role: Role) => role.name))
  const [editNote, setEditNote] = useState<number | null>(null)

  useEffect(() => {
    if (order === null) return
    fetch(route('order.history', { order: order?.id ?? 0 })).then(async (response) => { return await response.json() }).then((data) => {
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
          <div className="h-96 overflow-y-auto pr-3">
              {orderStatus.map((status) => {
                return (
                  <div key={status.id} className="sm:flex">
                    <div className="relative mx-auto mb-5 sm:mb-0 ltr:sm:mr-4 rtl:sm:ml-8 z-[2] before:absolute before:top-12 before:left-1/2 before:-bottom-[15px] before:-translate-x-1/2 before:border-l-2 before:border-[#ebedf2] before:w-0 before:h-auto before:-z-[1] dark:before:border-[#191e3a] before:hidden sm:before:block">
                        <div className='h-10 w-10 bg-primary rounded-md flex items-center justify-center text-white'>
                          <span>{getInitials(status?.user?.name ?? '')}</span>
                        </div>
                    </div>
                    <div className="flex-1">
                      <div className='flex justify-between'>
                        <div className="">
                          <h4 className="text-primary text-xl font-bold text-center ltr:sm:text-left rtl:sm:text-right">{status?.user?.name}</h4>
                          <p className="text-center ltr:sm:text-left rtl:sm:text-right">{status.created_at}</p>
                        </div>
                        <div className='flex flex-col items-end'>
                          <h6 className="inline-block font-bold">{status.status.toUpperCase()}</h6>
                          {(IS_ADMIN || IS_ACCOUNT_MANAGER || status?.user?.id === user.id) && editNote !== status.id && (
                              <button onClick={() => {
                                setEditNote(status.id)
                              }} className="btn btn-outline-primary btn-sm w-28"><EditIcon /> Edit Note</button>
                          )}
                        </div>
                      </div>
                      <div className="mt-3 sm:mt-3 mb-8">
                          {editNote !== status.id
                            ? <div className="ql-editor" dangerouslySetInnerHTML={{ __html: status.notes }} />
                            : <EditNote
                                status={status}
                                onComplete={(updatedStatus: OrderStatus | null) => {
                                  if (updatedStatus === null) {
                                    setEditNote(null)
                                  } else {
                                    setOrderStatus(orderStatus.map((s) => s.id === updatedStatus.id ? updatedStatus : s))
                                    setEditNote(null)
                                  }
                                }}
                              /> }
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
