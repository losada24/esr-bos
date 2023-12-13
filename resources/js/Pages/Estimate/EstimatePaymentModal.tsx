import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order } from '@/types'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { Link, router } from '@inertiajs/react'
import PrimaryButton from '@/Components/PrimaryButton'
import { type FormikHelpers, Formik, Form } from 'formik'

const EstimatePaymentModal = ({ showModal, onClose, estimate }: {
  showModal: boolean
  onClose: CallableFunction
  estimate: Order | null
}) => {
  const handleSubmit = async (values: any, helpers: FormikHelpers<Order>) => {
    router.post(route('estimate.order.store', values.id), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      },
      onSuccess: () => {
        onClose(false)
      }
    })
  }

  const initialValues: Order = {
    id: estimate?.id ?? 0,
    frame_color: estimate?.frame_color ?? '',
    glass_color: estimate?.glass_color ?? '',
    name: estimate?.name ?? '',
    notes: estimate?.notes ?? '',
    project_name: estimate?.project_name ?? '',
    markup: estimate?.markup ?? 0,
    client_id: estimate?.client_id ?? 0,
    tax_amount: estimate?.tax_amount ?? 0,
    tax_rate: estimate?.tax_rate ?? 0,
    installation: estimate?.installation ?? 0,
    permit: estimate?.permit ?? 0,
    other: estimate?.other ?? 0,
    glass_type: estimate?.glass_type ?? ''
  }

  return (
    <Modal
      show={showModal}
      closeable={true}
      onClose={() => { onClose(false) }}
    >
        <div className="flex items-center justify-between bg-[#fbfbfb] px-5 py-3 dark:bg-[#121c2c]">
          <div className="text-lg font-bold">Add Order { createMarkWithLeadingZero(estimate?.id ?? 0, 6) }</div>
          <button type="button" className="text-white-dark hover:text-dark" onClick={() => { onClose(false) }}>
            <CloseIcon />
          </button>
        </div>
        <div className='p-5'>
          <div className='flex flex-col justify-start'>
            <div>
              Convert Estimate to Order
            </div>
            <Formik<Order>
              initialValues={initialValues}
              // validationSchema={estimateSchema}
              onSubmit={handleSubmit}
            >
              {() => (
                <Form className='w-full'>
                  <div className="flex items-center justify-between mt-4">
                    <Link className='btn btn-danger uppercase' href={route('estimate.index')}>Cancel</Link>
                    <PrimaryButton className="btn btn-primary" type='submit'>
                      Save
                    </PrimaryButton>
                  </div>
                </Form>
              )}
            </Formik>
          </div>
        </div>
    </Modal>
  )
}

export default EstimatePaymentModal
