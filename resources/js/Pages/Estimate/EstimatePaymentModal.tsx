import Modal from '@/Components/Modal'
import CloseIcon from '@/Components/Icons/CloseIcon'
import { type Order, type PaymentMethod } from '@/types'
import { createMarkWithLeadingZero } from '@/Utils/mark'
import { Link, router } from '@inertiajs/react'
import PrimaryButton from '@/Components/PrimaryButton'
import { type FormikHelpers, Formik, Form, Field } from 'formik'
import { PAYMENT_METHODS } from '@/Utils/constants'

const EstimatePaymentModal = ({ showModal, onClose, estimate }: {
  showModal: boolean
  onClose: CallableFunction
  estimate: Order | null
}) => {
  const handleSubmit = async (values: any, helpers: FormikHelpers<PaymentMethod>) => {
    console.log(values)
    /* router.post(route('estimate.order.store', values.id), values, {
      forceFormData: true,
      onError: (errors: any) => {
        helpers.setErrors(errors)
      },
      onSuccess: () => {
        onClose(false)
      }
    }) */
  }

  const initialValues: PaymentMethod = {
    paymentMethod: PAYMENT_METHODS.CASH
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
            <Formik<PaymentMethod>
              initialValues={initialValues}
              // validationSchema={estimateSchema}
              onSubmit={handleSubmit}
            >
              {({ values }) => (
                <Form className='w-full space-y-5'>
                  <div className=''>
                    <label className="inline-flex" htmlFor='paymentMethod'>
                        <Field
                          type="radio"
                          className="form-checkbox rounded-full"
                          name='paymentMethod'
                          value={PAYMENT_METHODS.CASH}
                        />
                        <span>Cash/Zelle</span>
                    </label>

                  </div>
                  <div className=''>
                    <label className="inline-flex" htmlFor='paymentMethod'>
                        <Field type="radio"
                          className="form-checkbox rounded-full"
                          name='paymentMethod'
                          value={PAYMENT_METHODS.CHECK}
                        />
                        <span>Check Payment</span>
                    </label>
                  </div>
                  <div className=''>
                    <label className="inline-flex" htmlFor='paymentMethod'>
                        <Field type="radio"
                          className="form-checkbox rounded-full"
                          name='paymentMethod'
                          value={PAYMENT_METHODS.BANK_TRANSFER}
                        />
                        <span>Direct Bank Transfer</span>
                    </label>
                  </div>
                  {estimate?.user?.company?.allow_credit_payment && (
                    <div className=''>
                      <label className="inline-flex" htmlFor='paymentMethod'>
                          <Field type="radio"
                            className="form-checkbox rounded-full"
                            name='paymentMethod'
                            value={PAYMENT_METHODS.CREDIT}
                          />
                          <span>RG Impact System Credit</span>
                      </label>
                    </div>
                  )}
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
