import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order } from '@/types'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { router } from '@inertiajs/react'
import { type FormikHelpers, Formik } from 'formik'
import OrderStatusUpdateForm from './OrderStatusUpdateForm'
import { orderStatusUpdateSchema, type OrderStatusUpdate } from './OrderCommon'

const OrderUpdateStatusModal = ({ showModal, onClose, order, statuses }: {
  showModal: boolean
  onClose: CallableFunction
  order: Order | null
  statuses: string[]
}) => {
  const handleSubmit = async (values: any, helpers: FormikHelpers<OrderStatusUpdate>) => {
    router.post(route('order.status.update'), values, {
      onError: (errors: any) => {
        helpers.setErrors(errors)
      },
      onSuccess: () => {
        onClose(false)
      }
    })
  }

  const initialValues: OrderStatusUpdate = {
    id: order?.id ?? 0,
    status: '',
    notes: ''
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Update Order { createMarkWithLeadingZero(order?.id ?? 0, 6) }</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className='flex flex-col justify-start'>
            <Formik<OrderStatusUpdate>
              initialValues={initialValues}
              validationSchema={orderStatusUpdateSchema}
              onSubmit={handleSubmit}
            >
              {() => (
                <OrderStatusUpdateForm
                  submitCount={0}
                  errors={{}}
                  isCreate={false}
                  statuses={statuses}
                />
              )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default OrderUpdateStatusModal
